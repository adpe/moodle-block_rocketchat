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

/**
 * External function to log in to Rocket.Chat and return the refreshed block content.
 *
 * @package   block_rocketchat
 * @copyright 2026 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_rocketchat\external;

use block_rocketchat\login as login_handler;
use block_rocketchat\output\block;
use block_rocketchat\rocketchat_client;
use coding_exception;
use context_system;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use dml_exception;
use invalid_parameter_exception;
use moodle_exception;
use require_login_exception;

/**
 * Authenticates against Rocket.Chat and returns the block content to swap in without a page reload.
 */
class login extends external_api {
    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'username' => new external_value(PARAM_USERNAME, 'The Rocket.Chat username or email.'),
            'password' => new external_value(PARAM_RAW, 'The Rocket.Chat password.'),
            'courseid' => new external_value(PARAM_INT, 'The course the block is displayed in.', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Attempt to log in and render the appropriate block view.
     *
     * @param string $username the Rocket.Chat username or email
     * @param string $password the Rocket.Chat password
     * @param int $courseid the course the block is displayed in
     * @return array{authenticated: bool, html: string}
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     * @throws require_login_exception
     */
    public static function execute(string $username, string $password, int $courseid = 0): array {
        global $PAGE;

        $params = self::validate_parameters(self::execute_parameters(), [
            'username' => $username,
            'password' => $password,
            'courseid' => $courseid,
        ]);

        self::validate_context(context_system::instance());
        require_login();

        $renderer = $PAGE->get_renderer('block_rocketchat');
        $block = new block();

        $login = new login_handler();
        $login->attempt($params['username'], $params['password']);

        $token = get_user_preferences('local_rocketchat_external_token');
        $client = new rocketchat_client();

        if (!$login->error && $token && $client->authenticate_with_token($token)) {
            return [
                'authenticated' => true,
                'html' => $renderer->render_block($block, $client, $params['courseid'], $login->messages),
            ];
        }

        return [
            'authenticated' => false,
            'html' => $renderer->render_login($block, $params['courseid'], $login->messages),
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'authenticated' => new external_value(PARAM_BOOL, 'Whether the login succeeded.'),
            'html' => new external_value(PARAM_RAW, 'The rendered block content to display.'),
        ]);
    }
}
