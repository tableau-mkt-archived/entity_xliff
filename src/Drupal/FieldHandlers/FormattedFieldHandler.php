<?php

/**
 * @file
 * Defines an entity xliff field handler for use by fields with format.
 */

namespace EntityXliff\Drupal\FieldHandlers;

use EntityXliff\Drupal\Interfaces\FieldHandlerInterface;


class FormattedFieldHandler implements FieldHandlerInterface {

  /**
   * {@inheritdoc}
   */
  public function getValue(\EntityMetadataWrapper $wrapper) {
    $response = array();

    $value = $wrapper->value();
    $info = $wrapper->info();

    // Check for value text.
    if (isset($value['value']) && !empty($value['value'])) {
      $response['value'] = array(
        '#label' => $info['label'] . ' (value)',
        '#text' => $value['value'],
      );
    }

    // Check for summary text.
    if (isset($value['summary']) && !empty($value['summary'])) {
      $response['summary'] = array(
        '#label' => $info['label'] . ' (summary)',
        '#text' => $value['summary'],
      );
    }

    return $response;
  }
}
