<?php

/**
 * @file
 * Base class for making entities translatable using Entity Translation module.
 */

namespace EntityXliff\Drupal\Translatable;

use EntityXliff\Drupal\Utils\DrupalHandler;


/**
 * Class EntityFieldTranslatableBase
 */
abstract class EntityFieldTranslatableBase extends EntityTranslatableBase {

  /**
   * {@inheritdoc}
   */
  public function isTranslatable() {
    $type = $this->entity->type();
    $bundle = $this->entity->getBundle();
    return $this->drupal->entityTranslationEnabled($type, $bundle);
  }

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function initializeTranslation() {
    if ($this->entity->language->value() === DrupalHandler::LANGUAGE_NONE) {
      $this->entity->language->set('en');
      $this->entity->save();
    }
  }

}
