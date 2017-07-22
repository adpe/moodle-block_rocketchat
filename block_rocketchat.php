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
 
require_once __DIR__.'./vendor/autoload.php';

define('REST_API_ROOT', '/api/v1/');
define('ROCKET_CHAT_INSTANCE', 'http://86.119.34.80:3000');

class block_rocketchat extends block_base {

	public function init() {
		global $PAGE;
		
		$this->title = get_string('defaulttitle', 'block_rocketchat');
		$PAGE->requires->css('/blocks/rocketchat/css/style.css');
		//echo '<link rel="stylesheet" type="text/css" href="'.__DIR__.'/style/dropdown.css" media="screen" />';
		//$PAGE->requires->js('/blocks/rocketchat/js/jquery-3.2.1.min.js');

		//echo '<script>window.setInterval(function(){$login->getPresence();}, 5000);</script>';
		//echo '<script>window.setInterval(function(){$this->refresh_content();}, 5000);</script>';
	}
	
	public function get_content() {
		global $COURSE;
		
		if ($this->content !== null) {
			return $this->content;
		}
		$this->content = new stdClass;
		
		// initialize objects and variables
		$login = new \block_rocketchat\login();
		$channel = new \block_rocketchat\channel();
		$action = new \block_rocketchat\actions();

		// First look if session exists when not create the login form and login manual
		if (isset($_SESSION['rocketchat']['status']) && $_SESSION['rocketchat']['status'] == true) {

			// Login with session credentials to display content
			$login->loginWithSession();
			
			if ($login->getStatus() == 1) {
				$login->getPresence();
				$this->content->text = '<div class="status">Status: '.$login->getUserPresence().'</div>';
				$this->content->text .= '<div><span><i class="fa fa-user-secret fa-2x" aria-hidden="true"></i></span></br>
						Private Channels'.$channel->getPrivateChannels().'</div>';
				$this->content->text .= '<div><span><i class="fa fa-users fa-2x" aria-hidden="true"></i></span></br>
						Public Channels'.$channel->getPublicChannels().'</div>';
				$this->content->text .= $action->initDropdownMenu();
			} else {
				$this->content->text = '<div>Something went wrong with login procedure.';
			}
		} else {
			// Login without session
			$this->content->text = $login->form();
		}
		
		return $this->content;
	}
}