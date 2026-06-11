<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

// NOTE: no MOODLE_INTERNAL check, this is a Behat step definitions file.
// phpcs:disable moodle.Files.MoodleInternal.MoodleInternalNotNeeded

/**
 * Behat step definitions for the Rocket.Chat block.
 *
 * @package   block_rocketchat
 * @category  test
 * @copyright 2026 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Mink\Exception\ExpectationException;

/**
 * Steps to inspect stored user preferences from Behat scenarios.
 *
 * @copyright 2026 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_block_rocketchat extends behat_base {
    /**
     * Fetch a raw preference value straight from the database, bypassing caches.
     *
     * @param string $username the username the preference belongs to
     * @param string $preference the preference name
     * @return string|false the stored value, or false if no record exists
     */
    private function get_preference_from_db(string $username, string $preference): string|false {
        global $DB;

        $userid = $DB->get_field('user', 'id', ['username' => $username], MUST_EXIST);

        return $DB->get_field('user_preferences', 'value', ['userid' => $userid, 'name' => $preference]);
    }

    /**
     * Assert that a user preference no longer exists.
     *
     * @Then /^the user "(?P<username_string>(?:[^"]|\\")*)" should not have a "(?P<preference_string>(?:[^"]|\\")*)" user
     *         preference$/
     *
     * @param string $username the username the preference belongs to
     * @param string $preference the preference name
     * @throws ExpectationException
     */
    public function user_should_not_have_preference(string $username, string $preference): void {
        $value = $this->get_preference_from_db($username, $preference);

        if ($value !== false) {
            throw new ExpectationException(
                "The user preference '{$preference}' for user '{$username}' still exists with value '{$value}'",
                $this->getSession()
            );
        }
    }

    /**
     * Assert that a user preference exists with a given value.
     *
     * @Then /^the user "(?P<username_string>(?:[^"]|\\")*)" should have a "(?P<preference_string>(?:[^"]|\\")*)" user preference
     *         with value "(?P<value_string>(?:[^"]|\\")*)"$/
     *
     * @param string $username the username the preference belongs to
     * @param string $preference the preference name
     * @param string $expected the expected stored value
     * @throws ExpectationException
     */
    public function user_should_have_preference_with_value(string $username, string $preference, string $expected): void {
        $value = $this->get_preference_from_db($username, $preference);

        if ($value === false) {
            throw new ExpectationException(
                "The user preference '{$preference}' for user '{$username}' does not exist",
                $this->getSession()
            );
        }

        if ($value !== $expected) {
            throw new ExpectationException(
                "The user preference '{$preference}' for user '{$username}' is '{$value}', expected '{$expected}'",
                $this->getSession()
            );
        }
    }
}
