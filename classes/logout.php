<?php
//Load config files, please check paths and nameconvention
$PLUGIN_CFG = include_once(__DIR__.'./../cfg/config.php');

init_moodle_session();

function init_moodle_session() {
	global $PLUGIN_CFG;

	session_name($PLUGIN_CFG['cookiename']);
	ini_set('session.save_handler', 'files');
	session_save_path($PLUGIN_CFG['dataroot'].$PLUGIN_CFG['sessions']);
	session_start();
}

$_SESSION['rocketchat'] = array();

$courseid = $_GET['id'];
header("Location: ./../../../course/view.php?id=$courseid");

?>