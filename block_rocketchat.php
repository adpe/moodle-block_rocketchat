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
 * @package   block_rocketchat
 * @copyright 2019 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_rocketchat\login;
use block_rocketchat\output\block;
use Httpful\Exception\ConnectionErrorException;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/vendor/autoload.php');

define('REST_API_ROOT', '/api/v1/');
define('ROCKET_CHAT_INSTANCE', (new local_rocketchat\client())->get_instance_url());

/**
 * This block simply outputs a list of links to channels.
 */
class block_rocketchat extends block_base {
    /**
     * Initialises the block instance.
     *
     * @return void
     * @throws coding_exception
     */
    public function init(): void {
        $this->title = get_string('defaulttitle', 'block_rocketchat');
    }

    /**
     * Returns the contents.
     *
     * @return mixed contents of block
     * @throws ConnectionErrorException
     * @throws coding_exception
     */
    public function get_content(): mixed {
        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->footer = '';

        $renderer = $this->page->get_renderer('block_rocketchat');
        $block = new block();

        $login = new login();
        $token = get_user_preferences('local_rocketchat_external_token');

        if ($login->error || !$token) {
            $this->content->text = $renderer->render_login($block);

            return $this->content;
        }

        if ($login->login_with_token($token)) {
            $this->content->text = $renderer->render_block($block);
        }

        return $this->content;
    }
}
