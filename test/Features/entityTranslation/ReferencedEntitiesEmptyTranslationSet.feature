@api
Feature: Referenced Entities with empty translation sets
  In order to prove that it is possible to safely translate referenced entities that have an associated translation set that is empty
  Site administrators should be able to
  Import and export XLIFFs through the portal UI

  Background:
    Given I am logged in as a user with the "administer entity xliff,bypass node access" permissions
    And "page" content:
      | title                               | promote | status |
      | English empty tset host page title  | 1       | 1      |
      | English empty tset child page title | 1       | 1      |
    When I am on the homepage
    And I follow "English empty tset host page title"
    Given this node references the "English empty tset child page title" node
    When I reload the page
    Then I should see "English empty tset host page title"
    And I should see "English empty tset child page title"
    When I attach a "fr" translation of this "English" node
    And I press the "Import" button
    Then I should see the success message containing "Successfully imported"
    And there should be no corrupt translation sets.
    # Remove the translations from the translation set.
    When I go to the homepage
    And I follow "fr empty tset host page title"
    And I click "New draft"
    And I press "Delete"
    And I press "Delete"
    And I go to the homepage
    And I follow "fr empty tset child page title"
    And I click "New draft"
    And I press "Delete"
    And I press "Delete"

  Scenario: Export XLIFF through portal
    Given I am on the homepage
    And follow "English empty tset host page title"
    When I click "XLIFF"
    And I click "Download"
    Then the response should contain "<xliff"
    And the response should contain "<source xml:lang=\"en\">English empty tset host page title</source>"
    And the response should contain "<source xml:lang=\"en\">English empty tset child page title</source>"

  Scenario: Import XLIFF through portal
    Given I am on the homepage
    And follow "English empty tset host page title"
    And I click "XLIFF"
    When I attach a "fr" translation of this "English" node
    And I press the "Import" button
    Then I should see the success message containing "Successfully imported"
    And there should be no corrupt translation sets.
    When I click "View"
    And I click "Français"
    Then I should see "fr empty tset host page title"
    And I should see "fr empty tset child page title"
    # Re-import to test the pre-existing/non-initialization flow.
    When I click "English"
    And I click "XLIFF"
    And I attach a "fr" translation of this "English" node
    And I press the "Import" button
    Then I should see the success message containing "Successfully imported"
    And there should be no corrupt translation sets.
    When I click "View"
    Then I should see "English empty tset host page title"
    And I should see "English empty tset child page title"
    And I click "Français"
    Then I should see "fr empty tset host page title"
    And I should see "fr empty tset child page title"
