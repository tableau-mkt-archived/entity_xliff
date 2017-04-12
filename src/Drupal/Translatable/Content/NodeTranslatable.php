<?php

/**
 * @file
 * Defines a Node translatable compatible with the core Translation module.
 */

namespace EntityXliff\Drupal\Translatable\Content;

use EntityXliff\Drupal\Factories\EntityTranslatableFactory;
use EntityXliff\Drupal\Mediator\FieldMediator;
use EntityXliff\Drupal\Translatable\EntityTranslatableBase;
use EntityXliff\Drupal\Utils\DrupalHandler;


/**
 * Class NodeContentTranslatable
 * @package EntityXliff\Drupal\Translatable\Content
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
  public function __construct(\EntityDrupalWrapper $entityWrapper, DrupalHandler $handler = NULL, EntityTranslatableFactory $factory = NULL, FieldMediator $fieldMediator = NULL) {
    parent::__construct($entityWrapper, $handler, $factory, $fieldMediator);

    $this->evaluateTranslationSet();
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslatableFields() {
    $fields = parent::getTranslatableFields();
    if ($this->isTranslatable()) {
      $fields[] = 'title';
    }
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function isTranslatable() {
    return $this->drupal->translationSupportedType($this->entity->getBundle());
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntity($targetLanguage) {
    if (!isset($this->targetEntities[$targetLanguage]) || empty($this->targetEntities[$targetLanguage])) {
      // Always re-evaluate the translation set. It's possible that we may have
      // saved off a translated version in the set already during this request.
      // We don't want to keep appending duplicate nodes to the set.
      $this->evaluateTranslationSet();

      // If a translation already exists, use it!
      // $sourceLanguageOriginal = existing translation set source node (or the original source if there is no translation set).
      // $targetLanguageOriginal = exisiting translation set target (i.e. the node in the target language).
      // $target = New node (cloned from $sourceLanguageOriginal) used as target of xliff to ensure the complete structure exists.
      if (isset($this->tset[$targetLanguage]->nid)) {

        // Although an actual target already exists, we load the source and run it
        // through field translation preparation as if one didn't exist already.
        // This ensures all embedded entities have some structure for XLIFF
        // content to be set against.
        $targetLanguageOriginal = $this->drupal->nodeLoad($this->tset[$targetLanguage]->nid, NULL, TRUE);
        $sourceNid = $targetLanguageOriginal->tnid;
        $sourceLanguageOriginal = $this->drupal->nodeLoad($sourceNid);

        // Allow for alterations to the node entities
        // (i.e. workbench moderation to get current revisions).
        $this->drupal->alter('entity_xliff_target_entities', $sourceLanguageOriginal);
        $this->drupal->alter('entity_xliff_target_entities', $targetLanguageOriginal);
        // Retrieve the source and then clone it so that we are not accidentally
        // overwriting the version in static cache.
        $target = clone $sourceLanguageOriginal;
        unset($target->nid, $target->vid, $target->tnid);

        // Set properties as though translation_node_prepare set them.
        $target->language = $targetLanguage;
        $target->translation_source = $sourceLanguageOriginal;
        $target->title = $sourceLanguageOriginal->title;

        // Run through field translation preparation, but be sure to reset the
        // actual nid/vid/tnid values. This ensures that we never mistakenly
        // set translations on entities embedded on the source translation.
        $this->drupal->fieldAttachPrepareTranslation('node', $target, $targetLanguage, $sourceLanguageOriginal, $sourceLanguageOriginal->language);
        $target->nid = $targetLanguageOriginal->nid;
        $target->tnid = $targetLanguageOriginal->tnid;

        // Ensure all non-translatable properties are preserved from the actual
        // target (including path, but potentially other stuff from contrib
        // like metatags, workbench moderation state, etc).
        $translatableFields = $this->getTranslatableFields();
        $this->drupal->alter('entity_xliff_translatable_fields', $translatableFields, $this->entity);
        foreach ((array) $targetLanguageOriginal as $property => $propertyValue) {
          if (array_search($property, $translatableFields) === FALSE) {
            $target->{$property} = $propertyValue;
          }
        }

        // Do not mark this node as a new revision. This is necessary in
        // cases where this node happens to reference a field collection...
        $target->revision = TRUE;

      }
      // Otherwise create a new node in the target language
      // and then prepare it for translation.
      // The fieldAttachPrepareTranslation step is especially
      // important for paragraph fields, without it they get
      // duplicated with new revisions instead of replicated.
      else {
        // Make sure that the source node has its language set to "en".
        $this->initializeTranslation();

        // Retrieve the source and then clone it so that we are not accidentally
        // overwriting the version in static cache.
        $sourceLanguageOriginal = $this->getRawEntity($this->entity);
        $target = clone $sourceLanguageOriginal;
        // Set the original as the translation source to maintain the translation set.
        $target->translation_source = $sourceLanguageOriginal;
        // Clear out and/or set all the IDs from the target so it can be a new node.
        unset($target->nid, $target->vid);
        $target->is_new = TRUE;
        $target->language = $targetLanguage;

        // Run fieldAttachPrepareTranslation to fire translation related alters on
        // any complex field types (i.e. paragraphs!).
        $this->drupal->fieldAttachPrepareTranslation('node', $target, $targetLanguage, $sourceLanguageOriginal, $sourceLanguageOriginal->language);
      }

      $this->targetEntities[$targetLanguage] = $this->drupal->entityMetadataWrapper('node', $target);

    }

    return $this->targetEntities[$targetLanguage];
  }

  /**
   * {@inheritdoc}
   *
   * This converts a language neutral node to an English node that is part of a
   * translation set.
   */
  public function initializeTranslation() {
    $source = $this->getRawEntity($this->entity);
    if ($source->language === DrupalHandler::LANGUAGE_NONE || empty($source->tnid)) {
      $source->tnid = $source->nid;
      $source->language = $this->getSourceLanguage();
      $this->drupal->nodeSave($source);
      $this->entity = $this->drupal->entityMetadataWrapper('node', $source);
      $this->drupal->staticReset('translation_node_get_translations');
      $this->tset = $this->nodeGetTranslations((int) $source->tnid);
    }
  }

  /**
   * Evaluates or re-evaluates the translation set for the wrapped node and
   * stashes it on the "tset" property.
   */
  protected function evaluateTranslationSet() {
    $this->drupal->staticReset('translation_node_get_translations');
    $raw = $this->getRawEntity($this->entity);

    // If the tnid is not yet set or doesn't exist, assume we're about to make
    // a new translation set and this is the source (so, use this nid).
    if (!isset($raw->tnid) || empty($raw->tnid)) {
      $this->tset = $this->nodeGetTranslations((int) $this->entity->getIdentifier());
    }
    // Otherwise, always pull the translation set from the wrapped node's tnid.
    else {
      $this->tset = $this->nodeGetTranslations((int) $raw->tnid);
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

  /**
   * OO wrapper around translation_node_prepare().
   * @param object $node
   * @param int $sourceNid
   * @param string $targetLang
   */
  protected function translationNodePrepare($node, $sourceNid, $targetLang) {
    $tempUser = clone $GLOBALS['user'];
    $this->drupal->saveSession(FALSE);
    $GLOBALS['user'] = $this->drupal->userLoad(1);
    $_GET['translation'] = $sourceNid;
    $_GET['target'] = $targetLang;
    $this->drupal->translationNodePrepare($node);
    unset($_GET['translation'], $_GET['target']);
    $GLOBALS['user'] = $tempUser;
    $this->drupal->saveSession(TRUE);
  }

}
