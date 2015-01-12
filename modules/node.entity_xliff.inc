<?php

/**
 * @file
 * Hooks implemented on behalf of the Node module to integrate with
 * Entity Xliff.
 */

use EntityXliff\Drupal\Factories\EntityTranslatableFactory as Paradigm;

if (!function_exists('node_entity_xliff_translatable_info')) {

  /**
   * Implements hook_entity_xliff_translatable_info().
   */
  function node_entity_xliff_translatable_info() {
    return array(
      'node' => array(
        'classes' => array(
          Paradigm::CONTENT => 'EntityXliff\Drupal\Translatable\Content\NodeTranslatable',
          Paradigm::ENTITYFIELD => 'EntityXliff\Drupal\Translatable\Entity\NodeTranslatable',
        ),
      ),
    );
  }

}
