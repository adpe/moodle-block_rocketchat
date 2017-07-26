<?php

defined('MOODLE_INTERNAL') || die();

function getPresence($data) {
	
	$info = new \RocketChat\Client;
	$tmp = array(
			'status' => $info->me()->status
	);
	
	array_push($data['user'], $tmp);
	
	return $data;
}

function getChannels($tmpdata) {
	
	$api = new \RocketChat\Client();
	$private = $api->list_groups();
	$public = $api->list_channels();

	foreach($private as $i => $pri) {
		$tmp = array(
				'id' => $private[$i]->id,
				'name' => $private[$i]->name
		);

		array_push($tmpdata['private'], $tmp);
	}

	foreach($public as $i => $pub) {
		$tmp = array(
				'id' => $public[$i]->id,
				'name' => $public[$i]->name
		);

		array_push($tmpdata['public'], $tmp);
	}

	return $tmpdata;
}