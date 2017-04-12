@api
Feature: Unsuitable Translation Target (Regression)
  In order to prove that entities with previously non-existent embedded entities are translatable
  Site administrators should be able to
  Import XLIFF translations through the XLIFF portal UI for such content

  Background: Set up a translation set with no embedded entities.
    Given I am logged in as a user with the "administer entity xliff,bypass node access,bypass workbench moderation,view moderation history,translate content" permission
    And I am on the homepage
    And I click "Add content"
    And I click "Basic page"
    And I fill in "English page title" for "title"
    And I fill in "This page is English in a published state." for "Long Text"
    And I select "English" from "Language"
    And I press the "Save" button
    And I click "Translate"
    And I click "add translation"
    And I fill in "French page title" for "title"
    And I press "Save"

  Scenario: Field Collection values added after initial translation
    When I click "English"
    And I click "New draft"
    And I fill in "English field collection" for "field_field_collection[und][0][field_long_text][und][0][value]"
    And I press "Save"
    And I click "XLIFF"
    And I attach a "fr" translation of this "English" node
    And I press the "Import" button
    Then I should see the success message containing "Successfully imported"
    When I click "View published"
    And I click "Français"
    Then I should see "fr page title"

  Scenario: Field collection cardinality differs between source and target
    When I click "English"
    And I click "New draft"
    And I fill in "English page title field collection 0" for "field_field_collection[und][0][field_long_text][und][0][value]"
    And I press "Save"
    And I click "XLIFF"
    And I attach a "fr" translation of this "English" node
    And I press the "Import" button
    Then I should see the success message containing "Successfully imported"
    When this node has 2 additional field collections
    And I click "View published"
    Then I should see "English page title field collection 2"
    When I click "XLIFF"
    And I attach a "fr" translation of this "English" node
    And I press the "Import" button
    Then I should see the success message containing "Successfully imported"
    When I click "View published"
    And I click "Français"
   # These tests will fail until we reslve https://github.com/tableau-mkt/entity_xliff/issues/134
   # Then I should see "fr page title field collection 0"
   # And I should see "fr page title field collection 2"

  Scenario: Referenced entity added after initial translation
    And I am on the homepage
    And I click "Add content"
    And I click "Basic page"
    And I fill in "English regression child" for "title"
    And I fill in "Tnglish child body text." for "Long Text"
    And I select "English" from "Language"
    And I press the "Save" button

    And I am on the homepage
    And I click "Add content"
    And I click "Basic page"
    And I fill in "English page title" for "title"
    And I fill in "This page is English in a published state." for "Long Text"
    And I select "English" from "Language"
    And I press the "Save" button

    And this node references the "English regression child" node
    When I click "XLIFF"
    And I attach a "fr" translation of this "English" node
    And I press the "Import" button
    Then I should see the success message containing "Successfully imported"
    When I click "View published"
    And I click "Français"
    Then I should see "fr page title"

  Scenario: Paragraphs added after initial translation
    Given I am viewing a "page" content with paragraphs and the title "English page title"
    When I click "XLIFF"
    And I attach a "fr" translation of this "English" node
    And I press the "Import" button
    Then I should see the success message containing "Successfully imported"
    When I click "View published"
    And I click "Français"
    Then I should see "fr page title"
