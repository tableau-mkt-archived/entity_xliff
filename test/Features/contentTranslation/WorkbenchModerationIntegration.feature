@api
Feature: Workbench Moderation Feature
  In order to prove that workbench moderation is fully supported
  Site administrators should be able to
  Import and export XLIFF translations while using workbench moderation features

  Background:
    Given I am logged in as a user with the "administer entity xliff,bypass node access,bypass workbench moderation" permission
    And "page" content:
      | title          | field_long_text                               | promote | status |
      | Published page | This page is in a published state.  | 1       | 1      |
    And I am on the homepage
    And I follow "Published page"

  Scenario: Export current revision instead of published revision
    Given I click "New draft"
    When I fill in "This page is in a draft state." for "field_long_text"
    And I select "draft" from "workbench_moderation_state_new"
    And I press "Save"
    Then I should see "Edit draft"
    When I click "XLIFF"
    And I click "Download"
    Then the response should contain "<xliff"
    And the response should contain "<source xml:lang=\"en\">This page is in a draft state.</source>"
    And the response should not contain "<source xml:lang=\"en\">This page is in a published state.</source>"
