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

	class block_rocketchat extends block_base {

		//Sets the title for the new added block
		public function init() {
			$this->title = get_string('defaulttitle', 'block_rocketchat');
		}

		//This will be added to the content when it's the first time created
		public function get_content() {
			if ($this->content !== null) {
				return $this->content;
			}

			$this->content = new stdClass;

			if (! empty($this->config->text)) {
				$this->content->text = $this->config->text;
				$this->content->text .= "bliablabla";
			} else {
				$this->content->text = "no text added";
			}

			return $this->content;
		}

		//This part is to change the block values with the given one's in the configuration block.
		public function specialization() {
			if (isset($this->config)) {
				if (empty($this->config->title)) {
					$this->title = get_string('defaulttitle', 'block_rocketchat');
				} else {
					$this->title = $this->config->title;
				}

				if (! empty($this->config->text)) {
					$this->content->text = $this->config->text;
					$this->content->text .= "bliablabla";
				} else {
					$this->content->text = "no text added";
				}
			}
		}

		//This allows to add the plugin multiple times, normally you can add it only once
		public function instance_allow_multiple() {
			return true;
		}
	}