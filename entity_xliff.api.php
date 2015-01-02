<?php

/**
 * @file
 * Hook documentation for the Entity Xliff module.
 *
 * This file contains no working PHP code; it exists to provide additional
 * documentation for doxygen as well as to document hooks in the standard
 * Drupal manner.
 */

/**
 * @defgroup entity_xliff Entity Xliff module integrations.
 *
 * Module integrations with the Entity Xliff module.
 */


/**
 * @defgroup entity_xliff_hooks Entity Xliff's hooks
 * @{
 * Hooks that can be implemented by other modules in order to extend the Entity
 * Xliff module.
 */

/**
 * Return info about entities and the way in which they integrate with the
 * Entity Xliff module.
 *
 * @return array
 *   Returns an associative array of Entity Xliff translatable details, keyed by
 *   entity type. At a bare minimum Entity Xliff translatable info must include
 *   the following:
 *   - class: Class that implements EggsCereal\Interfaces\TranslatableInterface,
 *     describing how entities of this type can be imported/exported. If you
 *     are providing a class, you may wish to study or extend from the
 *     EntityXliff\Drupal\Translatable\EntityTranslatableBase abstract class.
 */
function hook_entity_xliff_translatable_info() {
  $translatables['node'] = array(
    'class' => 'NodeTranslatable',
  );

  // Example showing a namespace'd PHP class.
  $translatables['my_entity'] = array(
    'class' => 'NameSpace\Of\MyEntityTranslatable',
  );

  return $translatables;
}

/**
 * Alter Entity Xliff translatable info before it's applied to an Entity's info.
 *
 * @param array $translatables
 *   An associative array of Entity Xliff translatable details exactly as spec'd
 *   in hook_entity_xliff_translatable_info().
 */
function hook_entity_xliff_translatable_info_alter(&$translatables) {
  $translatables['node']['class'] = 'MyCustomNodeTranslatable';
}

/**
 * @}
 */
