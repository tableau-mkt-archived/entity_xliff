<?php

/**
 * @file
 * Performs test set up tasks to get Drupal in a state where Behat can do its
 * thing without much hassle.
 */


// Create a new language.
try {
  locale_add_language('fr', 'French', 'Français', LANGUAGE_LTR);
}
catch (PDOException $e) {
  echo "Language already installed.\n";
}

// Enable path prefix-based language negotiation.
$negotiation = array(
  'locale-url' => array(
    'callbacks' => array(
      'language' => 'locale_language_from_url',
      'switcher' => 'locale_language_switcher_url',
      'url_rewrite' => 'locale_language_url_rewrite_url',
    ),
    'file' => 'includes/locale.inc',
  ),
  'language-default' => array(
    'callbacks' => array(
      'language' => 'language_from_default',
    ),
  ),
);
variable_set('language_negotiation_language', $negotiation);
variable_set('language_negotiation_language_content', $negotiation);

// Enable translation for the page content type.
variable_set('language_content_type_page', TRANSLATION_ENABLED);

// Add relevant fields to the page content type:
add_long_text_field('node', 'page');
add_link_field('node', 'page');
add_image_field();
add_text_field_with_cardinality();
add_field_collection_field();

// Enable entity field translation for appropriate entities.
$etypes = variable_get('entity_translation_entity_types', array());
$etypes['user'] = 'user';
variable_set('entity_translation_entity_types', $etypes);

// Add relevant fields to those entities.
add_link_field('user', 'user');


/**
 * Adds a long text field to the page content type.
 */
function add_long_text_field($entity, $bundle) {
  if (!$field = field_read_field('field_long_text')) {
    try {
      $field_definition = array(
        'field_name' => 'field_long_text',
        'type' => 'text_long',
        'cardinality' => 1,
        'module' => 'text',
      );
      $field = field_create_field($field_definition);
    }
    catch (FieldException $e) {
      echo "Unable to create long text field.\n";
    }
  }

  $field_instance_definition = array(
    'label' => 'Long Text',
    'field_id' => $field['id'],
    'required' => FALSE,
    'description' => '',
    'default_value' => NULL,
    'field_name' => 'field_long_text',
    'entity_type' => $entity,
    'settings' => array(
      'text_processing' => 1,
    ),
  );

// Don't bother if the field already exists.
  if (!field_read_instance($entity, 'field_long_text', $bundle)) {
    // Apply bundle-specific settings.
    $field_instance_definition['bundle'] = $bundle;

    try {
      field_create_instance($field_instance_definition);
    }
    catch (FieldException $e) {
      echo "Unable to create link field instance.\n";
    }
  }
}

/**
 * Adds a link field (label "Link", name "field_link") to the entity/bundle.
 */
function add_link_field($entity, $bundle) {
  if (!$field = field_read_field('field_link')) {
    try {
      $field_definition = array(
        'field_name' => 'field_link',
        'type' => 'link_field',
        'module' => 'link',
        'settings' => array(
          'attributes' => array(
            'target' => 'default',
            'class' => '',
            'rel' => '',
          ),
          'url' => 0,
          'title' => 'optional',
          'title_value' => '',
          'title_maxlength' => 128,
          'enable_tokens' => 1,
          'display' => array('url_cutoff' => 80),
        ),
        'translatable' => TRUE,
      );
      $field = field_create_field($field_definition);
    }
    catch (FieldException $e) {
      echo "Unable to create link field.\n";
    }
  }

  $field_instance_definition = array(
    'label' => 'Link',
    'field_id' => $field['id'],
    'required' => FALSE,
    'description' => '',
    'default_value' => NULL,
    'field_name' => 'field_link',
    'entity_type' => $entity,
  );

// Don't bother if the field already exists.
  if (!field_read_instance($entity, 'field_link', $bundle)) {
    // Apply bundle-specific settings.
    $field_instance_definition['bundle'] = $bundle;

    try {
      field_create_instance($field_instance_definition);
    }
    catch (FieldException $e) {
      echo "Unable to create link field instance.\n";
    }
  }
}

/**
 * Adds an image field to the page content type.
 * Note: Doesn't need to create the field because standard profile's got it.
 */
function add_image_field() {
  $instance_definition = field_read_instance('node', 'field_image', 'article');
  $instance_definition['description'] = '';
  unset($instance_definition['id']);

// Don't bother if the field already exists.
  if (!field_read_instance('node', 'field_image', 'page')) {
    // Apply bundle-specific settings.
    $instance_definition['bundle'] = 'page';

    try {
      field_create_instance($instance_definition);
    }
    catch (FieldException $e) {
      echo "Unable to create image field instance.\n";
    }
  }
}

/**
 * Adds a text field with cardinality to the page content type.
 */
function add_text_field_with_cardinality() {
  if (!$field = field_read_field('field_text')) {
    try {
      $field_definition = array(
        'field_name' => 'field_text',
        'type' => 'text',
        'cardinality' => 2,
        'module' => 'text',
        'settings' => array(
          'max_length' => 255,
        ),
      );
      $field = field_create_field($field_definition);
    }
    catch (FieldException $e) {
      echo "Unable to create text field with cardinality.\n";
    }
  }

  $field_instance_definition = array(
    'label' => 'Text (With Cardinality)',
    'field_id' => $field['id'],
    'required' => FALSE,
    'description' => '',
    'default_value' => NULL,
    'field_name' => 'field_text',
    'entity_type' => 'node',
  );

  // Don't bother if the field already exists.
  if (!field_read_instance('node', 'field_text', 'page')) {
    // Apply bundle-specific settings.
    $field_instance_definition['bundle'] = 'page';

    try {
      field_create_instance($field_instance_definition);
    }
    catch (FieldException $e) {
      echo "Unable to create text field instance.\n";
    }
  }
}

/**
 * Adds a field collection field which itself contains a text field.
 */
function add_field_collection_field() {
  if (!$field = field_read_field('field_field_collection')) {
    try {
      $field_definition = array(
        'field_name' => 'field_field_collection',
        'type' => 'field_collection',
        'cardinality' => -1,
        'module' => 'field_collection',
        'translatable' => TRUE,
        'settings' => array(
          'hide_blank_items' => TRUE,
          'path' => '',
          'entity_translation_sync' => FALSE,
        ),
      );
      $field = field_create_field($field_definition);
    }
    catch (FieldException $e) {
      echo "Unable to create field collection field.\n";
    }
  }

  $field_instance_definition = array(
    'label' => 'Field Collection Field',
    'field_id' => $field['id'],
    'required' => FALSE,
    'description' => '',
    'default_value' => NULL,
    'field_name' => 'field_field_collection',
    'entity_type' => 'node',
    'widget' => array(
      'type' => 'field_collection_embed',
      'module' => 'field_collection',
    ),
  );

  // Don't bother if the field already exists.
  if (!field_read_instance('node', 'field_field_collection', 'page')) {
    // Apply bundle-specific settings.
    $field_instance_definition['bundle'] = 'page';

    try {
      field_create_instance($field_instance_definition);
    }
    catch (FieldException $e) {
      echo "Unable to create text field instance.\n";
    }
  }

  // Attach a long text field to this field collection type.
  add_long_text_field('field_collection_item', 'field_field_collection');
}