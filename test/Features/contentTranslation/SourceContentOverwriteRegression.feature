@api
Feature: Source Content Overrrite (Regression)
  In order to prove that XLIFF imports do not overwrite source content
  Site administrators should be able to
  Import XLIFF translations through the XLIFF portal for existing content without affecting source content

  Background: Set up a translation set
  Given I am logged in as a user with the "administer entity xliff,bypass node access,bypass workbench moderation,view moderation history,translate content,administer nodes" permission

    And I am on the homepage
    And I click "Add content"
    And I click "Basic page"
    And I fill in "English page title" for "title"
    And I fill in "English page body text." for "Long Text"
    And I select "English" from "Language"
    And I press the "Save" button

    And I click "Translate"
    And I click "add translation"
    And I fill in "French page title" for "title"
    And I press "Save"

  Scenario: Import over existing translation set (focus on field collections)
    When I click "Translate"
    And I click "English page title"
    Given this node has 1 field collection
    And I click "XLIFF"
    And I attach a "fr" translation of this "English" node
    And I press the "Import" button
#Then I Expect You To Die
    Then I should see the success message containing "Successfully imported"
    When I click "View published"
    Then I should see "English page title field collection 1"
    When I click "XLIFF"
    And I attach a "fr" translation of this "English" node
    And I press the "Import" button
    Then I should see the success message containing "Successfully imported"
    When I click "View published"
    Then I should see "English page title field collection 1"
    And I should not see "fr page title field collection 1"

  Scenario: Import over existing translation set (focus on entity references)

    Given I am on the homepage
    And I click "Add content"
    And I click "Basic page"
    And I fill in "English regression child" for "title"
    And I fill in "English child body text." for "Long Text"
    And I select "English" from "Language"
    And I press the "Save" button
    And I am on the homepage
    And I click "Add content"
    And I click "Basic page"
    And I fill in "English page title" for "title"
    And I fill in "English page body text." for "Long Text"
    And I select "English" from "Language"
    And I press the "Save" button

    And this node references the "English regression child" node
    When I click "XLIFF"
    And I attach a "fr" translation of this "English" node
    And I press the "Import" button
    Then I should see the success message containing "Successfully imported"
    When I click "View published"
    Then I should see "English child body text."
    When I click "XLIFF"
    And I attach a "fr" translation of this "English" node
    And I press the "Import" button
    Then I should see the success message containing "Successfully imported"
    When I click "View published"
    And I should see "English regression child"
    And I should not see "fr regression child"

  Scenario: Import over existing translation set (focus on paragraphs)
    Given I am viewing a "page" content with paragraphs and the title "English page title paragraphs"
    When I click "XLIFF"
    And I attach a "fr" translation of this "English" node
    And I press the "Import" button
    Then I should see the success message containing "Successfully imported"
    When I click "View published"
    Then I should see "English page title paragraphs paragraph 1"
    When I click "XLIFF"
    And I attach a "fr" translation of this "English" node
    And I press the "Import" button
    When I click "View published"
    Then I should see "English page title paragraphs paragraph 1"
    And I should not see "fr page title paragraphs paragraph 1"