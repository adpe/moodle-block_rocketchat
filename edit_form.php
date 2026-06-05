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
 * Instance configuration form for the Rocket.Chat block plugin.
 *
 * @package   block_rocketchat
 * @copyright 2026 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Per-instance settings for the Rocket.Chat block.
 */
class block_rocketchat_edit_form extends block_edit_form {
    /**
     * Block specific configuration.
     *
     * @param MoodleQuickForm $mform
     * @throws coding_exception
     */
    protected function specific_definition($mform): void {
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $options = [
            'popup' => get_string('displaymode_popup', 'block_rocketchat'),
            'window' => get_string('displaymode_window', 'block_rocketchat'),
            'newtab' => get_string('displaymode_newtab', 'block_rocketchat'),
        ];

        $mform->addElement('select', 'config_displaymode', get_string('displaymode', 'block_rocketchat'), $options);
        $mform->setDefault('config_displaymode', 'popup');
        $mform->addHelpButton('config_displaymode', 'displaymode', 'block_rocketchat');
    }
}
