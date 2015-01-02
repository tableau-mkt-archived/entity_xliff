<?php

/**
 * @file
 * Base class for making entities translatable.
 */

namespace EntityXliff\Drupal\Translatable;

use EggsCereal\Interfaces\TranslatableInterface;
use EggsCereal\Utils\Data;
use EntityXliff\Drupal\Mediator\EntityMediator;
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
   * Maps getter and setter methods for various field types.
   * @var array
   */
  protected $methodMap = array(
    'text' => array(
      'get' => 'getScalarValueFromEntity',
      'set' => 'setScalarValueFromEntity',
    ),
    'boolean' => array(
      'get' => 'getScalarValueFromEntity',
      'set' => 'setScalarValueFromEntity',
    ),
    'integer' => array(
      'get' => 'getScalarValueFromEntity',
      'set' => 'setScalarValueFromEntity',
    ),
    'uri' => array(
      'get' => 'getScalarValueFromEntity',
      'set' => 'setScalarValueFromEntity',
    ),
    'text_formatted' => array(
      'get' => 'getFormattedValueFromEntity',
      'set' => 'setFormattedValueFromEntity',
    ),
    'field_item_textsummary' => array(
      'get' => 'getFieldItemTextSummaryFromEntity',
      'set' => 'setFieldItemTextSummaryFromEntity',
    ),
    'field_item_link' => array(
      'get' => 'getFieldItemLinkFromEntity',
      'set' => 'setFieldItemLinkFromEntity',
    ),
    'field_item_file' => array(
      'get' => 'getFieldItemFileFromEntity',
      'set' => 'setFieldItemFileFromEntity',
    ),
    'field_item_image' => array(
      'get' => 'getFieldItemImageFromEntity',
      'set' => 'setFieldItemImageFromEntity',
    ),
  );

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
   */
  public function __construct(\EntityDrupalWrapper $entityWrapper, DrupalHandler $handler = NULL, EntityMediator $entityMediator = NULL) {
    // If no Drupal Handler was provided, instantiate it manually.
    if ($handler === NULL) {
      $handler = new DrupalHandler();
    }

    // If no Entity mediator was provided, instantiate it manually.
    if ($entityMediator === NULL) {
      $entityMediator = new EntityMediator($handler);
    }

    $this->entity = $entityWrapper;
    $this->drupal = $handler;
    $this->entityMediator = $entityMediator;
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
  public function getData(\EntityDrupalWrapper $wrapper = NULL) {
    if ($wrapper === NULL) {
      $wrapper = $this->entity;
    }

    $data = array();
    $fields = $this->getTranslatableFields($wrapper);
    $info = $wrapper->getPropertyInfo();

    // Iterate through all fields we're expecting to translate.
    foreach ($fields as $field) {
      $type = isset($info[$field]['type']) ? $info[$field]['type'] : 'text';
      if ($fieldData = $this->getFieldFromEntity($wrapper, $field, $type)) {
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
      $translatableClass = $this->entityMediator->getTranslatableClass($wrapper);

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
   * @param string $type
   * @param mixed $value
   * @return array
   */
  public function getFieldFromEntity(\EntityDrupalWrapper $wrapper, $field, $type = '', $value = NULL) {
    $response = array();
    $info = $wrapper->getPropertyInfo();
    $fieldInfo = $info[$field];

    // Check for getters against known types.
    if (isset($this->methodMap[$type]['get'])) {
      if ($text = $this->{$this->methodMap[$type]['get']}($wrapper, $field, $value)) {
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
      $response += $this->getEntityFromEntity($wrapper, $type, $field, $value);
    }
    // If this is a list, call ourselves recursively for each item.
    elseif (preg_match('/list<(.*?)>/', $type, $matches)) {
      foreach ($wrapper->{$field}->getIterator() as $delta => $fieldWrapper) {
        if ($fieldWrapperValue = $fieldWrapper->value()) {
          if ($text = $this->getFieldFromEntity($wrapper, $field, $matches[1], $fieldWrapperValue)) {
            $response[$delta] = $text;
          }
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
   * @param \EntityMetadataWrapper $wrapper
   * @param string $field
   * @param string $value
   * @return string
   */
  protected function getScalarValueFromEntity(\EntityMetadataWrapper $wrapper, $field, $value = NULL) {
    return $value ?: $wrapper->{$field}->value();
  }

  /**
   * @param \EntityMetadataWrapper $wrapper
   * @param $value
   */
  protected function setScalarValueFromEntity(\EntityMetadataWrapper $wrapper, $value) {
    $wrapper->set($value);
  }

  /**
   * @param \EntityMetadataWrapper $wrapper
   * @param string $field
   * @param array $value
   * @return mixed
   */
  protected function getFormattedValueFromEntity(\EntityMetadataWrapper $wrapper, $field, $value = NULL) {
    if ($value) {
      return $value['value'];
    }
    else {
      $fieldValue = $wrapper->{$field}->value();
      return $fieldValue['value'];
    }
  }

  /**
   * @param \EntityMetadataWrapper $wrapper
   * @param $value
   */
  protected function setFormattedValueFromEntity(\EntityMetadataWrapper $wrapper, $value) {
    $newValue = $wrapper->value();
    $newValue['value'] = $value;
    $wrapper->set($newValue);
  }

  /**
   * @param \EntityMetadataWrapper $wrapper
   * @param $field
   * @param null $value
   * @return array
   */
  protected function getFieldItemTextSummaryFromEntity(\EntityMetadataWrapper $wrapper, $field, $value = NULL) {
    $response = array();

    if ($value) {
      return $value;
    }
    else {
      $value = $wrapper->{$field}->value();
      $info = $wrapper->{$field}->info();

      // Check for value text.
      if (isset($value['value']) && !empty($value['value'])) {
        $response['value'] = array(
          '#label' => $info['label'] . ' (value)',
          '#text' => $value['value'],
        );
      }

      // Check for summary text.
      if (isset($value['summary']) && !empty($value['summary'])) {
        $response['summary'] = array(
          '#label' => $info['label'] . ' (summary)',
          '#text' => $value['summary'],
        );
      }

      return $response;
    }
  }

  /**
   * @param \EntityMetadataWrapper $wrapper
   * @param $value
   */
  protected function setFieldItemTextSummaryFromEntity(\EntityMetadataWrapper $wrapper, $value) {
    $newValue = $wrapper->value();

    if (isset($value['value'])) {
      $newValue['value'] = $value['value'];
    }
    if (isset($value['summary'])) {
      $newValue['summary'] = $value['summary'];
    }

    $wrapper->set($newValue);
  }

  /**
   * @param \EntityMetadataWrapper $wrapper
   * @param string $field
   * @param array $value
   */
  protected function getFieldItemLinkFromEntity(\EntityMetadataWrapper $wrapper, $field, $value = NULL) {
    if ($value) {
      return $value['title'];
    }
    else {
      $fieldValue = $wrapper->{$field}->value();
      return $fieldValue['title'];
    }
  }

  /**
   * @param \EntityMetadataWrapper $wrapper
   * @param $value
   */
  protected function setFieldItemLinkFromEntity(\EntityMetadataWrapper $wrapper, $value) {
    $newValue = $wrapper->value();
    $newValue['title'] = $value;
    $wrapper->set($newValue);
  }

  /**
   * @param \EntityMetadataWrapper $wrapper
   * @param $field
   * @param null $value
   * @return array
   */
  protected function getFieldItemFileFromEntity(\EntityMetadataWrapper $wrapper, $field, $value = NULL) {
    return array();
  }

  /**
   * @param \EntityMetadataWrapper $wrapper
   * @param null $value
   */
  protected function setFieldItemFileFromEntity(\EntityMetadataWrapper $wrapper, $value = NULL) {}

  /**
   * @param \EntityMetadataWrapper $wrapper
   * @param $field
   * @param null $value
   * @return array
   */
  protected function getFieldItemImageFromEntity(\EntityMetadataWrapper $wrapper, $field, $value = NULL) {
    $response = array();

    if ($value) {
      return $value;
    }
    else {
      $value = $wrapper->{$field}->value();

      // Check for alt text.
      if (isset($value['alt']) && !empty($value['alt'])) {
        $response['alt'] = array(
          '#label' => 'Alternate text',
          '#text' => $value['alt'],
        );
      }

      // Check for title text.
      if (isset($value['title']) && !empty($value['title'])) {
        $response['title'] = array(
          '#label' => 'Title text',
          '#text' => $value['title'],
        );
      }

      return $response;
    }
  }

  /**
   * @param \EntityMetadataWrapper $wrapper
   * @param $value
   */
  protected function setFieldItemImageFromEntity(\EntityMetadataWrapper $wrapper, $value) {
    $newValue = $wrapper->value();

    if (isset($value['alt'])) {
      $newValue['alt'] = $value['alt'];
    }
    if (isset($value['title'])) {
      $newValue['title'] = $value['title'];
    }

    $wrapper->set($newValue);
  }

  /**
   * @param \EntityDrupalWrapper $wrapper
   * @param $type
   * @param $field
   * @param null $value
   * @return array
   */
  protected function getEntityFromEntity(\EntityDrupalWrapper $wrapper, $type, $field, $value = NULL) {
    if ($value) {
      $entity = $this->drupal->entityMetadataWrapper($type, $value);
      return $this->getData($entity);
    }
    else {
      if ($identifier = $wrapper->{$field}->getIdentifier()) {
        $entity = $this->drupal->entityMetadataWrapper($type, $identifier);
        return $this->getData($entity);
      }
      else {
        return array();
      }
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
    if (isset($this->methodMap[$type]['set'])) {
      // If the parent IS the targetEntity, just set the value as calculated.
      if ($this->isTheSameAs($parent, $targetLang)) {
        $this->{$this->methodMap[$type]['set']}($ref, $value);

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
    if ($translatableClass = $this->entityMediator->getTranslatableClass($parent)) {
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
