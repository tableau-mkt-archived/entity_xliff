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
   * @When I attach a :arg1 translation of this content
   */
  public function iAttachATranslationOfThisContent($language) {
    $path = $this->getMinkParameter('files_path');
    $page = $this->getSession()->getPage();

    if ($downloadLink = $page->find('xpath', '(//a[text()="Download"])[2]')) {
      $importFileField = $page->find('css', 'input[name="files[import-fr]"]');
      $randomFile = mt_rand(0, 10000) . '-' . $language . '.xlf';

      // Click the second "download" button to get the XLIFF.
      $downloadLink->click();
      $xliff = $page->getContent();

      // "Translate" the file.
      $translation = str_replace('English', $language, $xliff);
      $fullPath = $path . DIRECTORY_SEPARATOR . $randomFile;

      // Write the file to the configured path.
      file_put_contents($fullPath, $translation);

      // Go back and attach the file to the expected import field.
      $this->getSession()->back();
      $importFileField->attachFile($fullPath);
    }
    else {
      // Allow use of this step from any local task off of a piece of content.
      if ($portalLink = $page->findLink('XLIFF')) {
        $portalLink->click();
        $this->iAttachATranslationOfThisContent($language);
      }
      else {
        throw new Exception('Unable to determine what "this content" is referring to.');
      }
    }
  }

}
