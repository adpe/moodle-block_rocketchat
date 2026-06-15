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
 * External function returning the up-to-date block content for the client-side refresh.
 *
 * @package   block_rocketchat
 * @copyright 2026 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_rocketchat\external;

use block_rocketchat\output\block;
use block_rocketchat\rocketchat_client;
use coding_exception;
use context_system;
use core_cache\cache;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use dml_exception;
use invalid_parameter_exception;
use moodle_exception;
use require_login_exception;

/**
 * Re-renders the block for the current user so the JS can refresh unread counts and presence
 * live, and warms the per-user cache the page render reads from.
 */
class get_block extends external_api {
    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'The course the block is displayed in.', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Render the current block view for the logged in user.
     *
     * @param int $courseid the course the block is displayed in
     * @return array{authenticated: bool, unreachable: bool, html: string}
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     * @throws require_login_exception
     */
    public static function execute(int $courseid = 0): array {
        global $PAGE, $USER;

        $params = self::validate_parameters(self::execute_parameters(), ['courseid' => $courseid]);

        self::validate_context(context_system::instance());
        require_login();

        $renderer = $PAGE->get_renderer('block_rocketchat');
        $block = new block();
        $token = get_user_preferences('local_rocketchat_external_token');

        if (empty($token)) {
            return [
                'authenticated' => false,
                'unreachable' => false,
                'html' => $renderer->render_login($block, $params['courseid']),
            ];
        }

        $client = new rocketchat_client();

        if (!$client->authenticate_with_token($token)) {
            return [
                'authenticated' => false,
                'unreachable' => $client->unreachable,
                'html' => $renderer->render_login($block, $params['courseid']),
            ];
        }

        $rooms = block::build_rooms($client);
        cache::make('block_rocketchat', 'blockdata')->set($USER->id, $rooms);

        return [
            'authenticated' => true,
            'unreachable' => false,
            'html' => $renderer->render_block_data($block->compose($rooms, $params['courseid'], 'popup')),
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'authenticated' => new external_value(PARAM_BOOL, 'Whether the stored token is still valid.'),
            'unreachable' => new external_value(PARAM_BOOL, 'Whether Rocket.Chat could not be reached.'),
            'html' => new external_value(PARAM_RAW, 'The rendered block content to display.'),
        ]);
    }
}
