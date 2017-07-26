<?php

namespace block_rocketchat;

class login {

	var $loginSession;
	var $credErr;
	var $nameErr;
	var $passErr;
	var $success;
	var $error;

	protected $username;
	protected $password;

	public function __construct() {

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
		$login = $this->verifyLogin();
		
		return $login;
	}

	private function verifyLogin() {
		$this->success = get_string('success', 'block_rocketchat');
		$this->error = get_string('error', 'block_rocketchat');

		$auth = new \RocketChat\User
			(
				$this->username,
				$this->password
			);

		if ($auth->login() == 1) {
			$_SESSION['rocketchat']['username'] = $this->username;
			$_SESSION['rocketchat']['password'] = $this->password;
			$_SESSION['rocketchat']['status'] = true;

			if (!$this->loginSession) {
				\core\notification::success($this->success);
			}
			return true;		
		} else {
			\core\notification::error($this->error);
			return false;
		}
	}
}