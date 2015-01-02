<?php

/**
 * @file
 * Defines a mediator between the Drupal entity subsystem and the Entity XLIFF
 * translatable system.
 */

namespace EntityXliff\Drupal\Mediator;

use EntityXliff\Drupal\Utils\DrupalHandler;


class Entity {

  /**
   * @var DrupalHandler
   */
  protected $drupal;

  /**
   * @var array
   */
  protected $classMap = array();

  /**
   * @param DrupalHandler $handler
   *   (optional) An instance of the DrupalHandler class.
   */
  public function __construct(DrupalHandler $handler = NULL) {
    // If no handler was provided, instantiate one ourselves.
    if ($handler === NULL) {
      $handler = new DrupalHandler();
    }

    $this->drupal = $handler;
    $this->buildMap();
  }

  /**
   * Builds an internal map of entity types to translatable classes.
   */
  public function buildMap() {
    foreach ($this->drupal->entityGetInfo() as $type => $definition) {
      if (isset($definition['entity xliff translatable class'])) {
        $this->classMap[$type] = $definition['entity xliff translatable class'];
      }
    }
  }

  /**
   * Given an Entity wrapper, returns its corresponding Translatable.
   *
   * @param \EntityDrupalWrapper $wrapper
   *   The Entity wrapper for which a translatable is desired.
   *
   * @return \EggsCereal\Interfaces\TranslatableInterface|null
   *   Returns an instance of the entity's translatable. If no translatable
   *   class is known, NULL is returned.
   */
  public function getTranslatable(\EntityDrupalWrapper $wrapper) {
    if ($translatable = $this->getTranslatableClass($wrapper)) {
      return new $translatable($wrapper);
    }
    else {
      return NULL;
    }
  }

  /**
   * Returns whether or not a given Entity can be translated (more specifically,
   * whether or not an \EggsCereal\Interfaces\TranslatableInterface compatible
   * translatable is known for the given entity type.
   *
   * @param \EntityDrupalWrapper $wrapper
   *   The Entity wrapper for which translatability status is desired.
   *
   * @return bool
   *   TRUE if the given Entity can be translated
   */
  public function canBeTranslated(\EntityDrupalWrapper $wrapper) {
    return isset($this->classMap[$wrapper->type()]);
  }

  /**
   * Returns the class for a given Entity wrapper's corresponding translatable.
   *
   * @param \EntityDrupalWrapper $wrapper
   *   The Entity wrapper for which a translatable class is desired.
   *
   * @return string|bool
   *   The name of the class (suitable for dynamic class instantiation) or FALSE
   *   if the entity type is not known to be translatable.
   */
  public function getTranslatableClass(\EntityDrupalWrapper $wrapper) {
    if ($this->canBeTranslated($wrapper)) {
      return $this->classMap[$wrapper->type()];
    }
    else {
      return FALSE;
    }
  }

}
