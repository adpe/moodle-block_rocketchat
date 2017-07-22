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
		$this->title = get_string('defaulttitle', 'block_rocketchat');
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
		
		// First look if session exists when not create the login form and login manual
		if (isset($_SESSION['rocketchat']['status']) && $_SESSION['rocketchat']['status'] == true) {
			
			// Login with session credentials to display content
			$login->loginWithSession();
			
			if ($login->getStatus() == 1) {
				$this->content->text = '<div>Status: '.$login->getStatus().'</div>';
				$this->content->text .= '<div>Private Channels: '.$channel->getPrivateChannels().'</div>';
				$this->content->text .= '<div>Public Channels: '.$channel->getPublicChannels().'</div>';
				$this->content->text .= '<div><a href="./../blocks/rocketchat/classes/logout.php?id='.$COURSE->id.'">Logout</a></div>';
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