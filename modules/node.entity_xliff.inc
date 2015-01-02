<?php

/**
 * @file
 * Hooks implemented on behalf of the Node module to integrate with
 * Entity Xliff.
 */

if (!function_exists('node_entity_xliff_translatable_info')) {

  /**
   * Implements hook_entity_xliff_translatable_info().
   */
  function node_entity_xliff_translatable_info() {
    return array(
      'node' => array(
        'class' => 'EntityXliff\Drupal\Translatable\NodeTranslatable',
      ),
    );
  }

}
