<?php

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends RawDrupalContext implements SnippetAcceptingContext {

  /**
   * Initializes context.
   *
   * Every scenario gets its own context instance.
   * You can also pass arbitrary arguments to the
   * context constructor through behat.yml.
   */
  public function __construct() {
  }

  /**
   * @When I attach a :langcode translation of this content
   */
  public function iAttachATranslationOfThisContent($langcode) {
    $baseUrl = $this->getMinkParameter('base_url');
    $path = $this->getMinkParameter('files_path');
    $session = $this->getSession();
    $url = $session->getCurrentUrl();

    if (preg_match('/node\/(\d+)/', $url, $matches)) {
      $nid = $matches[1];
      $randomFile = mt_rand(0, 10000) . '-' . $langcode . '.xlf';

      // Load the XLIFF file for this node.
      $this->getSession()->visit($baseUrl . "/node/$nid/as.xlf?targetLang=$langcode");
      $xliff = $session->getPage()->getContent();

      // "Translate" the file.
      $translation = str_replace('English', $langcode, $xliff);
      $fullPath = $path . DIRECTORY_SEPARATOR . $randomFile;

      // Write the file to the configured path.
      if (file_put_contents($fullPath, $translation)) {
        // Go back and attach the file to the expected import field.
        $session->visit($baseUrl . "/node/$nid/xliff");
        $page = $session->getPage();
        $importFileField = $page->find('css', 'input[name="files[import-' . $langcode . ']"]');
        $importFileField->attachFile($fullPath);
      }
      else {
        throw new Exception('Unable to write translation to disk.');
      }
    }
    else {
      throw new Exception('Unable to determine what "this content" is referring to.');
    }
  }

}
