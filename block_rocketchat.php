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
 * @copyright Adrian Perez <adrian.perez@ffhs.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once __DIR__.'./vendor/autoload.php';

define('REST_API_ROOT', '/api/v1/');
define('ROCKET_CHAT_INSTANCE', 'http://86.119.34.80:3000');

class block_rocketchat extends block_base {

	public function init() {
		global $CFG, $PAGE;

		$this->title = get_string('defaulttitle', 'block_rocketchat');
		$PAGE->requires->css('/blocks/rocketchat/css/style.css');

		/**
		 * Development code
		 *
		 * echo '<script>window.setInterval(function(){$this->refresh_content();}, 5000);</script>';
		 * $PAGE->requires->js(new moodle_url($CFG->wwwroot . '/blocks/rocketchat/js/mustache.js'));
		 * $PAGE->requires->js(new moodle_url($CFG->wwwroot . '/blocks/rocketchat/js/jquery-3.2.1.min.js'));
		 * $PAGE->requires->yui_module('moodle-block_rocketchat-module', 'M.block_rocketchat.module.init', null);
		 *
		 */
	}

	public function get_content() {
		global $COURSE;

		if ($this->content !== null) {
			return $this->content;
		}
		$this->content = new stdClass;

		$renderer = $this->page->get_renderer('block_rocketchat');
		$block = new \block_rocketchat\output\block();

		//Initialize objects and variables
		$login = new \block_rocketchat\login();

		//First look if session exists when not create the login form and login manual
		if (isset($_SESSION['rocketchat']['status']) && $_SESSION['rocketchat']['status'] == true) {
			//Login with session credentials to display content
			$login->loginWithSession();

			if ($login->getStatus() == 1) {
                $this->content->text = $renderer->render_block($block, $login);
			} else {
				$this->content->text = '<div>Something went wrong with login procedure.';
			}
		} else {
			//Login without session
            $this->content->text = $renderer->render_login($block);

		}
		
		$this->content->footer = '';
		return $this->content;
	}
}