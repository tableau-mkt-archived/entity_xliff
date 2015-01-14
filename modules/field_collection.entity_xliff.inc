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
