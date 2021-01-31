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
 * Rocket.Chat logout handler.
 *
 * @package     block_rocketchat
 * @copyright   2019 Adrian Perez <p.adrian@gmx.ch> {@link https://adrianperez.me}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_rocketchat;

use context_system;
use moodle_url;

require_once($CFG->dirroot . '../../../config.php');

$confirm = optional_param('confirm', 0, PARAM_INT);
$sesskey = optional_param('sesskey', '__notpresent__', PARAM_RAW); // we want not null default to prevent required sesskey warning

$PAGE->set_url('/blocks/rocketchat/classes/logout.php');
$PAGE->set_context(context_system::instance());

$url_components = parse_url($_SERVER['HTTP_REFERER']);
parse_str($url_components['query'], $tmpparams);

$params['id'] = $tmpparams['id'];
$redirect = new moodle_url('/course/view.php', $params);

if ($confirm) {
    unset($_SESSION['rocketchat']);
    unset_user_preference('local_rocketchat_external_token');

    redirect($redirect);
}

if (!confirm_sesskey($sesskey)) {
    $PAGE->set_title($SITE->fullname);
    $PAGE->set_heading($SITE->fullname);

    echo $OUTPUT->header();
    echo $OUTPUT->confirm(get_string('logoutconfirm'), new moodle_url($PAGE->url, array('sesskey'=>sesskey(), 'confirm' => 1)), $redirect);
    echo $OUTPUT->footer();

    die;
}