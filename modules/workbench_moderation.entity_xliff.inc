<?php

/**
 * @file
 * Hooks implemented on behalf of the Workbench Moderation module to integrate
 * with Entity Xliff.
 */

if (!function_exists('workbench_moderation_entity_xliff_translatable_source_alter')) {

  /**
   * Implements hook_entity_xliff_translatable_source_alter().
   */
  function workbench_moderation_entity_xliff_translatable_source_alter(&$wrapper, $type) {
    $id = $wrapper->getIdentifier();
    if ($type === 'node' && !empty($id)) {
      // If workbench moderation is enabled for this content type...
      // And this is a source node (i.e. it is not an unsaved target with no ID)
      if (workbench_moderation_node_type_moderated($wrapper->getBundle())) {
        // Then wrap the current revision, not the live version.
        $current_node = workbench_moderation_node_current_load($wrapper->value());
        $published_node = node_load($id);
        if ($current_node->vid != $published_node->vid) {
          // There is a published node and a current Draft.
          // First force the published node to stay published
          $wrapper->set($published_node);
          $published_node->workbench_moderation_state_new = workbench_moderation_state_published();
          $wrapper->save();
          // Then force the curent node to stay draft!
          $current_node->workbench_moderation_state_new = workbench_moderation_state_none();
          $wrapper->set($current_node);
        }
      }
    }
  }
}

if (!function_exists('workbench_moderation_entity_xliff_target_entities_alter')) {

  /**
   * Implements hook_entity_xliff_target_entities_alter().
   *
   * Allows us to use the current revisions of a node as opposed to whatever node_load() returns.
   *
   * @param $node_to_alter
   *        A node object which may or may not be the current revision.
   */
  function workbench_moderation_entity_xliff_target_entities_alter(&$node_to_alter) {
    try {
      $wrapper = entity_metadata_wrapper('node', $node_to_alter);
      $id = $wrapper->getIdentifier();
      if ($wrapper->type() === 'node' && !empty($id)) {
        // If workbench moderation is enabled for this content type...
        // And this is an existing target node (i.e. it is not an unsaved target with no ID)
        if (workbench_moderation_node_type_moderated($wrapper->getBundle())) {
          // Then use the current node revision, which is not necessarily the published one.
          $current_node = workbench_moderation_node_current_load($node_to_alter);
          if ($current_node) {
            $node_to_alter = $current_node;
          }
        }
      }
    } catch (Exception $e) {
      // If the wrapper caused an exception, catch it and log it.
      watchdog('entity xliff', 'There was a problem processing node @id. Message: !message', array(
        '@id' => $node_to_alter->nid,
        '!message' => $e->getMessage(),
      ), WATCHDOG_ERROR);
    }
  }
}

if (!function_exists('workbench_moderation_entity_xliff_presave_alter')) {

  /**
   * Implements hook_entity_xliff_presave_alter().
   *
   * I there is a current draft over a published node
   * Workbench_moderation has the unfortunate habit of unpublishing the published node
   * And overwriting the draft.
   *
   * In that situation this code saves the new target node first and then passes the
   * Published node forward to be saved by entity_xliff.
   * It also manages the draft/published state based upon the existing target.
   *
   * It is dirty but it works.
   *
   * @param wrapper
   *        A wrapper object which may or may not want to be the published revision.
   */
  function workbench_moderation_entity_xliff_presave_alter(&$wrapper, $type) {

    if ($wrapper->type() === 'node') {

      $node = $wrapper->value();
      $id = $wrapper->getIdentifier();
      if (!empty($id)) {
        $current_node = workbench_moderation_node_current_load($node);
        $published_node = node_load($node->nid);
        if ($current_node->vid != $published_node->vid) {
          // There is a published node and a current Draft.
          $wrapper->save();
          $wrapper->set($published_node);
          $node->workbench_moderation_state_new = workbench_moderation_state_none();
        }
      }else{
        // This is a brand new target, remove any workbench stuff left from the source.
        // Force it to follow the default that is set in workbench.
        unset($node->workbench_moderation);
        $node->workbench_moderation_state_new = variable_get('workbench_moderation_default_state_' . $node->type, workbench_moderation_state_none());
      }

    }
  }
}
