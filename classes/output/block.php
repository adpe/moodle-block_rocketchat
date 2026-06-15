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
 * Rocket.Chat controller to get prepare data.
 *
 * @package   block_rocketchat
 * @copyright 2019 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_rocketchat\output;

use block_rocketchat\rocketchat_client;
use coding_exception;
use moodle_exception;
use moodle_url;
use renderable;
use renderer_base;
use templatable;

/**
 * Class to help display the block.
 */
class block implements renderable, templatable {
    /**
     * Prepare data for use in a template.
     *
     * @param renderer_base $output
     * @return array Template data
     */
    public function export_for_template(renderer_base $output): array {
        return [];
    }

    /**
     * Prepare data to use when logged in.
     *
     * @param renderer_base $output
     * @param rocketchat_client $client an authenticated client
     * @param int $courseid the course the block is displayed in, used for the logout return url
     * @param string $displaymode how channels open (popup, window or newtab)
     * @return array Template data
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function export_for_block(
        renderer_base $output,
        rocketchat_client $client,
        int $courseid,
        string $displaymode
    ): array {
        return $this->compose(self::build_rooms($client), $courseid, $displaymode);
    }

    /**
     * Fetch the user's Rocket.Chat room data from the API.
     *
     * This is the only part of the block view that touches the network, so it is also the part
     * the block caches per user (see {@see \block_rocketchat\block_rocketchat::get_content()}).
     * Unread counts from the user's subscriptions are merged into each room by name.
     *
     * @param rocketchat_client $client an authenticated client
     * @return array{status: string|null, private: array, public: array} the cacheable room data
     */
    public static function build_rooms(rocketchat_client $client): array {
        $baseurl = $client->get_instance_url();
        $status = $client->me()?->status;
        $groups = $client->list_groups();
        $channels = $client->list_channels();
        $unread = $client->get_subscriptions();

        $room = static fn($room, string $href): array => [
            'name' => $room->name,
            'href' => $href,
            'layout' => '?layout=embedded',
            'unread' => $unread[$room->name] ?? 0,
            'hasunread' => ($unread[$room->name] ?? 0) > 0,
        ];

        return [
            'status' => $status,
            'private' => array_map(static fn($group): array => $room($group, $baseurl . '/group/'), $groups),
            'public' => array_map(static fn($channel): array => $room($channel, $baseurl . '/channel/'), $channels),
        ];
    }

    /**
     * Build the full template data for the logged in view from (cacheable) room data.
     *
     * The instance urls are derived from configuration and the stored token, so this needs no
     * network access and can render a cached {@see self::build_rooms()} payload.
     *
     * @param array $rooms the room data from {@see self::build_rooms()}
     * @param int $courseid the course the block is displayed in, used for the logout return url
     * @param string $displaymode how channels open (popup, window or newtab)
     * @return array Template data
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function compose(array $rooms, int $courseid, string $displaymode): array {
        $token = get_user_preferences('local_rocketchat_external_token');
        $baseurl = rocketchat_client::instance_url();
        $status = $rooms['status'] ?? null;

        return [
                'courseid' => $courseid,
                'displaymode' => $displaymode,
                'instanceurl' => $baseurl,
                'loginurl' => $baseurl . '/home?resumeToken=' . $token,
                'logouturl' => new moodle_url('/blocks/rocketchat/logout.php', ['id' => $courseid]),
                'user' => [[
                        'status-online' => $status === 'online',
                        'status-away' => $status === 'away',
                        'status-busy' => $status === 'busy',
                        'status-offline' => $status === 'offline',
                ]],
                'private' => $rooms['private'] ?? [],
                'public' => $rooms['public'] ?? [],
        ];
    }

    /**
     * Prepare data to use when not logged in.
     *
     * @param renderer_base $output
     * @param int $courseid the course the block is displayed in, passed back when logging in
     * @return array
     * @throws coding_exception
     */
    public function export_for_login(renderer_base $output, int $courseid): array {
        return [
                'courseid' => $courseid,
                'sesskey' => sesskey(),
                'tmpusername' => optional_param('rocketchat_username', '', PARAM_USERNAME),
        ];
    }
}
