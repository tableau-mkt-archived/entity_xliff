<?php

/**
 * @file
 *
 */

namespace EntityXliff\Drupal\Translatable;

/**
 * Class FieldCollectionTranslatable
 * @package EntityXliff\Drupal\Translatable
 */
class FieldCollectionTranslatable extends EntityTranslatableBase {

  /**
   * {@inheritdoc}
   *
   * Field collections support both entity field translation and content
   * translation workflows... We handle that here.
   */
  public function getTargetEntity($targetLanguage) {
    if (!isset($this->targetEntities[$targetLanguage]) || empty($this->targetEntities[$targetLanguage])) {
      // Handling for content translation. Entity field translation should be
      // taken care of by the parent. @todo Need to be able to determine this
      // based on the host entity's paradigm...
      if (!$this->drupal->moduleExists('entity_translation')) {
        $target = $this->getRawEntity($this->entity);

        // Pull the parent/host entity.
        if ($rawHost = $this->getHostEntity($target)) {
          $host = $this->drupal->entityMetadataWrapper($target->hostEntityType(), $rawHost);
        }
        else {
          $host = $this->getParent($this->entity);
          $rawHost = $this->getRawEntity($host);
        }

        // If the language of the host does not match the specified target
        // language, then we need to create a new field collection. Otherwise,
        // we're just updating the existing field collection.
        if ($host->language->value() !== $targetLanguage) {
          unset($target->item_id, $target->revision_id);
          $target->is_new = TRUE;
          $target->setHostEntity($host->type(), $rawHost);
        }
        else {
          $target->is_new = TRUE;
          $target->setHostEntity($host->type(), $rawHost);
          $target->is_new = FALSE;
          $target->is_new_revision = FALSE;
          $target->default_revision = TRUE;
        }

        $this->targetEntities[$targetLanguage] = $this->drupal->entityMetadataWrapper('field_collection_item', $target);
      }
      else {
        $this->targetEntities[$targetLanguage] = parent::getTargetEntity($targetLanguage);
      }
    }

    return $this->targetEntities[$targetLanguage];
  }

  /**
   * {@inheritdoc}
   */
  public function isTranslatable() {
    // @todo Determine best way to do this based on host entity paradigm...
    return TRUE;
  }

  /**
   * {@inheritdoc}
   *
   * Do not save the host entity; that is taken care of elsewhere.
   */
  public static function saveWrapper(\EntityDrupalWrapper $wrapper) {
    $wrapper->raw()->save(TRUE);
  }

  /**
   * Returns a given field collection item entity's host entity.
   * @param \FieldCollectionItemEntity $fieldCollection
   * @return bool|mixed|object
   */
  public function getHostEntity(\FieldCollectionItemEntity $fieldCollection) {
    // Pull the parent/host entity with the maximum user privilege possible.
    $tempUser = clone $GLOBALS['user'];
    $this->drupal->saveSession(FALSE);
    $GLOBALS['user'] = $this->drupal->userLoad(1);
    $rawHost = $fieldCollection->hostEntity();
    $GLOBALS['user'] = $tempUser;
    $this->drupal->saveSession(TRUE);
    return $rawHost;
  }

}
