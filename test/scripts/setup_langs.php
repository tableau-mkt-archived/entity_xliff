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

// Enable translation for the page content type.
variable_set('language_content_type_page', TRANSLATION_ENABLED);

// Add relevant fields to the content type:
add_long_text_field();
add_link_field();
add_image_field();
add_text_field_with_cardinality();




/**
 * Adds a long text field to the page content type.
 */
function add_long_text_field() {
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
    'entity_type' => 'node',
  );

// Don't bother if the field already exists.
  if (!field_read_instance('node', 'field_long_text', 'page')) {
    // Apply bundle-specific settings.
    $field_instance_definition['bundle'] = 'page';

    try {
      field_create_instance($field_instance_definition);
    }
    catch (FieldException $e) {
      echo "Unable to create link field instance.\n";
    }
  }
}

/**
 * Adds a link field (label "Link", name "field_link") to the page content type.
 */
function add_link_field() {
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
    'entity_type' => 'node',
  );

// Don't bother if the field already exists.
  if (!field_read_instance('node', 'field_link', 'page')) {
    // Apply bundle-specific settings.
    $field_instance_definition['bundle'] = 'page';

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
