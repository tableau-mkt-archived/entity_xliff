<?php

/**
 * @file
 * Defines an entity xliff field handler for use by link fields.
 */

namespace EntityXliff\Drupal\FieldHandlers;

use EntityXliff\Drupal\Interfaces\FieldHandlerInterface;


class LinkFieldHandler implements FieldHandlerInterface {

  /**
   * {@inheritdoc}
   */
  public function getValue(\EntityMetadataWrapper $wrapper) {
    $response = array();

    $field_value = $wrapper->value();
    // Check for link url.
    if (isset($field_value['url']) && !empty($field_value['url'])) {
      $response['url'] = array(
        '#label' => 'Link URL',
        '#text' => $field_value['url'],
      );
    }
    // Check for title text.
    if (isset($field_value['title']) && !empty($field_value['title'])) {
      $response['title'] = array(
        '#label' => 'Title text',
        '#text' => $field_value['title'],
      );
    }

    return $response;
  }
}
