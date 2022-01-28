@mod @mod_wikifilter @javascript
Feature: Wikifilter settings
  As an admin
  I need to configure a wikifilter instance

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | format |
      | Course 1 | C1        | 0        | topics |
    And the following "activities" exist:
    | course | idnumber | activity | name   | firstpagetitle | wikimode      |
    | C1     | wiki1    | wiki     | wiki 1 | First page     | collaborative |
    | C1     | wiki2    | wiki     | wiki 2 | First page     | collaborative |
    And I log in as "admin"
    # Create wiki 1 first page with tags.
    When I am on the "wiki1" "Activity" page
    Then I press "Create page"
    And I set the following fields to these values:
      | HTML format | Collaborative teacher1 page [[new page]] |
      | Tags | zoom, simulation, patient |
    And I press "Save"
    # Create wiki 2 first page with tags.
    When I am on the "wiki2" "Activity" page
    Then I press "Create page"
    And I set the following fields to these values:
      | HTML format | Collaborative teacher1 page [[new page]] |
      | Tags | exam, conception, validation |
    And I press "Save"
    When I am on "Course 1" course homepage
    Then I turn editing mode on
    # Create wikifilter.
    And I add a "Wiki filter" to section "1" and I fill the form with:
      | Name | wikifilter 1 |
      | Description | wikifilter 1 description |

  @javascript
  Scenario: Set wikifilter wiki
    Given I am on "Course 1" course homepage
    And I open "wikifilter 1" actions menu
    And I click on "Edit settings" "link" in the "wikifilter 1" activity
    And I expand all fieldsets
    When I set the field "id_wiki" to "wiki 2"
    And I press "addassociationbtn"
    And I open the autocomplete suggestions list
    Then I should see "exam" in the ".form-autocomplete-suggestions" "css_element"
    And I should see "conception" in the ".form-autocomplete-suggestions" "css_element"
    And I should see "validation" in the ".form-autocomplete-suggestions" "css_element"
    And I press "Cancel"
    When I set the field "id_wiki" to "wiki 1"
    And I press "addassociationbtn"
    And I open the autocomplete suggestions list
    Then I should see "zoom" in the ".form-autocomplete-suggestions" "css_element"
    And I should see "simulation" in the ".form-autocomplete-suggestions" "css_element"
    And I should see "patient" in the ".form-autocomplete-suggestions" "css_element"
    And I press "Cancel"

  @javascript
  Scenario: Set wikifilter associations
    Given I am on "Course 1" course homepage
    And I open "wikifilter 1" actions menu
    And I click on "Edit settings" "link" in the "wikifilter 1" activity
    And I expand all fieldsets
    # Add an association.
    When I press "addassociationbtn"
    Then I should see "Add an association"
    And I set the field "id_role" to "Student"
    And I open the autocomplete suggestions list
    And I should see "zoom" in the ".form-autocomplete-suggestions" "css_element"
    And I should see "simulation" in the ".form-autocomplete-suggestions" "css_element"
    And I should see "patient" in the ".form-autocomplete-suggestions" "css_element"
    And I click on "zoom" item in the autocomplete list
    And I click on "simulation" item in the autocomplete list
    When I press "addassociation-modal"
    Then the following should exist in the "id_associations_table" table:
    | Role    | Tags       |
    | Student | zoom       |
    | Student | simulation |
    # Modify an association.
    When I click on "Modify" "button" in the "Student" "table_row"
    And I should see "Modify an association"
    And I open the autocomplete suggestions list
    And I should see "zoom" in the ".form-autocomplete-selection" "css_element"
    And I should see "simulation" in the ".form-autocomplete-selection" "css_element"
    And I click on "patient" item in the autocomplete list
    When I press "modifyassociation-modal"
    Then the following should exist in the "id_associations_table" table:
    | Role    | Tags       |
    | Student | zoom       |
    | Student | simulation |
    | Student | patient    |
    # Delete an association.
    When I click on "Delete" "button" in the "Student" "table_row"
    Then I should see "No data available"
    And I press "Save and display"
