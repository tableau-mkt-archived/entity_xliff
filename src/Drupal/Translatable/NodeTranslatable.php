<?php

/**
 * @file
 *
 */

namespace EntityXliff\Drupal\Translatable;

/**
 * Class NodeTranslatable
 * @package EntityXliff\Drupal\Translatable
 */
class NodeTranslatable extends EntityTranslatableBase {

  /**
   * An array of partial nodes in the translation set represented by this node,
   * keyed by site (used for content translation).
   *
   * @var array
   */
  protected $tset = array();

  /**
   * {@inheritdoc}
   *
   * @param array $translationSet
   *   An array of partial nodes, keyed by their respective language codes, that
   *   constitute the translation set for this node. Used when content
   *   translation is in use. If no translation set is provided, a default will
   *   be provided by translation_node_get_translations().
   */
  public function __construct(\EntityDrupalWrapper $entityWrapper, array $entityInfo = array(), array $translationSet = array()) {
    parent::__construct($entityWrapper, $entityInfo);

    // Handle content translation for nodes.
    $this->drupalStaticReset('translation_node_get_translations');
    if ($entityWrapper->language->value() === 'en') {
      $this->tset = $translationSet ?: $this->nodeGetTranslations((int) $entityWrapper->getIdentifier());
    }
    else {
      $raw = $entityWrapper->raw();
      if (!is_object($raw)) {
        $raw = $this->nodeLoad((int) $raw);
      }
      $this->tset = $translationSet ?: $this->nodeGetTranslations((int) $raw->tnid);
    }
  }

  /**
   * @return array
   */
  public function getTranslatableFields(\EntityDrupalWrapper $wrapper = NULL) {
    $fields = parent::getTranslatableFields($wrapper);
    $type = $wrapper->type();

    // Only add the title property if we're using content translation.
    if ($type === 'node' && !$this->moduleExists('entity_translation')) {
      $fields[] = 'title';
    }

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntity($targetLanguage) {
    if (!isset($this->targetEntities[$targetLanguage]) || empty($this->targetEntities[$targetLanguage])) {
      // Handling for content translation. Entity field translation should be
      // taken care of by the parent. @todo check if that assumption is legit.
      if (!$this->moduleExists('entity_translation')) {
        // If a translation already exists, use it!
        if (isset($this->tset[$targetLanguage]->nid)) {
          $target = $this->nodeLoad($this->tset[$targetLanguage]->nid, NULL, TRUE);

          // Do not mark this node as a new revision. This is necessary in
          // cases where this node happens to reference a field collection...
          $target->revision = FALSE;
        }
        // Otherwise, "clone" the original and mark it as new.
        else {
          // Ensure that the original is ready for translation.
          $this->initializeContentTranslation();

          $target = $this->entity->raw();
          if (!is_object($target) && !empty($target)) {
            $target = $this->nodeLoad((int) $target, NULL, TRUE);
          }
          $target->translation_source = clone $target;

          unset($target->nid, $target->vid);
          $target->is_new = TRUE;
          $target->tnid = (int) $this->entity->getIdentifier();
          $target->language = $targetLanguage;
        }

        $this->targetEntities[$targetLanguage] = $this->entityMetadataWrapper('node', $target);
      }
      else {
        $this->targetEntities[$targetLanguage] = parent::getTargetEntity($targetLanguage);
      }
    }

    return $this->targetEntities[$targetLanguage];
  }

  /**
   * Initializes content translation on the translation set master, in cases
   * where it hasn't yet been translated.
   *
   * Put in English: this converts a language neutral node to an English node
   * that is part of a translation set.
   */
  protected function initializeContentTranslation() {
    $nid = (int) $this->entity->getIdentifier();
    $source = $this->nodeLoad($nid, NULL, TRUE);
    if ($source->language === LANGUAGE_NONE || empty($source->tnid)) {
      $source->tnid = $nid;
      $source->language = 'en';
      $this->nodeSave($source);
      $this->entity = $this->entityMetadataWrapper('node', $source);
      $this->drupalStaticReset('translation_node_get_translations');
    }
  }

  /**
   * OO wrapper around translation_node_get_translations().
   *
   * @param int $tnid
   * @return array
   */
  protected function nodeGetTranslations($tnid) {
    // We have to temporarily pretend to have access to all nodes. If a node in
    // a translation set happens to be unpublished, we still want to know about
    // it so we can update/override it (rather than creating a new one).
    $tempUser = clone $GLOBALS['user'];
    $this->drupalSaveSession(FALSE);
    $GLOBALS['user'] = user_load(1);
    $tset = translation_node_get_translations($tnid);
    $GLOBALS['user'] = $tempUser;
    $this->drupalSaveSession(TRUE);
    return $tset;
  }

  /**
   * OO wrapper around node_load().
   * @param null $nid
   * @param null $vid
   * @param bool $reset
   */
  protected function nodeLoad($nid = NULL, $vid = NULL, $reset = FALSE) {
    return node_load($nid, $vid, $reset);
  }

  /**
   * OO wrapper around node_save().
   * @param $node
   * @throws \Exception
   */
  protected function nodeSave($node) {
    node_save($node);
  }

  /**
   * OO wrapper around module_exists().
   *
   * @param string $module
   *   The name of the module (without the .module extension).
   *
   * @return bool
   *   TRUE if the module is both installed and enabled, FALSE otherwise.
   */
  protected function moduleExists($module) {
    return module_exists($module);
  }

  /**
   * OO wrapper around drupal_static_rest().
   * @param string $name
   */
  protected function drupalStaticReset($name = NULL) {
    drupal_static_reset($name);
  }

  /**
   * OO wrapper around drupal_save_session().
   * @param bool $status
   */
  protected function drupalSaveSession($status = NULL) {
    return drupal_save_session($status);
  }

}
