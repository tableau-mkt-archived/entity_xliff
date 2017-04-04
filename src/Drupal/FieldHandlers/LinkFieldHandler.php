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

  /**
   * {@inheritdoc}
   */
  public function setValue(\EntityMetadataWrapper $wrapper, $value, $context = array()) {
    $new_value = $wrapper->value();
    $parents = $context['#parents'];

    // $parents contains the structured hierarchy of the link field--e.g. the url and
    // title attributes.
    if (!empty($parents) && is_string($parents[0])) {
      switch($parents[0]) {
        case 'url':
          $new_value['url'] = html_entity_decode($value, ENT_HTML5, 'utf-8');
          break;
        case 'title':
          $new_value['title'] = html_entity_decode($value, ENT_QUOTES, 'utf-8');
          break;
      }
    }

    $wrapper->set($new_value);
  }

}
