@api
Feature: Node References
  In order to prove that nodes referenced using the references module are compiled and also translated
  Site administrators should be able to
  Import and export XLIFFs through the portal UI including, node references

  Background:
    Given I am logged in as a user with the "administer entity xliff" permission
    And "page" content:
      | title                              | field_long_text                    | promote | status |
      | English node host page title       | English node host body text.       | 1       | 1       |
      | English node child page title      | English node child body text.      | 1       | 1       |
      | English node grandchild page title | English node grandchild body text. | 1       | 1       |
    When I am on the homepage
    And follow "English node child page title"
    And this node references the "English node grandchild page title" node on the field_node_reference field
    And I am on the homepage
    And follow "English node host page title"
    And this node references the "English node child page title" node on the field_node_reference field
    When I reload the page
    Then I should see "English node host page title"
    And I should see "English node host body text"
    And I should see "English node child page title"
    And I should see "English node child body text"
    And I should see "English node grandchild page title"
    And I should see "English node grandchild body text"

  Scenario: Export XLIFF through portal
    Given I am on the homepage
    And follow "English node host page title"
    When I click "XLIFF"
    And I click "Download"
    Then the response should contain "<xliff"
    And the response should contain "<source xml:lang=\"en\">English node host page title</source>"
    And the response should contain "<source xml:lang=\"en\">English node host body text.</source>"
    And the response should contain "<source xml:lang=\"en\">English node child page title</source>"
    And the response should contain "<source xml:lang=\"en\">English node child body text.</source>"
    And the response should contain "<source xml:lang=\"en\">English node grandchild page title</source>"
    And the response should contain "<source xml:lang=\"en\">English node grandchild body text.</source>"

  Scenario: Import XLIFF through portal
    Given I am on the homepage
    And follow "English node host page title"
    And I click "XLIFF"
    When I attach a "fr" translation of this "English" node
    And I press the "Import" button
    Then I should see the success message containing "Successfully imported"
    When I click "View"
    And I click "fr node host page title"
    Then I should see "fr node host page title"
    And I should see "fr node host body text"
    And I should see "fr node child page title"
    And I should see "fr node child body text"
    And I should see "fr node grandchild page title"
    And I should see "fr node grandchild body text"
    And there should be no corrupt translation sets.
    # Re-run import to test the pre-initialized case.
    When I click "English node host page title"
    And I attach a "fr" translation of this "English" node
    And I press the "Import" button
    Then I should see the success message containing "Successfully imported"
    When I click "View"
    And I click "fr node host page title"
    Then I should see "fr node host page title"
    And I should see "fr node host body text"
    And I should see "fr node child page title"
    And I should see "fr node child body text"
    And I should see "fr node grandchild page title"
    And I should see "fr node grandchild body text"
    And there should be no corrupt translation sets.
