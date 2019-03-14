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
 * The main block for the Rocket.Chat block plugin.
 *
 * @package     block_rocketchat
 * @copyright   2019 Adrian Perez <p.adrian@gmx.ch> {@link https://adrianperez.me}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/vendor/autoload.php');

define('REST_API_ROOT', '/api/v1/');
define('ROCKET_CHAT_INSTANCE', get_config('local_rocketchat', 'host') . ':' . get_config('local_rocketchat', 'port'));
define('ROCKET_CHAT_PROTOCOL', get_config('local_rocketchat', 'protocol') == 0 ? 'https' : 'http');

class block_rocketchat extends block_base {

    public function init() {
        global $CFG, $PAGE;

        $this->title = get_string('defaulttitle', 'block_rocketchat');
    }

    public function get_content() {
        global $COURSE;

        if ($this->content !== null) {
            return $this->content;
        }
        $this->content = new stdClass;

        $renderer = $this->page->get_renderer('block_rocketchat');
        $block = new \block_rocketchat\output\block();

        // Initialize objects and variables.
        $login = new \block_rocketchat\login();

        // First look if session exists when not create the login form and login manual.
        if (isset($_SESSION['rocketchat']['status']) && $_SESSION['rocketchat']['status'] == true) {
            // Login with session credentials to display content.
            $status = $login->login_with_session();

            if ($status == true) {
                $this->content->text = $renderer->render_block($block, $login);
            } else {
                $this->content->text = get_string('loginerror', 'block_rocketchat');;
            }
        } else {
            // Login without session.
            $this->content->text = $renderer->render_login($block);

        }

        $this->content->footer = '';
        return $this->content;
    }
}
