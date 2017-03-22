@api
Feature: Content Translation of Node and Field Collection Entities
  In order to prove that nodes can be translated via Content Translation
  Site administrators should be able to
  Import and export XLIFF translations through the XLIFF portal UI for a node

  Background:
    Given I am logged in as a user with the "administer entity xliff,translate content,bypass node access,bypass workbench moderation" permission

  Scenario: Access XLIFF portal local task
    Given I am viewing a "page" content with the title "English page title"
    Then I should see the link "XLIFF"
    When I click "XLIFF"
    Then the url should match "node/\d+/xliff"
    And I should see "Export as XLIFF"
    And I should see "Import from XLIFF"

  Scenario: Export XLIFF through portal
    Given I am viewing a "page" content with the title "English page title"
    And I click "XLIFF"
    When I click "Download"
    Then the response should contain "<xliff"
    And the response should contain "<source xml:lang=\"en\">English page title</source>"

  Scenario: Import XLIFF through portal
    Given "page" content:
    | title              | field_long_text                    | promote | uid | language |
    | English page title | English page body text. | 1       | 1   | en       |
    When I am on the homepage
    And follow "English page title"
    When I attach a "fr" translation of this "English" node
    And I press the "Import" button
    Then I should see the success message containing "Successfully imported"
    And there should be no corrupt translation sets.
    When I click "View published"
    And I click "Français"
    Then I should see the heading "fr page title"
    And I should see "fr page body text."
    # Re-import to test the pre-existing/non-initialization flow.
    When I click "Translate"
    And I click "English page title"
    And I click "XLIFF"
    And I attach a "fr" translation of this "English" node
    And I press the "Import" button
    Then I should see the success message containing "Successfully imported"
    And there should be no corrupt translation sets.
    When I click "View published"
    And I click "Français"
    Then I should see the heading "fr page title"
    And I should see "fr page body text."
    When I click "Translate"
    And I click "English"
    Then I should see the heading "English page title"
    And I should see "English page body text."


    # Not supported in combination with Workbench moderation
  Scenario: Import collection-based XLIFF through portal
    Given I am viewing a 3 complex "page" content with the title "Complex English page title"
    When I click "XLIFF"
    And I attach a "fr" translation of this "English" node
    And I press the "Import" button
    Then I should see the success message containing "Successfully imported"
    When I click "View published"
    Then I should not see the heading "Complex fr page title"
    And I should not see "Complex fr page title field collection 1"
    And I should not see "Complex fr page title field collection 2"
    And I should not see "Complex fr page title field collection 3"
    When I click "Translate"
    When I click "Complex fr page title"
    Then I should see the heading "Complex fr page title"
    And I should see "Complex fr page title field collection 1"
    And I should see "Complex fr page title field collection 2"
    And I should see "Complex fr page title field collection 3"
    And there should be no corrupt translation sets.

  Scenario: Import paragraph-based XLIFF through portal
    Given I am viewing a "page" content with paragraphs and the title "Paragraph English page title"
    When I click "XLIFF"
    And I attach a "fr" translation of this "English" node
    And I press the "Import" button
    Then I should see the success message containing "Successfully imported"
    And there should be no corrupt translation sets.
    When I click "View published"
    Then I should not see the heading "Paragraph fr page title"
    And I should not see "Paragraph fr page title paragraph 1"
    And I should not see "Paragraph fr page title paragraph 2"
    When I click "Français"
    Then I should see the heading "Paragraph fr page title"
    And I should see "Paragraph fr page title paragraph 1"
    And I should see "Paragraph fr page title paragraph 2"

    # Re-import to test the pre-existing/non-initialization flow.
    When I click "Translate"
    And I click "Paragraph English page title"
    And I click "XLIFF"
    And I attach a "fr" translation of this "English" node
    And I press the "Import" button
    Then I should see the success message containing "Successfully imported"
    And there should be no corrupt translation sets.
    When I click "View published"
    Then I should not see the heading "Paragraph fr page title"
    And I should not see "Paragraph fr page title paragraph 1"
    And I should not see "Paragraph fr page title paragraph 2"
    When I click "Français"
    Then I should see the heading "Paragraph fr page title"
    And I should see "Paragraph fr page title paragraph 1"
    And I should see "Paragraph fr page title paragraph 2"

  Scenario: No access to XLIFF portal local task without permissions
    Given I am not logged in
    When I am logged in as a user with the "authenticated user" role
    And I am viewing a "page" content with the title "English page title"
    Then I should not see the link "XLIFF"
