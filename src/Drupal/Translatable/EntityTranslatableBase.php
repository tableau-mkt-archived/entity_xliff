<?php

/**
 * @file
 * Base class for making entities translatable.
 */

namespace EntityXliff\Drupal\Translatable;

use EggsCereal\Utils\Data;
use EntityXliff\Drupal\Exceptions\EntityDataGoneAwayException;
use EntityXliff\Drupal\Exceptions\EntityStructureDivergedException;
use EntityXliff\Drupal\Factories\EntityTranslatableFactory;
use EntityXliff\Drupal\Interfaces\EntityTranslatableInterface;
use EntityXliff\Drupal\Mediator\EntityMediator;
use EntityXliff\Drupal\Mediator\FieldMediator;
use EntityXliff\Drupal\Utils\DrupalHandler;


/**
 * Class EntityTranslatableBase
 */
abstract class EntityTranslatableBase implements EntityTranslatableInterface {

  /**
   * @var \EntityDrupalWrapper
   */
  protected $entity;

  /**
   * @var DrupalHandler
   */
  protected $drupal;

  /**
   * @var array
   */
  protected $entityInfo;

  /**
   * @var EntityTranslatableFactory
   */
  protected $translatableFactory;

  /**
   * @var FieldMediator
   */
  protected $fieldMediator;

  /**
   * @var \EntityDrupalWrapper[]
   */
  protected $targetEntities = array();

  /**
   * An array of entity metadata wrappers for which data has been updated and
   * should be saved.
   *
   * @var \EntityDrupalWrapper[]
   */
  protected $entitiesNeedSave = array();

  /**
   * Depth of recursion for each entity needing to be saved. Ensures that they
   * are saved in the correct order and that revisions are maintained.
   *
   * @var int
   */
  protected $entitiesNeedSaveDepth = 0;

  /**
   * @var EntityTranslatableInterface[]
   */
  protected $translatables = array();

  /**
   * The source language of the wrapped entity.
   *
   * @var string
   */
  protected $sourceLanguage;

  /**
   * Creates a Translatable from an Entity wrapper.
   *
   * @param \EntityDrupalWrapper $entityWrapper
   *   Metadata wrapper for the entity we wish to translate.
   *
   * @param DrupalHandler $handler
   *   (Optional) Inject the utility Drupal handler.
   *
   * @param EntityTranslatableFactory $factory
   *   (Optional) Inject the entity translatable factory.
   *
   * @param FieldMediator $fieldMediator
   *   (Optional) Inject the field mediator.
   */
  public function __construct(\EntityDrupalWrapper $entityWrapper, DrupalHandler $handler = NULL, EntityTranslatableFactory $factory = NULL, FieldMediator $fieldMediator = NULL) {
    // If no Drupal Handler was provided, instantiate it manually.
    if ($handler === NULL) {
      $handler = new DrupalHandler();
    }

    // If no translatable factory was provided, instantiate it manually.
    if ($factory === NULL) {
      $factory = EntityTranslatableFactory::getInstance($handler);
    }

    if ($fieldMediator === NULL) {
      $fieldMediator = new FieldMediator($handler);
    }

    $this->entity = $entityWrapper;
    $this->drupal = $handler;
    $this->translatableFactory = $factory;
    $this->fieldMediator = $fieldMediator;
    $this->entityInfo = $handler->entityGetInfo();
  }

