<?php

/**
 * @file
 * Hooks implemented on behalf of the Field Collection module to integrate with
 * Entity Xliff.
 */


if (!function_exists('field_collection_entity_xliff_translatable_info')) {

  /**
   * Implements hook_entity_xliff_translatable_info().
   */
  function field_collection_entity_xliff_translatable_info() {
    return array(
      'field_collection_item' => array(
        'class' => 'EntityXliff\Drupal\Translatable\FieldCollectionTranslatable',
      ),
    );
  }

}
