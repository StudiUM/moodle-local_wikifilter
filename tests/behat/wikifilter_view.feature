@mod @mod_wikifilter @javascript
Feature: view wikifilter
  As a teacher 
  I can see wiki teacher pages
  As a student
  I can see wiki student pages

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email       |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category | format |
      | Course 1 | C1        | 0        | topics |
    And the following "course enrolments" exist:
      | user     | course | role           | 
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activity" exists:
      | course         | C1             |
      | idnumber       | wiki1          |
      | activity       | wiki           |
      | name           | wiki 1         |
      | firstpagetitle | First page     |
      | wikimode       | collaborative  |
    And I log in as "admin"
    And I am on the "wiki1" "Activity" page
    # Create first page.
    And I press "Create page"
    And I set the following fields to these values:
      | HTML format | [[page 1]] [[page 2]] [[page 3]] [[page 4]]|
      | Tags        | simulation|
    And I press "Save"
    # Create page 1.
    And I follow "page 1"
    And I press "Create page"
    And I set the following fields to these values:
      | HTML format | page 1 content    |
      | Tags        | zoom, simulation |
    And I press "Save"
    # Create page 2.
    And I follow "wiki 1"
    And I follow "page 2"
    And I press "Create page"
    And I set the following fields to these values:
      | HTML format | page 2 content |
      | Tags        | exam, room     | 
    And I press "Save"
    # Create page 3.
    And I follow "wiki 1"
    And I follow "page 3"
    And I press "Create page"
    And I set the following fields to these values:
      | HTML format | page 3 content    |
      | Tags        | evaluation, activity | 
    And I press "Save"
    # Create page 4.
    And I follow "wiki 1"
    And I follow "page 4"
    And I press "Create page"
    And I set the following fields to these values:
      | HTML format | page 4 content    |
      | Tags        | conception, validation | 
    And I press "Save"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    # Create wikifilter.
    And I add a "Wiki filter" to section "1" and I fill the form with:
      | Name        | wikifilter 1             |
      | Description | wikifilter 1 description |
    And I open "wikifilter 1" actions menu
    And I click on "Edit settings" "link" in the "wikifilter 1" activity
    And I expand all fieldsets
    # Add a teacher association.
    When I press "addassociationbtn"
    Then I should see "Add an association"
    And I set the field "id_role" to "Teacher"
    And I open the autocomplete suggestions list
    And I click on "simulation" item in the autocomplete list
    And I click on "evaluation" item in the autocomplete list
    And I click on "conception" item in the autocomplete list
    When I press "addassociation-modal"
    Then the following should exist in the "id_associations_table" table:
    | Role    | Tags       |
    | Teacher | simulation |
    | Teacher | evaluation |
    | Teacher | conception |
    # Add a student association.
    When I press "addassociationbtn"
    Then I should see "Add an association"
    And I set the field "id_role" to "Student"
    And I open the autocomplete suggestions list
    And I click on "simulation" item in the autocomplete list
    When I press "addassociation-modal"
    Then the following should exist in the "id_associations_table" table:
    | Role    | Tags       |
    | Student | simulation |
    # save associations.
    And I press "Save and display"
    And I log out

  Scenario: view wikifilter as teacher1
    Given I am logged in as "teacher1"
    And I am on "Course 1" course homepage
    When I follow "wikifilter 1"
    Then "page 1" "link" should be visible 
    And "page 2" "link" should not be visible 
    And "page 3" "link" should be visible
    And "page 4" "link" should be visible

  Scenario: view wikifilter as student1
    Given I am logged in as "student1"
    And I am on "Course 1" course homepage
    When I follow "wikifilter 1"
    Then "page 1" "link" should be visible 
    And "page 2" "link" should not be visible
    And "page 3" "link" should not be visible
    And "page 4" "link" should not be visible
