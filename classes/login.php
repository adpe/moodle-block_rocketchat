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
 * Rocket.Chat login handler.
 *
 * @package     block_rocketchat
 * @copyright   2019 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_rocketchat;

use Httpful\Mime;
use Httpful\Request;

defined('MOODLE_INTERNAL') || die();

class login {

    public $loginsession;
    public $credentialserror;
    public $usernameerror;
    public $passworderror;
    public $success;
    public $error;

    protected $username;
    protected $password;

    public function __construct() {

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $this->login_with_form();
        }

    }

    private function login_with_form() {

        $this->credentialserror = get_string('credentialserror', 'block_rocketchat');
        $this->usernameerror = get_string('usernameerror', 'block_rocketchat');
        $this->passworderror = get_string('passworderror', 'block_rocketchat');

        $this->username = $_POST['rocketchat_username'];
        $this->password = $_POST['rocketchat_password'];

        do {
            if (empty($this->username) && empty($this->password)) {
                \core\notification::info($this->credentialserror);
                break;
            }

            if (empty($this->username)) {
                \core\notification::warning($this->usernameerror);
                break;
            }

            if (empty($this->password)) {
                \core\notification::warning($this->passworderror);
                break;
            }

            $this->verify_login();

        } while (0);

    }

    /**
     *
     *
     * @param $token
     * @return bool
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function login_with_token($token) {
        $response = Request::post(ROCKET_CHAT_INSTANCE . REST_API_ROOT . 'login')
            ->body(['resume' => $token], Mime::JSON)
            ->send();

        if ($response->code == 200 && isset($response->body->status) && $response->body->status == 'success') {
            // Save auth token for future requests.
            $tmp = Request::init()
                ->addHeader('X-Auth-Token', $response->body->data->authToken)
                ->addHeader('X-User-Id', $response->body->data->userId);
            Request::ini($tmp);

            return true;
        } else {
            echo ($response->body->message);

            return false;
        }
    }

    public function login_with_session() {

        $this->username = $_SESSION['rocketchat']['username'];
        $this->password = $_SESSION['rocketchat']['password'];
        $this->loginsession = true;
        $login = $this->verify_login();

        return $login;
    }

    private function verify_login() {
        $this->success = get_string('validationsuccess', 'block_rocketchat');
        $this->error = get_string('validationerror', 'block_rocketchat');

        $auth = new \RocketChat\User
        (
                $this->username,
                $this->password
        );

        if ($auth->login() == 1) {
            $_SESSION['rocketchat']['username'] = $this->username;
            $_SESSION['rocketchat']['password'] = $this->password;
            $_SESSION['rocketchat']['status'] = true;

            if (!$this->loginsession) {
                \core\notification::success($this->success);
            }

            return true;
        } else {
            \core\notification::error($this->error);

            return false;
        }
    }
}
