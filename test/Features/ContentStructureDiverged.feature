@api
Feature: Content structure divergence
  In order to prove that attempts to import stale XLIFFs are safe
  Site administrators should be able to
  Attempt to import XLIFF translations with outdated field structures and receive useful error messaging

  Background:
    Given I am logged in as a user with the "administer entity xliff,bypass node access" permission
    And "page" content:
      | title                    | body                          | language  | promote |
      | Outdated structure title | Exact contents do not matter. | und       | 1       |
    And I am on the homepage
    And I follow "Outdated structure title"

  Scenario: Attempt to import XLIFF with outdated field structures
    When I click "XLIFF"
    And I attach an outdated translation of this node
    And I press the "Import" button
    Then I should not see the message containing "Successfully imported"
    # @todo: Then I should see the error message containing "You will need to re-export and try again."
    And there should be no corrupt translation sets.
    When I click "Edit"
    # Ensures database transaction rollback occurred (the initialization of the
    # node from language neutral to English should be reverted).
    Then I should see "Language neutral" in the "#edit-language" element
