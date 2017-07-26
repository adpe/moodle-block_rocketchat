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
 * @package   block_rocketchat
*/

namespace block_rocketchat\output;

defined('MOODLE_INTERNAL') || die();

require_once __DIR__.'./../../locallib.php';

class block implements \renderable, \templatable {

	/**
	 * Contructor
	 */
	public function __construct() {
	}

	/**
	 * Prepare data for use in a template
	 *
	 * @param \renderer_base $output
	 * @return array Template data
	 */

	public function export_for_template(\renderer_base $output) {
		return false;
	}

	public function export_for_block(\renderer_base $output) {
		
		$data = array(
				'user' => array(),
				'private' => array(),
				'public' =>	array()
		);
		
		$tmpdata = getPresence($data);
		$finaldata = getChannels($tmpdata);
		
		return $finaldata;
	}

	public function export_for_login(\renderer_base $output) {

		$data = [
				'tmpName' => isset($_POST["username"]) ? $_POST["username"] : '',
				'tmpPass' => isset($_POST["password"]) ? $_POST["password"] : '',
		];

		return $data;
	}
}