@api
Feature: Handling for Encoded HTML Entities
  In order to prove that XLIFFs that include HTML encoded characters can be imported
  Site administrators should be able to
  Import and export XLIFF translations that contain HTML encoded entities

  Background:
    Given I am logged in as a user with the "administer entity xliff" permission
    And "page" content:
      | title             | body                                 | language | promote |
      | French page title | French page body text 'en français.' | fr       | 1       |
    And I am on the homepage
    And I follow "French page title"

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
