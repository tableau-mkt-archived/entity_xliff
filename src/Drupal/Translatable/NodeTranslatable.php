<?php

/**
 * @file
 *
 */

namespace EntityXliff\Drupal\Translatable;

use EntityXliff\Drupal\Utils\DrupalHandler;


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
   */
  public function __construct(\EntityDrupalWrapper $entityWrapper, DrupalHandler $handler = NULL) {
    parent::__construct($entityWrapper, $handler);

    // Handle content translation for nodes.
    $this->drupal->staticReset('translation_node_get_translations');
    if ($entityWrapper->language->value() === 'en') {
      $this->tset = $this->nodeGetTranslations((int) $entityWrapper->getIdentifier());
    }
    else {
      $raw = $entityWrapper->raw();
      if (!is_object($raw)) {
        $raw = $this->drupal->nodeLoad((int) $raw);
      }
      $this->tset = $this->nodeGetTranslations((int) $raw->tnid);
    }
  }

  /**
   * @param \EntityDrupalWrapper $wrapper
   * @return array
   */
  public function getTranslatableFields(\EntityDrupalWrapper $wrapper = NULL) {
    $fields = parent::getTranslatableFields($wrapper);
    $type = $wrapper->type();

    // Only add the title property if we're using content translation.
    if ($type === 'node' && !$this->drupal->moduleExists('entity_translation')) {
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
      if (!$this->drupal->moduleExists('entity_translation')) {
        // If a translation already exists, use it!
        if (isset($this->tset[$targetLanguage]->nid)) {
          $target = $this->drupal->nodeLoad($this->tset[$targetLanguage]->nid, NULL, TRUE);

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
            $target = $this->drupal->nodeLoad((int) $target, NULL, TRUE);
          }
          $target->translation_source = clone $target;

          unset($target->nid, $target->vid);
          $target->is_new = TRUE;
          $target->tnid = (int) $this->entity->getIdentifier();
          $target->language = $targetLanguage;
        }

        $this->targetEntities[$targetLanguage] = $this->drupal->entityMetadataWrapper('node', $target);
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
    $source = $this->drupal->nodeLoad($nid, NULL, TRUE);
    if ($source->language === DrupalHandler::LANGUAGE_NONE || empty($source->tnid)) {
      $source->tnid = $nid;
      $source->language = 'en';
      $this->drupal->nodeSave($source);
      $this->entity = $this->drupal->entityMetadataWrapper('node', $source);
      $this->drupal->staticReset('translation_node_get_translations');
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
    $this->drupal->saveSession(FALSE);
    $GLOBALS['user'] = $this->drupal->userLoad(1);
    $tset = $this->drupal->translationNodeGetTranslations($tnid);
    $GLOBALS['user'] = $tempUser;
    $this->drupal->saveSession(TRUE);
    return $tset;
  }

}
