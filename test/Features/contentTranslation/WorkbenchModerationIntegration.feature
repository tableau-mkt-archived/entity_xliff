@api
Feature: Workbench Moderation Feature
  In order to prove that workbench moderation is fully supported
  Site administrators should be able to
  Import and export XLIFF translations while using workbench moderation features

  Background:
    # Make sure a failed test has not left page in draft default
    Given I switch page default moderation To published

    Given I am logged in as a user with the "administer entity xliff,bypass node access,bypass workbench moderation,view moderation history,translate content" permission
    And I am on the homepage
    And I click "Add content"
    And I click "Basic page"
    And I fill in "English page" for "title"
    And I fill in "This page is English in a published state." for "Long Text"
    And I select "English" from "Language"
    And I press the "Save" button

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
    # Make a new node to avoid the registered callback that worbench sets.
    # It will only be exectured after the scenario file exits.
    Given I am on the homepage
    When I click "Add content"
    And I click "Basic page"
    And I fill in "English page two" for "title"
    And I fill in "This page is English in a published state." for "Long Text"
    And I select "English" from "Language"
    And I press the "Save" button
    # We now have a published node source, to which we add a draft.
    And I click "New draft"
    And I fill in "This page is English in a NEW draft state." for "Long Text"
    And I select "draft" from "workbench_moderation_state_new"
    And I press "Save"
    Then I should see "View draft"
    # Add a published French version
    And I click "XLIFF"
    And I attach a "fr" translation of this "English" node
    And I press the "Import" button
    Then I should see the success message containing "Successfully imported"
    # Make sure that the source node still has both published and draft versions.
    And I should see "View draft"
    And I should see "view published"
    # Switch to French.
    When I click "Translate"
    And I click "fr page two"
    # Make sure the new target has been published (per the default).
    Then I should see "View published"
    And I should not see "View draft"
    # Add a new draft revision on top of the current published one.
    When I click "New draft"
    And I fill in "This page is French in a draft." for "Long Text"
    And I select "draft" from "workbench_moderation_state_new"
    And I press "Save"
    # Import a new copy of the English page.
    And I click "Translate"
    And I click "English page two"
    And I click "XLIFF"
    And I attach a "fr" translation of this "English" node
    And I press the "Import" button
    And I click "Translate"
    And I click "fr page two"
    # Make sure that the target node still has both published and draft versions.
    Then I should see "View draft"
    And I should see "view published"
    # Go back, Jack, do it again.
    When I click "Translate"
    And I click "English page two"
    And I click "XLIFF"
    And I attach a "fr" translation of this "English" node
    And I press the "Import" button
    And I click "Translate"
    And I click "fr page two"
    # Make sure that the target node still has both published and draft versions.
    Then I should see "View draft"
    And I should see "view published"

  Scenario: Import Net-new should follow the default moderation state
    # Make a new node to avoid the registered callback that worbench sets.
    # It will only be exectured after the scenario file exits.
    Given I am on the homepage
    And I click "Add content"
    And I click "Basic page"
    And I fill in "English page three" for "title"
    And I fill in "This page is English in a published state." for "Long Text"
    And I select "English" from "Language"
    And I press the "Save" button
    And I click "XLIFF"
    And I attach a "de" translation of this "English" node
    And I press the "Import" button
    And I click "Translate"
    And I click "de page three"
      # Make sure the new target has been published.
    Then I should see "View published"

    # Now check with default draft
    Given I switch page default moderation To draft
    Given I am on the homepage
    And I click "Add content"
    And I click "Basic page"
    And I fill in "English page four" for "title"
    And I fill in "This page is English in a published state." for "Long Text"
    And I select "English" from "Language"
    And I press the "Save" button
    And I click "XLIFF"
    And I attach a "de" translation of this "English" node
    And I press the "Import" button
    And I click "Translate"
    And I click "de page four"
    # Make sure the new target has NOT been published.
    Then I should see "View draft"
    And I should not see "View published"
    # Reset the page default state.
    And I switch page default moderation To published