  /**
   * {@inheritdoc}
   */
  public function getIdentifier() {
    return $this->entity->getIdentifier();
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->entity->label();
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceLanguage() {
    // If we've already determined the source language, return it.
    if ($this->sourceLanguage) {
      return $this->sourceLanguage;
    }

    $language = $this->entity->language->value();
    if ($language === DrupalHandler::LANGUAGE_NONE || empty($language)) {
      $language = $this->drupal->languageDefault('language');
    }
    $this->sourceLanguage = $language;
    return $this->sourceLanguage;
  }

  /**
   * {@inheritdoc}
   */
  public function getData() {
    $data = array();
    $fields = $this->getTranslatableFields();

    // Allow modules to alter translatable fields for this entity.
    $this->drupal->entityXliffLoadModuleIncs();
    $this->drupal->alter('entity_xliff_translatable_fields', $fields, $this->entity);

    // Iterate through all fields we're expecting to translate.
    foreach ($fields as $field) {
      if ($fieldData = $this->getFieldFromEntity($this->entity, $field)) {
        $data[$field] = $fieldData;
      }
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   *
   * Adds optional parameter $saveData, mostly used internally.
   */
  public function setData(array $data, $targetLanguage, $saveData = TRUE) {

    // Do not proceed to saving the data if specified.
    if (!$saveData) {
      return;
    }
    // Get the actual target entity we are translating into.
    // Set it as the "root" node (or other entity) in the array of entities
    // to be translated with a depth of 0 and ensure that it gets translated last.
    $targetEntity = $this->getTargetEntity($targetLanguage);
    // First time here the target node may not exist yet and won't have an ID.
    // Create it and add it to the list of entities needing to be saved (after translation).
    if (empty($targetEntity->getIdentifier())) {
      $translatable = $this->translatableFactory->getTranslatable($targetEntity);
      $translatable->initializeTranslation();
      $fieldType = $targetEntity->type();
      $this->drupal->alter('entity_xliff_presave', $targetEntity, $fieldType);
      $translatable->saveWrapper($targetEntity, $targetLanguage);
    }
    $this->setEntitiesNeedsSave($targetEntity);
    // Add translated data passing in the root node as the first wrapper to be unrolled.
    $this->addTranslatedDataRecursive($data, array(), $targetLanguage, $targetEntity);



    // Attempt to initialize translation.
    $this->initializeTranslation();

    // The array of entities needing save must be ordered so that entities are saved in the reverse of
    // their order of reference. The array contains arrays like [depth => n, wrapper => entity].
    // Must save the deepest entities first, otherwise a node will be saved with paragraphs fields (and
    // maybe field collection fields) pointing at the wrong revision.
    // Also, nested paragraphs may point at wrong revisions.

    usort($this->entitiesNeedSave, function ($a, $b) {
      return $b['depth'] - $a['depth'];
    });

    // Save any entities that need saving (this includes the target entity).
    foreach ($this->entitiesNeedSave as $key => $value) {
      $wrapper = $value['wrapper'];
      $translatable = $this->translatableFactory->getTranslatable($wrapper);
      $translatable->initializeTranslation();
      $type = $wrapper->type();
      $this->drupal->alter('entity_xliff_presave', $wrapper, $type);
      $translatable->saveWrapper($wrapper, $targetLanguage);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslatableFields() {
    $fields = array();

    // If the wrapped entity is not translatable, return no fields.
    if (!$this->isTranslatable()) {
      return $fields;
    }
    $list = $this->entity->getPropertyInfo();
    foreach ($this->entity->getPropertyInfo() as $property => $info) {
      if (isset($info['field']) && $info['field']) {
        $fields[] = $property;
      }
    }
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function saveWrapper(\EntityDrupalWrapper $wrapper, $targetLanguage) {
    $wrapper->save();
  }

  /**
   * Unroll an array of translation data and find the matching fields in a
   * supplied wrapper. Depending upon the type of field, either set a value, a
   * structure, or recurse into lists and entities (unrolling entities to set
   * their values as needed).
   *
   * @param $translation
   *   Array of translated values to be set into the translated content.
   * @param $key
   *   Key pointing to the item the values should be applied.
   *   The key can be either be an array containing the keys of a nested array
   *   hierarchy path or a string with '][' or '|' as delimiter.
   * @param $targetLang
   * @param null $parent
   *   Starts as the outer most node wrapper, and then tracks one "level"
   *   above the field being handled.
   * @param null $field
   *   The field being set in this recursive iteration.
   *
   */
  protected function addTranslatedDataRecursive($translation, array $key = array(), $targetLang, $parent = NULL, $field = NULL) {

    // TODO: why do we have this? It looks like maybe it handles a format we no longer use?
    $arrayKeys = Data::ensureArrayKeys($key);

    // The first time in there is no field chosen yet.
    if (empty($field)) {
      // Iterate over the items to be translated ignoring any keys that start with #.
      foreach ($this->drupal->elementChildren($translation) as $item) {
        // Get the next level of wrapper.

        if (is_numeric($item) && isset($wrapper[$item])) {
          // This is a list wrapper.
          $field = &$parent[$item];
        }
        elseif (isset($parent->{$item})) {
          // This is an entity or structure.
          $field = &$parent->{$item};
        }
        else {
          throw new EntityStructureDivergedException('XLIFF serialized structure has diverged from Drupal content structure: ' . $item);
        }
        $this->addTranslatedDataRecursive($translation[$item], array_merge($arrayKeys, array($item)), $targetLang, $parent, $field);
      }
    }
    else {
      // We have a field so check what kind of wrapper it is and act accordingly.
      // Note that the "types" of wrappers are not mutually exclusive
      // (i.e. EntityListWrappers are also EntityStructureWrappers)
      // so the order in which we test them matters.

      // List.
      if (is_a($field, 'EntityListWrapper')) {
        // Iterate over list items.
        foreach ($field->getIterator() as $item => $fieldItem) {
          // $item is the current index so we can keep track of the position in the $translation array.
          // Down a level.
          $this->addTranslatedDataRecursive($translation[$item], array($item), $targetLang, $field, $fieldItem);
        }
        return;
      }

      // Entity
      if (is_a($field, 'EntityDrupalWrapper')) {

        // Increment a counter into our cache of entities that need to be saved.
        $this->entitiesNeedSaveDepth++;



        // If the entity exists and we already have it in static cache, use it.
        if ($this->getEntitiesNeedsSave($field) !== FALSE) {
          $field = $this->getEntitiesNeedsSave($field);
        }
        else {
          if ($translatable = $this->translatableFactory->getTranslatable($field)) {
            $translatable->initializeTranslation();
            $field = $translatable->getTargetEntity($targetLang);
          }
        }

        // If this is a new entity, we need to initialize and save it first.
        if ($field->getIdentifier() === FALSE) {
          $translatable = $this->translatableFactory->getTranslatable($field);
          $translatable->initializeTranslation();
          $fieldType = $field->type();
          $this->drupal->alter('entity_xliff_presave', $field, $fieldType);
          $translatable->saveWrapper($field, $targetLang);
        }

        // If we are not yet supposed to save this entity, then add it to the list now.
        // Do this at the end since we may have gotten a new field in the interim.
        if ($this->getEntitiesNeedsSave($field) === FALSE) {
          $this->setEntitiesNeedsSave($field);
        }

        // Set the reference on the parent wrapper.
        // $arrayKeys is  always an array, but at this point they should only contain one value.
        $reference = array_pop($arrayKeys);
        if (is_numeric($reference)) {
          // The parent is a list field.
          $values = $parent->raw();
          $values[$reference] = $field->getIdentifier();
          $parent->set($values);
        }
        else {
          // The parent is single cardinality.
          $parent->{$reference}->set($field->getIdentifier());
        }

        // Down a level. Do not pass a new field since we need to recurse into the entities values.
        $this->addTranslatedDataRecursive($translation, array(), $targetLang, $field);

        return;
      }

      // Structured Field.
      if (is_a($field, 'EntityStructureWrapper')) {
        $this->setStructuredFieldValue($field, $translation);
        return;
      }

      // Plain value, set it!
      if (is_a($field, 'EntityValueWrapper')) {
        $this->setFieldValue($field, $translation);
        return;
      }
    }


  }

  /**
   * @param $field
   * @param $translation
   *
   * @return bool
   */
  protected function setFieldValue($field, $translation) {
    if (!is_array($translation) || !isset($translation['#text'])) {
      // Something has gone wrong since we should get an array of field "parts" and their values.
      return FALSE;
    }

    $new_value = html_entity_decode(trim($translation['#text']), ENT_HTML5, 'utf-8');
    $field->set($new_value);
    return TRUE;
  }

  /**
   * @param $field
   * @param $translation
   *
   * @return bool
   */
  protected function setStructuredFieldValue($field, $translation) {
    if (!is_array($translation)) {
      // Something has gone wrong since we should get an array of field "parts" and their values.
      return FALSE;
    }
    // Get the existing values so that anything not set will copy over from the source.
    // TODO: Should this test against the field info array to make sure the structure element exists?
    $new_value = $field->value();
    foreach ($translation as $key => $value) {
      if(isset($value['#text'])){
        $new_value[$key] = html_entity_decode(trim($value['#text']), ENT_HTML5, 'utf-8');
      }
    }
    $field->set($new_value);
    return TRUE;
  }

  /**
   * @param \EntityDrupalWrapper $wrapper
   * @param string $field
   * @param int $delta
   *
   * @return array
   */
  public function getFieldFromEntity(\EntityDrupalWrapper $wrapper, $field, $delta = NULL) {
    $response = array();
    $fieldWrapper = $delta !== NULL ? $wrapper->{$field}[$delta] : $wrapper->{$field};
    $fieldInfo = $fieldWrapper->info();
    $type = isset($fieldInfo['type']) ? $fieldInfo['type'] : 'text';

    // Check for getters against known types.
    if ($handler = $this->fieldMediator->getInstance($fieldWrapper)) {
      if ($text = $handler->getValue($fieldWrapper)) {
        if (is_array($text)) {
          $response = $text;
        }
        else {
          $response['#label'] = $fieldInfo['label'];
          $response['#text'] = $text;
        }
      }
    }
    // If this is an entity reference, restart the process recursively with the
    // referenced entity as the starting point.
    elseif (isset($this->entityInfo[$type])) {
      $response += $this->getEntityFromEntity($fieldWrapper);
    }
    // If this is a list, call ourselves recursively for each item.
    elseif (preg_match('/list<(.*?)>/', $type, $matches)) {
      foreach ($fieldWrapper->getIterator() as $delta => $subFieldWrapper) {
        if ($text = $this->getFieldFromEntity($wrapper, $field, $delta)) {
          $response[$delta] = $text;
        }
      }
    }
    else {
      $this->drupal->watchdog('entity xliff', 'Could not pull translatable data. Unknown field type %type.', array(
        '%type' => $type,
      ), DrupalHandler::WATCHDOG_WARNING);
    }

    return $response;
  }

  /**
   * @param \EntityDrupalWrapper $wrapper
   *
   * @return array
   */
  protected function getEntityFromEntity(\EntityDrupalWrapper $wrapper) {
    // Ensure that this wrapper represents real data, not just a placeholder
    // that has no data. Also make sure we know how to translate thie entity.
    if ($wrapper->getIdentifier() && $translatable = $this->translatableFactory->getTranslatable($wrapper)) {
      return $translatable->getData();
    }
    else {
      return array();
    }
  }


  /**
   * Pull an entity out of the cache or return FALSE if not found.
   *
   * @param $wrapper
   *
   * @return bool|null
   */
  protected function getEntitiesNeedsSave($wrapper) {
    $targetId = $wrapper->getIdentifier();
    $targetType = $wrapper->type();
    $needsSaveKey = $targetType . ':' . $targetId;
    // If the entity is already in the list then it is OK to update it so each field gets translated
    // and added, but do not change its depth since we want to maintain the order in which they are first added.
    if (isset($this->entitiesNeedSave[$needsSaveKey])) {
      return $this->entitiesNeedSave[$needsSaveKey]['wrapper'];
    }
    return FALSE;
  }

  /**
   * Push an entity into the array of entitites needing to be saved.
   * Use the current depth if it is a new entry.
   * If the entity is already in the list, it will be "refreshed" but its depth
   * will not change.
   *
   * @param $wrapper
   */
  protected function setEntitiesNeedsSave($wrapper) {
    $targetId = $wrapper->getIdentifier();
    $targetType = $wrapper->type();
    $needsSaveKey = $targetType . ':' . $targetId;
    // If the entity is already in the list then it is OK to update it so each field gets translated
    // and added, but do not change its depth since we want to maintain the order in which they are first added.
    if (isset($this->entitiesNeedSave[$needsSaveKey])) {
      $this->entitiesNeedSaveDepth = $this->entitiesNeedSave[$needsSaveKey]['depth'];
    }
    // Mark this entity as needing saved.
    // Create array ordered by # of parents so we can save them in reverse order.
    $this->entitiesNeedSave[$targetType . ':' . $targetId] = array(
      'depth' => $this->entitiesNeedSaveDepth,
      'wrapper' => $wrapper,
    );
  }

  /**
   * Returns a given Entity wrapper's parent (if one exists).
   *
   * @param \EntityMetadataWrapper $child
   *   The child Entity Wrapper to check.
   *
   * @return \EntityDrupalWrapper|bool
   *   Returns the given entity's parent if one exists. FALSE if it has no
   *   parent.
   */
  public function getParent(\EntityMetadataWrapper $child) {
    $fieldInfo = $child->info();
    if (isset($fieldInfo['parent'])) {
      $parent = $fieldInfo['parent'];
      // It's possible the parent is a list wrapper. If so, find ITS parent.
      // Do so recursively 'til we get to a DrupalWrapper.
      if (!is_a($parent, 'EntityDrupalWrapper')) {
        return $this->getParent($parent);
      }
      else {
        return $parent;
      }
    }
    else {
      return FALSE;
    }
  }

  /**
   * Returns the raw entity object given its entity wrapper.
   *
   * @param \EntityDrupalWrapper $wrapper
   *   The Entity wrapper whose raw entity object is desired.
   *
   * @return mixed
   *   The raw entity object.
   *
   * @throws EntityDataGoneAwayException
   */
  public function getRawEntity(\EntityDrupalWrapper $wrapper) {
    $raw = $wrapper->raw();
    if (!is_object($raw)) {
      $entities = $this->drupal->entityLoad($wrapper->type(), array((int) $raw));
      $raw = reset($entities);
    }

    // If we still don't have an object to serve back, something is wrong.
    if (!is_object($raw)) {
      throw new EntityDataGoneAwayException('Underlying entity lost during processing.');
    }

    return $raw;
  }

}
