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

defined('MOODLE_INTERNAL') || die();

$config = include_once(__DIR__ . '../cfg/config.php');

init_moodle_session();

function init_moodle_session() {
    global $config;

    session_name($config['cookiename']);
    ini_set('session.save_handler', 'files');
    session_save_path($config['dataroot'] . $config['sessions']);
    session_start();
}

$_SESSION['rocketchat'] = array();

$courseid = $_GET['id'];
header("Location: ./../../../course/view.php?id=$courseid");
