<?php

namespace block_rocketchat;

class channel {
	
	protected $api;
	
	function __construct() {
		$this->api = new \RocketChat\Client();
	}
	
	public function getPrivateChannels() {

		$output = '<div class="privatelist"><ul>';
		$private = $this->api->list_groups();
		//var_dump($private);
		foreach($private as $i => $pri) {
			$output .= '<li><a href="'.$private[$i]->id.'">'.$private[$i]->name.'</a></li>';
		}

		$output .= '</ul></div>';
		return $output;
	}
	
	public function getPublicChannels() {
		global $api;
		
		$output = '<div class="publiclist"><ul>';
		$public = $this->api->list_channels();
		//var_dump($public);
		foreach($public as $i => $pub) {
    		$output .= '<li><a href="'.$public[$i]->id.'">'.$public[$i]->name.'</a></li>';
		}
		
		$output .= '</ul></div>';	
		return $output;
	}

}