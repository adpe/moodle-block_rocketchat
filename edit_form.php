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
 * Edit form for the RocketChat plugin.
 *
 * @package   block_rocketchat
 * @copyright Adrian Perez <adrian.perez@ffhs.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_rocketchat_edit_form extends block_edit_form {

	protected function specific_definition($mform) {

 		// Section header title according to language file.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block_rocketchat'));

        // Add a title for the block
        $mform->addElement('text', 'config_title', get_string('blocktitle', 'block_rocketchat'));
        $mform->setDefault('config_title', 'default value');
        $mform->setType('config_title', PARAM_TEXT);

        // Add a value for the message
        $mform->addElement('text', 'config_text', get_string('blockstring', 'block_rocketchat'));
        $mform->setDefault('config_text', 'default value');
        $mform->setType('config_text', PARAM_RAW);

    }
}