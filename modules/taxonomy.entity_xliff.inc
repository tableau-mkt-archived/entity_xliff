<?php

/**
 * @file
 * Hooks implemented on behalf of the Taxonomy module to integrate with
 * Entity Xliff.
 */

use EntityXliff\Drupal\Factories\EntityTranslatableFactory as Paradigm;

if (!function_exists('taxonomy_entity_xliff_translatable_info')) {

  /**
   * Implements hook_entity_xliff_translatable_info().
   */
  function taxonomy_entity_xliff_translatable_info() {
    return array(
      'taxonomy_term' => array(
        'classes' => array(
          Paradigm::ENTITYFIELD => 'EntityXliff\Drupal\Translatable\Entity\TaxonomyTermTranslatable',
        ),
      ),
    );
  }

}
