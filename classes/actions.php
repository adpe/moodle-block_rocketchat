<?php

namespace block_rocketchat;

class actions {
	
	function construct__() {
		
	}
	
	public function initDropdownMenu() {
		global $COURSE;
		
		$menu = '<div class="dropdown">
  					<button class="dropbtn">Actions</button>
  					<div class="dropdown-content">
    					<a href="#">Create channel</a>
    					<a href="#">Delete channel</a>
    					<a href="#">Write message</a>
						<a href="#">Refresh</a>
						<a href="./../blocks/rocketchat/classes/logout.php?id='.$COURSE->id.'">Logout</a>
 				 	</div>
				</div>
				';
		
		return $menu;
	}

}
?>