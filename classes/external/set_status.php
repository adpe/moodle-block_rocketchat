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
 * External function to update the user's Rocket.Chat presence status.
 *
 * @package   block_rocketchat
 * @copyright 2026 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_rocketchat\external;

use block_rocketchat\rocketchat_client;
use coding_exception;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use core_external\restricted_context_exception;
use dml_exception;
use invalid_parameter_exception;
use moodle_exception;
use require_login_exception;

/**
 * Updates the current user's Rocket.Chat presence status.
 */
class set_status extends external_api {
    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'status' => new external_value(PARAM_ALPHA, 'The new presence status (online, away, busy or offline).'),
        ]);
    }

    /**
     * Update the user's Rocket.Chat presence status.
     *
     * @param string $status the requested status
     * @return array{status: string, success: bool}
     * @throws coding_exception
     * @throws restricted_context_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     * @throws require_login_exception
     */
    public static function execute(string $status): array {
        $params = self::validate_parameters(self::execute_parameters(), ['status' => $status]);

        self::validate_context(\context_system::instance());
        require_login();

        $newstatus = self::set_presence($params['status']);

        return [
            'status' => $newstatus,
            'success' => $newstatus === $params['status'],
        ];
    }

    /**
     * Update the current user's Rocket.Chat presence using their stored token.
     *
     * @param string $status one of {@see rocketchat_client::STATUSES}
     * @return string the resulting status, or an empty string on failure
     * @throws coding_exception
     */
    private static function set_presence(string $status): string {
        $token = get_user_preferences('local_rocketchat_external_token');

        if (empty($token)) {
            return '';
        }

        $client = new rocketchat_client();

        if (!$client->authenticate_with_token($token)) {
            return '';
        }

        return $client->set_status($status) ? $status : '';
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_ALPHA, 'The resulting presence status (empty on failure).'),
            'success' => new external_value(PARAM_BOOL, 'Whether the status was updated successfully.'),
        ]);
    }
}
