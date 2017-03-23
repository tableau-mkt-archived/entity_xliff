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
        $node = workbench_moderation_node_current_load($wrapper->value());
        $wrapper->set($node);
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
        // And this is a source node (i.e. it is not an unsaved target with no ID)
        if (workbench_moderation_node_type_moderated($wrapper->getBundle())) {
          // Then use the current node revision, which is not necessarily the published one.
          $node_to_alter = workbench_moderation_node_current_load($node_to_alter);

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
