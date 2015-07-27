<?php

/**
 * @file
 * Hooks implemented on behalf of the Field Collection module to integrate with
 * Entity Xliff.
 */

use EntityXliff\Drupal\Factories\EntityTranslatableFactory as Paradigm;

if (!function_exists('field_collection_entity_xliff_translatable_info')) {

  /**
   * Implements hook_entity_xliff_translatable_info().
   */
  function field_collection_entity_xliff_translatable_info() {
    return array(
      'field_collection_item' => array(
        'classes' => array(
          Paradigm::CONTENT => 'EntityXliff\Drupal\Translatable\Content\FieldCollectionTranslatable',
        ),
      ),
    );
  }

}

if (!function_exists('field_collection_entity_xliff_translatable_fields_alter')) {

  /**
   * Implements hook_entity_xliff_translatable_fields_alter().
   *
   * Ensures that field collection fields always appear last in XLIFF exports.
   * This works around an issue where field collections rely on host entity
   * translation initialization.
   */
  function field_collection_entity_xliff_translatable_fields_alter(&$fields, $wrapper) {
    $info = $wrapper->getPropertyInfo();
    $needle = 'field_collection_item';

    for ($n = 0; $n < count($fields); $n++) {
      $field = $fields[$n];
      // If this field is translatable and it's a field collection, move it to
      // the back of the translatable list.
      if (isset($info[$field]) && strpos($info[$field]['type'], $needle)) {
        unset($fields[$n]);
        $fields[] = $field;
      }
    }
  }

}
