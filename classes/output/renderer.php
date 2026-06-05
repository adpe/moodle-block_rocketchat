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
 * Rocket.Chat renderer to pass data to template.
 *
 * @package   block_rocketchat
 * @copyright 2019 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_rocketchat\output;

use block_rocketchat\rocketchat_client;
use moodle_exception;
use plugin_renderer_base;
use templatable;

/**
 * Class renderer for rendering block pages.
 */
class renderer extends plugin_renderer_base {
    /**
     * Render block channels page.
     *
     * The display mode is only consumed client-side (as a data attribute); the dynamic-login web service
     * renders without it and the JS carries it across the swap, hence the default.
     *
     * @param  templatable $block
     * @param  rocketchat_client $client an authenticated client
     * @param  int $courseid the course the block is displayed in
     * @param  array $notifications feedback messages to render inside the block
     * @param  string $displaymode how channels open (popup, window or newtab)
     * @return string|boolean
     * @throws moodle_exception
     */
    public function render_block(
        templatable $block,
        rocketchat_client $client,
        int $courseid,
        array $notifications = [],
        string $displaymode = 'popup'
    ): bool|string {
        $data = $block->export_for_block($this, $client, $courseid, $displaymode);
        $data['notifications'] = $notifications;

        return $this->render_from_template('block_rocketchat/block', $data);
    }

    /**
     * Render block login page.
     *
     * @param  templatable $block
     * @param  int $courseid the course the block is displayed in
     * @param  array $notifications feedback messages to render inside the block
     * @param  string $displaymode how channels open (popup, window or newtab)
     * @return string|boolean
     * @throws moodle_exception
     */
    public function render_login(
        templatable $block,
        int $courseid,
        array $notifications = [],
        string $displaymode = 'popup'
    ): bool|string {
        $data = $block->export_for_login($this, $courseid);
        $data['displaymode'] = $displaymode;
        $data['notifications'] = $notifications;

        return $this->render_from_template('block_rocketchat/login', $data);
    }
}
