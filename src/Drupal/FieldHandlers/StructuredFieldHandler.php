<?php

/**
 * @file
 * Defines an entity xliff field handler for use by link fields.
 */

namespace EntityXliff\Drupal\FieldHandlers;

use EntityXliff\Drupal\Interfaces\FieldHandlerInterface;


class StructuredFieldHandler implements FieldHandlerInterface {

  /**
   * {@inheritdoc}
   */
  public function getValue(\EntityMetadataWrapper $wrapper) {
    $response = array();
    $field_info = $wrapper->info();
    $field_value = $wrapper->value();

    foreach ($field_info['property info'] as $property => $value) {
      if (isset($field_value[$property]) && !empty($field_value[$property])) {
        $response[$property] = array(
          '#label' => isset($value['label']) ? $value['label'] : '',
          '#text' => $field_value[$property],
        );
      }
    }

    return $response;
  }
}
