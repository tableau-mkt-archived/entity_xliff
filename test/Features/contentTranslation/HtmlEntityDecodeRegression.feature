@api
Feature: Handling for Encoded HTML Entities
  In order to prove that XLIFFs that include HTML encoded characters can be imported
  Site administrators should be able to
  Import and export XLIFF translations that contain HTML encoded entities

  Background:
    Given I am logged in as a user with the "administer entity xliff,bypass node access,bypass workbench moderation,view moderation history,translate content" permission
    And I am on the homepage
    And I click "Add content"
    And I click "Basic page"
    And I fill in "French page title" for "title"
    And I fill in "en page body text 'en français.'" for "Long Text"
    And I select "French" from "Language"
    And I press the "Save" button

  Scenario: Import XLIFF containing HTML encoded entities through portal
    When I click "XLIFF"
    # Note: code in FeatureContext.php that replaces single quotes and the "ç"
    # character with UTF-8 encoded equivalents.
    And I attach an "en" translation of this "French" node
    And I press the "Import" button
    Then I should see the success message containing "Successfully imported"
    When I click "View published"
    And I click "English"
    Then I should see the heading "en page title"
    And I should see "en page body text 'en français.'"
