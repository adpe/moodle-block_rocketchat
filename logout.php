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
 * Clears the stored Rocket.Chat token so the block falls back to its login form.
 *
 * @package   block_rocketchat
 * @copyright 2019 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

$id = optional_param('id', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);

$PAGE->set_url('/blocks/rocketchat/logout.php', ['id' => $id]);
$PAGE->set_context(context_system::instance());

$redirect = $id ? new moodle_url('/course/view.php', ['id' => $id]) : new moodle_url('/my');

if ($confirm && confirm_sesskey()) {
    unset_user_preference('local_rocketchat_external_token');
    unset_user_preference('local_rocketchat_external_user');

    redirect($redirect);
}

$PAGE->set_title($SITE->fullname);
$PAGE->set_heading($SITE->fullname);

echo $OUTPUT->header();
echo $OUTPUT->confirm(
    get_string('logoutconfirm'),
    new moodle_url($PAGE->url, ['confirm' => 1, 'sesskey' => sesskey()]),
    $redirect
);
echo $OUTPUT->footer();
