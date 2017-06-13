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
        $latest_node = node_load($id);
        // The only case where the latest node revision is NOT the "current" node
        // is if there is a draft over a published version.
        // Otherwise the current will always be the most recent regardless of status.
        if ($current_node->vid !== $latest_node->vid) {
          // In this situation we have to make sure that the latest revision stays published.
          $wrapper->set($latest_node);
          $latest_node->workbench_moderation_state_new = workbench_moderation_state_published();
          $wrapper->save();
          // We also force the curent node to stay draft.
          $current_node->workbench_moderation_state_new = workbench_moderation_state_none();
          $wrapper->set($current_node);
          // Save happens in calling function.
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
   * If there is a current draft over a published node
   * Workbench_moderation has the unfortunate habit of unpublishing the published node
   * And overwriting the draft.
   *
   * In that situation this code grabs the draft node to be saved by entity_xliff.
   *
   * @param wrapper
   *        A wrapper object which may or may not want to be the published revision.
   */
  function workbench_moderation_entity_xliff_presave_alter(&$wrapper, $type) {

    if ($wrapper->type() === 'node' && workbench_moderation_node_type_moderated($wrapper->getBundle())) {

      $node = $wrapper->value();
      $id = $wrapper->getIdentifier();
      if (!empty($id)) {
        $current_node = workbench_moderation_node_current_load($node);
        $latest_node = node_load($node->nid);
        // The only case where the latest node revision is NOT the "current" node
        // is if there is a draft over a published version.
        // Otherwise the current will always be the most recent regardless of status.
        if ($current_node->vid != $latest_node->vid) {
          // There is a published node and a current Draft.
          // Use the current (i.e. draft) node and keep it draft.
          $wrapper->set($current_node);
          $current_node->workbench_moderation_state_new = workbench_moderation_state_none();
        }
      }
      else {
        // This is a brand new target, remove any workbench stuff left from the source.
        // Force it to follow the default that is set in workbench.
        unset($node->workbench_moderation);
        $node->workbench_moderation_state_new = variable_get('workbench_moderation_default_state_' . $node->type, workbench_moderation_state_none());
        // Workbench looks for workbench_moderation_state_current in its own presave_alter.
        // Since this is a known new node, force it to follow the new default state.
        $node->workbench_moderation_state_current = $node->workbench_moderation_state_new;
      }

    }
  }
}
