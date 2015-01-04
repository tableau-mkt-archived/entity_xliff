<?php

/**
 * @file
 * Base class for making entities translatable.
 */

namespace EntityXliff\Drupal\Translatable;

use EggsCereal\Interfaces\TranslatableInterface;
use EggsCereal\Utils\Data;
use EntityXliff\Drupal\Mediator\EntityMediator;
use EntityXliff\Drupal\Mediator\FieldMediator;
use EntityXliff\Drupal\Utils\DrupalHandler;


/**
 * Class EntityTranslatableBase
 */
abstract class EntityTranslatableBase implements TranslatableInterface  {

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
   * @var EntityMediator
   */
  protected $entityMediator;

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
   * @var TranslatableInterface[]
   */
  protected $translatables = array();

  /**
   * Creates a Translatable from an Entity wrapper.
   *
   * @param \EntityDrupalWrapper $entityWrapper
   *   Metadata wrapper for the entity we wish to translate.
   *
   * @param DrupalHandler $handler
   *   (Optional) Inject the utility Drupal handler.
   *
   * @param EntityMediator $entityMediator
   *   (Optional) Inject the entity mediator.
   *
   * @param FieldMediator $fieldMediator
   *   (Optional) Inject the field mediator.
   */
  public function __construct(\EntityDrupalWrapper $entityWrapper, DrupalHandler $handler = NULL, EntityMediator $entityMediator = NULL, FieldMediator $fieldMediator = NULL) {
    // If no Drupal Handler was provided, instantiate it manually.
    if ($handler === NULL) {
      $handler = new DrupalHandler();
    }

    // If no Entity mediator was provided, instantiate it manually.
    if ($entityMediator === NULL) {
      $entityMediator = new EntityMediator($handler);
    }

    if ($fieldMediator === NULL) {
      $fieldMediator = new FieldMediator($handler);
    }

    $this->entity = $entityWrapper;
    $this->drupal = $handler;
    $this->entityMediator = $entityMediator;
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
  public function getData() {
    $data = array();
    $fields = $this->getTranslatableFields($this->entity);

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
    // Add translated data.
    $this->addTranslatedDataRecursive($data, array(), $targetLanguage);

    // Do not proceed to saving the data if specified.
    if (!$saveData) {
      return;
    }

    // Save any entities that need saving (this includes the target entity).
    foreach ($this->entitiesNeedSave as $key => $wrapper) {
      $translatableClass = $this->entityMediator->getClass($wrapper);

      // If a translatable class provides a static save method, call it.
      if ($translatableClass && method_exists($translatableClass, 'saveWrapper')) {
        call_user_func_array($translatableClass . '::saveWrapper', array($wrapper));
      }
      // Otherwise, call \EntityDrupalWrapper::save().
      else {
        $wrapper->save();
      }
    }
  }

  /**
   * Sets the target entity when data is being imported.
   *
   * Likely, you'll want to override this method with one of your own, depending
   * on the entity.
   *
   * @param string $targetLanguage
   *   The language code of the intended target language.
   *
   * @return \EntityDrupalWrapper
   *   An entity metadata wrapper representing the target.
   */
  public function getTargetEntity($targetLanguage) {
    if (!isset($this->targetEntities[$targetLanguage]) || empty($this->targetEntities[$targetLanguage])) {
      $target = clone $this->entity;
      $target->language($targetLanguage);
      $this->targetEntities[$targetLanguage] = $target;
    }
    return $this->targetEntities[$targetLanguage];
  }

  /**
   * @param $translation
   * @param array $key
   * @param string $targetLang
   */
  protected function addTranslatedDataRecursive($translation, array $key = array(), $targetLang) {
    if (isset($translation['#text'])) {
      $values = array(
        '#translation' => $translation,
      );
      $this->setItem($key, $values, $targetLang);
      return;
    }
    foreach ($this->drupal->elementChildren($translation) as $item) {
      $this->addTranslatedDataRecursive($translation[$item], array_merge($key, array($item)), $targetLang);
    }
  }

  /**
   * Updates the values for a specific substructure in the data array.
   *
   * The values are either set or updated but never deleted.
   *
   * @param $key
   *   Key pointing to the item the values should be applied.
   *   The key can be either be an array containing the keys of a nested array
   *   hierarchy path or a string with '][' or '|' as delimiter.
   * @param $values
   *   Nested array of values to set.
   * @param string $targetLang
   *   The target language of the item being set.
   */
  public function setItem($key, $values = array(), $targetLang) {
    foreach ($values as $index => $value) {
      // In order to preserve existing values, we can not apply the values array
      // at once. We need to apply each containing value on its own.
      // If $value is an array we need to advance the hierarchy level.
      if (is_array($value)) {
        $this->setItem(array_merge(Data::ensureArrayKeys($key), array($index)), $value, $targetLang);
      }
      // Apply the value.
      else {
        // Do not bother setting empty values.
        $trimmed = trim($value);
        if (!empty($trimmed)) {
          // Get the list of relevant array keys via the xliff serializer.
          $arrayKeys = Data::ensureArrayKeys($key);
          array_pop($arrayKeys);

          // Set the value via a method inspired by drupal's nested array setter.
          $this->entitySetNestedValue($this->getTargetEntity($targetLang), $arrayKeys, $value, $targetLang);
        }
      }
    }
  }


  /**
   * @param \EntityDrupalWrapper $wrapper
   * @return array
   */
  public function getTranslatableFields(\EntityDrupalWrapper $wrapper = NULL) {
    $fields = array();

    if ($wrapper === NULL) {
      $wrapper = $this->entity;
    }

    foreach ($wrapper->getPropertyInfo() as $property => $info) {
      if (isset($info['field']) && $info['field']) {
        $fields[] = $property;
      }
    }
    return $fields;
  }

  /**
   * @param \EntityDrupalWrapper $wrapper
   * @param string $field
   * @param int $delta
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
    elseif(isset($this->entityInfo[$type])) {
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
   * @return array
   */
  protected function getEntityFromEntity(\EntityDrupalWrapper $wrapper) {
    // Ensure that this wrapper represents real data, not just a placeholder
    // that has no data. Also make sure we know how to translate thie entity.
    if ($wrapper->getIdentifier() && $translatable = $this->entityMediator->getInstance($wrapper)) {
      return $translatable->getData();
    }
    else {
      return array();
    }
  }

  /**
   * @param \EntityDrupalWrapper $wrapper
   * @param array $parents
   * @param $value
   * @param $targetLang
   */
  protected function entitySetNestedValue(\EntityDrupalWrapper $wrapper, array $parents, $value, $targetLang) {
    $ref = &$wrapper;
    $field = '';
    $delta = NULL;

    foreach ($parents as $parent) {
      if (is_numeric($parent)) {
        $ref = &$ref[$parent];
      }
      else {
        $ref = &$ref->{$parent};
        $field = $parent;
      }
    }

    // Get the field type for this field.
    $fieldInfo = $ref->info();
    $type = isset($fieldInfo['type']) ? $fieldInfo['type'] : 'text';

    // Save off the parent entity for saving.
    $parent = $this->getParent($ref);
    if ($parent === FALSE) {
      $parent = $wrapper;
    }

    // Set the field value according to the type of data.
    if ($handler = $this->fieldMediator->getInstance($ref)) {
      // If the parent IS the targetEntity, just set the value as calculated.
      if ($this->isTheSameAs($parent, $targetLang)) {
        $handler->setValue($ref, $value);

        // This may be a brand new entity. If so, save it immediately, then
        // re-queue it for one more save once all values have been appended.
        $targetId = $wrapper->getIdentifier();
        $targetType = $wrapper->type();
        if ($targetId === FALSE) {
          $wrapper->save();
          $targetId = $wrapper->getIdentifier();
        }
        $this->entitiesNeedSave[$targetType . ':' . $targetId] = $wrapper;
      }
      // Otherwise, we're in an entity reference and we need to handle it on the
      // entity's target.
      else {
        $this->entitySetReferencedEntity($parent, $field, $parents, $value, $targetLang);
      }
    }
  }

  /**
   * @param \EntityDrupalWrapper $parent
   * @param string $field
   * @param array $parents
   * @param $value
   * @param $targetLang
   */
  protected function entitySetReferencedEntity(\EntityDrupalWrapper $parent, $field, array $parents, $value, $targetLang) {
    $parentType = $parent->type();
    $needsSaveKey = $parent->type() . ':' . $parent->getIdentifier();
    if ($translatableClass = $this->entityMediator->getClass($parent)) {
      // Load the translatable for this referenced entity.
      if (isset($this->entitiesNeedSave[$needsSaveKey])) {
        $parentWrapper = $this->entitiesNeedSave[$needsSaveKey];
      }
      else {
        $parentWrapper = clone $parent;
      }

      // Attempt to load the translatable from static cache.
      if (isset($this->translatables[$needsSaveKey])) {
        $parentTranslatable = $this->translatables[$needsSaveKey];
      }
      else {
        $parentTranslatable = new $translatableClass($parentWrapper);
      }

      // Recreate the $data array for this referenced entity.
      $fieldKey = array_search($field, $parents);
      $parentDataParents = array_slice($parents, $fieldKey);
      $parentContext = array_slice($parents, 0, $fieldKey);
      $delta = FALSE;
      if (is_numeric($parentDataParents[0])) {
        $delta = array_shift($parentDataParents);
      }
      elseif (isset($parentContext[1]) && is_numeric($parentContext[1])) {
        $delta = $parentContext[1];
      }
      $parentDataParents[] = '#text';
      $parentData = array();
      $this->drupal->arraySetNestedValue($parentData, $parentDataParents, $value);

      // Set the data.
      $parentTranslatable->setData($parentData, $targetLang, FALSE);

      // Set the wrapper with the new data for save.
      $targetWrapper = $parentTranslatable->getTargetEntity($targetLang);
      $targetId = $targetWrapper->getIdentifier();
      $this->entitiesNeedSave[$needsSaveKey] = $targetWrapper;

      // If the target ID we pulled is FALSE, we have to save the target entity
      // first. It's brand new. Then add the correct entity to the save queue.
      if (empty($targetId)) {
        $targetWrapper->save();
        $targetId = $targetWrapper->getIdentifier();

        // Correct the wrapper that needs to be saved.
        unset($this->entitiesNeedSave[$needsSaveKey]);
        $needsSaveKey = $targetWrapper->type() . ':' . $targetId;
        $this->entitiesNeedSave[$needsSaveKey] = $targetWrapper;
      }

      // Save off the translatable.
      $this->translatables[$needsSaveKey] = $parentTranslatable;

      // Update the reference to use the (potentially newly created) entity.
      $grandParent = $this->getParent($parent);
      if ($grandParent === FALSE) {
        $grandParent = $parent;
      }

      $grandParentSaveKey = $grandParent->type() . ':' . $grandParent->getIdentifier();
      if ($delta !== FALSE) {
        $grandParent->{$parentContext[0]}[$delta]->set($targetWrapper);
        $this->entitiesNeedSave[$grandParentSaveKey] = $grandParent;
      }
      else {
        $grandParent->{$parentContext[0]}->set($targetWrapper);
        $this->entitiesNeedSave[$grandParentSaveKey] = $grandParent;
      }
    }
    else {
      // Otherwise, register the unknown entity.
      $this->drupal->watchdog('entity xliff', 'Could not update entity reference. Unknown entity type %type.', array(
        '%type' => $parentType,
      ), DrupalHandler::WATCHDOG_WARNING);
    }
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
   * @param \EntityDrupalWrapper $wrapper
   *   The entity wrapper to compare.
   *
   * @param string $targetLang
   *   (Optional) If provided, the target entity will be pulled via the
   * getTargetEntity method.
   *
   * @return bool
   *   TRUE if the given entity is the same as the primary entity represented by
   *   this translatable. FALSE otherwise.
   */
  protected function isTheSameAs(\EntityDrupalWrapper $wrapper, $targetLang = NULL) {
    $comparator = $targetLang ? $this->getTargetEntity($targetLang) : $this->entity;
    return $comparator->getIdentifier() === $wrapper->getIdentifier() && $comparator->type() === $wrapper->type();
  }

}
