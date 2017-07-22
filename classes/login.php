<?php

namespace block_rocketchat;

class login {

	var $loginStatus;
	var $loginSession;
	var $userPresence;
	var $credErr;
	var $nameErr;
	var $passErr;
	var $success;
	var $error;

	protected $username;
	protected $password;

	function __construct() {

		if ($_SERVER["REQUEST_METHOD"] == "POST") {
			$this->loginWithForm();
		}

	}

	private function loginWithForm() {

		$this->credErr = get_string('credErr', 'block_rocketchat');
		$this->nameErr = get_string('nameErr', 'block_rocketchat');
		$this->passErr = get_string('passErr', 'block_rocketchat');

		do {
			if (empty($_POST["username"]) && empty($_POST["password"])) {
				\core\notification::info($this->credErr);
				break;
			}

			if (empty($_POST["username"])) {
				\core\notification::warning($this->nameErr);
				break;
			}

			if (empty($_POST["password"])) {
				\core\notification::warning($this->passErr);
				break;
			}

			$this->username = $_POST['username'];
			$this->password = $_POST['password'];
			$this->verifyLogin();

		} while(0);

	}

	public function loginWithSession() {

		$this->username = $_SESSION['rocketchat']['username'];
		$this->password = $_SESSION['rocketchat']['password'];
		$this->loginSession = true;
		$this->verifyLogin();

	}

	private function verifyLogin() {
		$this->success = get_string('success', 'block_rocketchat');
		$this->error = get_string('error', 'block_rocketchat');

		$auth = new \RocketChat\User
			(
				$this->username,
				$this->password
			);

		$this->loginStatus = $auth->login();

		if ($this->loginStatus == 1) {
			$_SESSION['rocketchat']['username'] = $this->username;
			$_SESSION['rocketchat']['password'] = $this->password;
			$_SESSION['rocketchat']['status'] = true;

			$this->userId = $auth->id;

			if (!$this->loginSession) {
				\core\notification::success($this->success);
			}

		} else {
			\core\notification::error($this->error);
		}

	}

	public function form() {

		$intro = get_string('intro', 'block_rocketchat');
		$username = get_string('username', 'block_rocketchat');
		$password = get_string('password', 'block_rocketchat');
		$submit = get_string('submit', 'block_rocketchat');

		$tmpName = isset($_POST["username"]) ? $_POST["username"] : '';
		$tmpPass = isset($_POST["password"]) ? $_POST["password"] : '';

		$form = '
	    		'.$intro.'<br/>

	 			<form id="rocketchat_form_login" method="post" action="">
	    			<div class="field-group">
	    				<div><label for="username">'.$username.'</label></div>
						<div><input class="textfield" type="text" name="username" value="'.$tmpName.'"></div>
					</div>
					<div class="field-group">
						<div><label for="password">'.$password.'</label></div>
	    				<div><input class="textfield" type="password" name="password" value="'.$tmpPass.'"></div>
					</div>
	    			<div class="field-group">
	    				<div><input class="loginbtn" type="submit" name="submit" value="'.$submit.'"></div>
					</div>
	    		</form>
				';
			return $form;
	}
	
	public function getPresence() {
		$info = new \RocketChat\Client;
		$this->userPresence = $info->me()->status;
	}
	
	public function getUserPresence() {
		return $this->userPresence;
	}
	
	public function getStatus() {
		return $this->loginStatus;
	}
	
}