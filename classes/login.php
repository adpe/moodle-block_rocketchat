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
     * Feedback messages to render inside the block.
     *
     * Each entry has a Bootstrap alert `type` (success, danger, warning, info) and a `message`.
     *
     * @var array<int, array{type: string, message: string}>
     */
    public array $messages = [];

    /**
     * Login form constructor.
     *
     * Only acts on a genuine submission of this block's login form, identified by the
     * {@see rocketchat_login} marker, and protected against CSRF by the session key.
     *
     * @throws coding_exception
     */
    public function __construct() {
        if (optional_param('rocketchat_login', 0, PARAM_BOOL) && confirm_sesskey()) {
            $username = optional_param('rocketchat_username', '', PARAM_USERNAME);
            $password = optional_param('rocketchat_password', '', PARAM_RAW);

            $this->attempt($username, $password);
        }
    }

    /**
     * Validate the supplied credentials and, when valid, log in against Rocket.Chat.
     *
     * Populates {@see self::$error} and {@see self::$messages} with the outcome. On success the
     * Rocket.Chat token is stored as a user preference. Used by both the form POST fallback and
     * the {@see external\login} web service.
     *
     * @param string $username the email or username
     * @param string $password the user password
     * @throws coding_exception
     */
    public function attempt(string $username, string $password): void {
        if (empty($username) || empty($password)) {
            $this->error = true;
        }

        if (empty($username) && empty($password)) {
            $this->add_message('info', 'credentialserror');

            return;
        }

        if (empty($username)) {
            $this->add_message('warning', 'usernameerror');

            return;
        }

        if (empty($password)) {
            $this->add_message('warning', 'passworderror');

            return;
        }

        $this->verify_login($username, $password);
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
            $this->add_message('danger', 'validationerror');

            return;
        }

        if (isset($response->status) && $response->status === 'success') {
            set_user_preference('local_rocketchat_external_user', $username);
            set_user_preference('local_rocketchat_external_token', $response->data->authToken);

            $this->add_message('success', 'validationsuccess');
        }
    }

    /**
     * Queue a feedback message for rendering inside the block.
     *
     * @param string $type a Bootstrap alert type (success, danger, warning, info)
     * @param string $identifier the language string identifier in block_rocketchat
     * @throws coding_exception
     */
    private function add_message(string $type, string $identifier): void {
        $this->messages[] = [
            'type' => $type,
            'message' => get_string($identifier, 'block_rocketchat'),
        ];
    }
}
