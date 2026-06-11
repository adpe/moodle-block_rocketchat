@block @block_rocketchat
Feature: Logging out of Rocket.Chat from the block
  In order to disconnect my linked Rocket.Chat account
  As a user
  I need to be able to log out and have all stored account data removed

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Jane      | Doe      | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    And the following "blocks" exist:
      | blockname  | contextlevel | reference | pagetypepattern | defaultregion |
      | rocketchat | Course       | C1        | course-view-*   | side-pre      |
    And the following "user preferences" exist:
      | user     | preference                      | value     |
      | student1 | local_rocketchat_external_token | faketoken |
      | student1 | local_rocketchat_external_user  | jane      |

  Scenario: Confirming the logout removes the stored token and username
    Given I log in as "student1"
    When I visit "/blocks/rocketchat/logout.php"
    Then I should see "Do you really want to log out?"
    When I press "Continue"
    Then the user "student1" should not have a "local_rocketchat_external_token" user preference
    And the user "student1" should not have a "local_rocketchat_external_user" user preference
    And I am on the "C1" "Course" page
    And I should see "Please log in with your Rocket.Chat credentials." in the "Rocket.Chat" "block"

  Scenario: Cancelling the logout keeps the stored account data
    Given I log in as "student1"
    When I visit "/blocks/rocketchat/logout.php"
    Then I should see "Do you really want to log out?"
    When I press "Cancel"
    Then the user "student1" should have a "local_rocketchat_external_token" user preference with value "faketoken"
    And the user "student1" should have a "local_rocketchat_external_user" user preference with value "jane"
