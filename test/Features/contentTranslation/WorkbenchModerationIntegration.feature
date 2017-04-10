@api
Feature: Workbench Moderation Feature
  In order to prove that workbench moderation is fully supported
  Site administrators should be able to
  Import and export XLIFF translations while using workbench moderation features

  Background:
    Given I am logged in as a user with the "administer entity xliff,bypass node access,bypass workbench moderation,view moderation history,translate content" permission
    And "page" content:
      | title          | field_long_text                               | promote | status | language |
      | English page | This page is English in a published state.  | 1       | 1      | en |
    And I am on the homepage
    And I follow "English page"

  Scenario: Import should follow the default moderation state
    And I click "XLIFF"
    And I attach a "fr" translation of this "English" node
    And I press the "Import" button
    And I click "Translate"
    And I click "fr page"
    # Make sure the new target has been published.
    Then I should see "View published"


  Scenario: Export current revision instead of published revision
    Given I click "New draft"
    When I fill in "This page is English in a draft state." for "Long Text"
    And I select "draft" from "workbench_moderation_state_new"
    And I press "Save"
    Then I should see "Edit draft"
    When I click "XLIFF"
    And I click "Download"
    Then the response should contain "<xliff"
    And the response should contain "<source xml:lang=\"en\">This page is English in a draft state.</source>"
    And the response should not contain "<source xml:lang=\"en\">This page is English in a published state.</source>"

  Scenario: Import over current revision even if it is an unpublished draft
    Then I click "New draft"
    When I fill in "This page is English in a NEW draft state." for "Long Text"
    And I select "draft" from "workbench_moderation_state_new"
    And I press "Save"
    Then I should see "View draft"
    And I click "XLIFF"
    And I attach a "fr" translation of this "English" node
    And I press the "Import" button
    Then I should see the success message containing "Successfully imported"
    # Make sure that the source node has not been published.
    Then I should see "View draft"
    And I click "Translate"
    And I click "fr page"
    # Make sure the target has not been published.
    Then I should see "View draft"
    # Add a new published revision.
    Then I click "Edit draft"
    When I fill in "This page is French in a published state." for "Long Text"
    And I select "published" from "workbench_moderation_state_new"
    And I press "Save"
    # Now add a new draft.
    Then I click "New draft"
    When I fill in "This page is French in a draft state." for "Long Text"
    And I select "draft" from "workbench_moderation_state_new"
    And I press "Save"
    # Import a new copy of the English page.
    And I click "Translate"
    And I click "English page"
    And I click "XLIFF"
    And I attach a "fr" translation of this "English" node
    And I press the "Import" button
    And I click "Translate"
    And I click "fr page"
     # Make sure the target has not been published.
    Then I should see "View draft"
