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
 * @package     block_rocketchat
 * @copyright   2019 Adrian Perez <p.adrian@gmx.ch> {@link https://adrianperez.me}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_rocketchat\output;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . './../../locallib.php');

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
        return [];
    }

    public function export_for_block(\renderer_base $output) {
        global $COURSE;

        $data = [
            'courseid' => $COURSE->id,
            'user' => [],
            'private' => [],
            'public' => []
        ];

        $tmpdata = get_presence($data);
        $finaldata = get_channels($tmpdata);

        return $finaldata;
    }

    public function export_for_login(\renderer_base $output) {

        $data = [
            'tmpusername' => isset($_POST["username"]) ? $_POST["username"] : '',
            'tmppassword' => isset($_POST["password"]) ? $_POST["password"] : '',
        ];

        return $data;
    }
}
