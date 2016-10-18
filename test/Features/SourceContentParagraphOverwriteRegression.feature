@api
Feature: Source Content Paragraphs Cross Connect (Regression)
  In order to prove that XLIFF imports do not create cross connected paragraphs source content
  Site administrators should be able to
  Import XLIFF translations through the XLIFF portal for existing content without affecting source content

  Background: Set up a translation set
    Given I am logged in as a user with the "administer entity xliff,translate content,bypass node access,bypass workbench moderation" permissions
    And "page" content:
      | title              | body                    | language | promote |
      | English page title | English page body text. | en       | 1       |
    And I am on the homepage
    And follow "English page title"

  Scenario: Import over existing translation set (focus on paragraphs)
    Given I am viewing a "page" content with paragraphs and the title "English page title"
    When I click "XLIFF"
    And I attach a "fr" translation of this "English" node
    And I press the "Import" button
    Then I should see the success message containing "Successfully imported"
    And there should be no cross connected paragraphs.
    When I click "View published"
    Then I should see "English page title paragraph 1"
    When I click "XLIFF"
    And I attach a "fr" translation of this "English" node
    And I press the "Import" button
    When I click "View published"
    Then I should see "English page title paragraph 1"
    And I should not see "fr page title paragraph 1"
    And there should be no cross connected paragraphs.
