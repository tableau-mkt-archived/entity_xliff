@api
Feature: Content structure divergence
  In order to prove that attempts to import stale XLIFFs are safe
  Site administrators should be able to
  Attempt to import XLIFF translations with outdated field structures and receive useful error messaging

  Background:
    Given I am logged in as a user with the "administer entity xliff,bypass node access,bypass workbench moderation,view moderation history,translate content" permission
    And I am on the homepage
    And I click "Add content"
    And I click "Basic page"
    And I fill in "Outdated structure title" for "title"
    And I fill in "This page is English in a published state." for "Long Text"
    And I press the "Save" button

  Scenario: Attempt to import XLIFF with outdated field structures
    When I click "XLIFF"
    And I attach an outdated translation of this node
    And I press the "Import" button
    Then there should be no corrupt translation sets.
    And I should not see the message containing "Successfully imported"
    And I should see the error message containing "You will need to re-export and try again."

    When I click "New draft"
    # Ensures database transaction rollback occurred (the initialization of the
    # node from language neutral to English should be reverted).
    Then I should see "Language neutral" in the "#edit-language" element
