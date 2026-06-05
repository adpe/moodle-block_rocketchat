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
 * Rocket.Chat block external function definitions.
 *
 * @package   block_rocketchat
 * @copyright 2026 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
        'block_rocketchat_login' => [
                'classname' => 'block_rocketchat\external\login',
                'description' => 'Logs in to Rocket.Chat and returns the refreshed block content.',
                'type' => 'write',
                'ajax' => true,
        ],
        'block_rocketchat_set_status' => [
                'classname' => 'block_rocketchat\external\set_status',
                'description' => 'Updates the current user\'s Rocket.Chat presence status.',
                'type' => 'write',
                'ajax' => true,
        ],
];
