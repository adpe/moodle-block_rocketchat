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
 * The block for the RocketChat plugin.
 *
 * @package   block_rocketchat
 * @copyright 2019 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use RocketChat\Client;

/**
 * Return user status data.
 *
 * @param  array $data
 * @return array
 */
function block_rocketchat_get_presence(array $data): array {
    $info = new Client();
    $tmp = [
            'status' => $info->me()->status,
    ];

    $data['user'][] = $tmp;

    return $data;
}

/**
 * Return private and public channels data.
 *
 * @param  array $tmpdata
 * @return array
 */
function block_rocketchat_get_channels(array $tmpdata): array {
    $api = new Client();

    if (!empty($private = $api->list_groups())) {
        foreach ($private as $i => $pri) {
            $tmp = [
                    'id' => $private[$i]->id,
                    'name' => $private[$i]->name,
                    'href' => ROCKET_CHAT_INSTANCE . '/group/',
                    'layout' => '?layout=embedded',
            ];

            $tmpdata['private'][] = $tmp;
        }
    }

    if (!empty($public = $api->list_channels())) {
        foreach ($public as $i => $pub) {
            $tmp = [
                    'id' => $public[$i]->id,
                    'name' => $public[$i]->name,
                    'href' => ROCKET_CHAT_INSTANCE . '/channel/',
                    'layout' => '?layout=embedded',
            ];

            $tmpdata['public'][] = $tmp;
        }
    }

    return $tmpdata;
}
