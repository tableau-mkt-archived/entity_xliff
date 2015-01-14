<?php

/**
 * @file
 * Hooks implemented on behalf of the Comment module to integrate with
 * Entity Xliff.
 */

use EntityXliff\Drupal\Factories\EntityTranslatableFactory as Paradigm;

if (!function_exists('comment_entity_xliff_translatable_info')) {

  /**
   * Implements hook_entity_xliff_translatable_info().
   */
  function comment_entity_xliff_translatable_info() {
    return array(
      'comment' => array(
        'classes' => array(
          Paradigm::ENTITYFIELD => 'EntityXliff\Drupal\Translatable\Entity\CommentTranslatable',
        ),
      ),
    );
  }

}
