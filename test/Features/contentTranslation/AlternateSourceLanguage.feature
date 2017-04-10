@api
Feature: Alternative Source Languages
  In order to prove that source content can be of any language
  Site administrators should be able to
  Import and export XLIFF translations with language sources other than English

  Background:
    Given I am logged in as a user with the "administer entity xliff,bypass node access,bypass workbench moderation,view moderation history,translate content" permission

    And "page" content:
      | title             | field_long_text                   | language | promote | status |
      | French page title | French page body text. | fr       | 1       | 1      |

    And I am on the homepage
    And I click "French page title"

  Scenario: Access and Export French sourced XLIFF through portal
    When I click "XLIFF"
    Then the url should match "node/\d+/xliff"
    And I should see "Export as XLIFF"
    And I should see "Import from XLIFF"
    When I click "Download"
    Then the response should contain "<xliff"
    And the response should contain "<source xml:lang=\"fr\">French page title</source>"

  Scenario: Import French sourced XLIFF through portal
    When I click "XLIFF"
    When I attach an "en" translation of this "French" node
    And I press the "Import" button
    Then print last response
    Then I should see the success message containing "Successfully imported"
    And there should be no corrupt translation sets.

    When I click "Translate"
    And I click "en page title"
    Then I should see the heading "en page title"
    And I should see "en page body text."

    # Re-import to test the pre-existing/non-initialization flow.
    When I click "Translate"
    And I click "French page title"
    And I click "XLIFF"
    And I attach an "en" translation of this "French" node
    And I press the "Import" button
    Then I should see the success message containing "Successfully imported"
    And there should be no corrupt translation sets.
    When I click "View published"
    Then I should see the heading "French page title"
    And I should see "French page body text."
    When I click "Translate"
    And I click "en page title"
    Then I should see the heading "en page title"
    And I should see "en page body text."