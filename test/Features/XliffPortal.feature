@api
Feature: XLIFF Portal
  In order to prove the module "works" in the broadest sense,
  Site administrators should be able to
  Import and export XLIFF translations through the XLIFF portal UI

  Background:
    Given I am logged in as a user with the "administer entity xliff" permission

  Scenario: Access XLIFF portal local task
    Given I am viewing a "page" content with the title "English page title"
    Then I should see the link "XLIFF"
    When I click "XLIFF"
    Then the url should match "node/\d+/xliff"
    And I should see "Export as XLIFF"
    And I should see "Import from XLIFF"

  Scenario: Export XLIFF through portal
    Given I am viewing a "page" content with the title "English page title"
    And I click "XLIFF"
    When I click "Download"
    Then the response should contain "<xliff"
    And the response should contain "<source xml:lang=\"en\">English page title</source>"

  Scenario: Import XLIFF through portal
    Given "page" content:
    | title              | body                    | promote |
    | English page title | English page body text. | 1       |
    When I am on the homepage
    And follow "English page title"
    And I click "XLIFF"
    When I attach a "fr" translation of this node
    And I press the "Import" button
    Then I should see the success message containing "Successfully imported"
    When I click "View"
    And I click "Français"
    Then I should see the heading "fr page title"
    And I should see "fr page body text."

  Scenario: No access to XLIFF portal local task without permissions
    Given I am not logged in
    When I am logged in as a user with the "authenticated user" role
    And I am viewing a "page" content with the title "English page title"
    Then I should not see the link "XLIFF"
