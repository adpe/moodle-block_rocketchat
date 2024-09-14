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
 * @package   block_rocketchat
 * @copyright 2019 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_rocketchat;

use coding_exception;
use core\notification;
use Httpful\Exception\ConnectionErrorException;
use Httpful\Mime;
use Httpful\Request;
use local_rocketchat\client;

/**
 * This class handles the login.
 */
class login {
    /**
     * Store if a validation error exists.
     *
     * @var bool
     */
    public bool $error = false;

    /**
     * Login form constructor.
     *
     * @throws coding_exception
     */
    public function __construct() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->login_with_form();
        }
    }

    /**
     * Login with the block login form.
     *
     * @throws coding_exception
     */
    private function login_with_form(): void {
        $username = required_param('rocketchat_username', PARAM_USERNAME);
        $password = required_param('rocketchat_password', PARAM_RAW);

        if (empty($username) || empty($password)) {
            $this->error = true;
        }

        if (empty($username) && empty($password)) {
            notification::info(get_string('credentialserror', 'block_rocketchat'));

            return;
        }

        if (empty($username)) {
            notification::warning(get_string('usernameerror', 'block_rocketchat'));

            return;
        }

        if (empty($password)) {
            notification::warning(get_string('passworderror', 'block_rocketchat'));

            return;
        }

        $this->verify_login($username, $password);
    }

    /**
     * Login by stored user token.
     *
     * @param string $token the user preference auth token
     * @return bool
     * @throws ConnectionErrorException
     */
    public function login_with_token(string $token): bool {
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
        }

        return false;
    }

    /**
     * Verify the login by credentials and store the user token.
     *
     * @param string $username the email or username
     * @param string $password the user password
     * @throws coding_exception
     */
    private function verify_login(string $username, string $password): void {
        $rocketchat = new client();
        $response = $rocketchat->authenticate($username, $password);

        if (is_null($response) || $response->status === 'error') {
            notification::error(get_string('validationerror', 'block_rocketchat'));
            return;
        }

        if (isset($response->status) && $response->status === 'success') {
            set_user_preference('local_rocketchat_external_user', $username);
            set_user_preference('local_rocketchat_external_token', $response->data->authToken);

            notification::success(get_string('validationsuccess', 'block_rocketchat'));
        }
    }
}
