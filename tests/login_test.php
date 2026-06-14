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
 * Unit tests for the block_rocketchat login handler.
 *
 * @package    block_rocketchat
 * @category   test
 * @copyright  2026 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_rocketchat;

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Unit tests for the block_rocketchat login handler.
 *
 * Rocket.Chat HTTP traffic is simulated through \curl::mock_response(). Note
 * that mocked responses form a stack (LIFO): the local_rocketchat client
 * constructor performs the first login request, the verification of the user
 * credentials the second one.
 *
 * @copyright  2026 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(login::class)]
final class login_test extends \advanced_testcase {
    /**
     * Configure local_rocketchat so its client can be constructed.
     */
    private function setup_client_config(): void {
        set_config('host', 'chat.example.com', 'local_rocketchat');
        set_config('port', '', 'local_rocketchat');
        set_config('protocol', 0, 'local_rocketchat');
        set_config('username', 'apiadmin', 'local_rocketchat');
        set_config('password', 'apipassword', 'local_rocketchat');
    }

    /**
     * The JSON body of a successful /api/v1/login response.
     *
     * @param string $token the auth token to embed
     * @return string
     */
    private function login_success_response(string $token = 'token123'): string {
        return json_encode([
            'status' => 'success',
            'data' => ['authToken' => $token, 'userId' => 'someid'],
        ]);
    }

    /**
     * Missing credentials must flag an error without any network access.
     */
    public function test_attempt_with_missing_credentials(): void {
        $this->resetAfterTest();

        $login = new login();
        $login->attempt('', '');

        $this->assertTrue($login->error);
        $this->assertCount(1, $login->messages);
        $this->assertSame('info', $login->messages[0]['type']);
    }

    /**
     * Valid credentials must store the user and token preferences.
     */
    public function test_attempt_stores_token_on_success(): void {
        $this->resetAfterTest();
        $this->setup_client_config();
        $this->setUser($this->getDataGenerator()->create_user());

        // LIFO: user verification first, admin client login (consumed first) last.
        \curl::mock_response($this->login_success_response('usertoken'));
        \curl::mock_response($this->login_success_response());

        $login = new login();
        $login->attempt('jane', 'secret');

        $this->assertFalse($login->error);
        $this->assertSame('jane', get_user_preferences('local_rocketchat_external_user'));
        $this->assertSame('usertoken', get_user_preferences('local_rocketchat_external_token'));
        $this->assertSame('success', $login->messages[0]['type']);
    }

    /**
     * A response without a status field must fail cleanly.
     *
     * Regression test: verify_login() used to read $response->status without
     * checking it exists, raising an undefined property warning on error
     * payloads (e.g. proxy error pages), and did not set the error flag.
     */
    public function test_attempt_handles_response_without_status(): void {
        $this->resetAfterTest();
        $this->setup_client_config();
        $this->setUser($this->getDataGenerator()->create_user());

        // LIFO: user verification (statusless payload) first, admin client login last.
        \curl::mock_response('{}');
        \curl::mock_response($this->login_success_response());

        $login = new login();
        $login->attempt('jane', 'wrongpassword');

        $this->assertTrue($login->error);
        $this->assertSame('danger', $login->messages[0]['type']);
        $this->assertNull(get_user_preferences('local_rocketchat_external_user'));
        $this->assertNull(get_user_preferences('local_rocketchat_external_token'));
    }

    /**
     * A successful status without an auth token must not store preferences.
     */
    public function test_attempt_requires_auth_token(): void {
        $this->resetAfterTest();
        $this->setup_client_config();
        $this->setUser($this->getDataGenerator()->create_user());

        // LIFO: user verification (success but no token) first, admin client login last.
        \curl::mock_response(json_encode(['status' => 'success', 'data' => []]));
        \curl::mock_response($this->login_success_response());

        $login = new login();
        $login->attempt('jane', 'secret');

        $this->assertTrue($login->error);
        $this->assertNull(get_user_preferences('local_rocketchat_external_token'));
    }

    /**
     * A missing username (with a password) is flagged with a targeted warning.
     */
    public function test_attempt_with_missing_username(): void {
        $this->resetAfterTest();

        $login = new login();
        $login->attempt('', 'secret');

        $this->assertTrue($login->error);
        $this->assertCount(1, $login->messages);
        $this->assertSame('warning', $login->messages[0]['type']);
        $this->assertSame(get_string('usernameerror', 'block_rocketchat'), $login->messages[0]['message']);
    }

    /**
     * A missing password (with a username) is flagged with a targeted warning.
     */
    public function test_attempt_with_missing_password(): void {
        $this->resetAfterTest();

        $login = new login();
        $login->attempt('jane', '');

        $this->assertTrue($login->error);
        $this->assertCount(1, $login->messages);
        $this->assertSame('warning', $login->messages[0]['type']);
        $this->assertSame(get_string('passworderror', 'block_rocketchat'), $login->messages[0]['message']);
    }

    /**
     * A genuine form submission (marker plus valid session key) triggers a login attempt.
     */
    public function test_constructor_processes_form_submission(): void {
        $this->resetAfterTest();
        $this->setup_client_config();
        $this->setUser($this->getDataGenerator()->create_user());

        $_POST['rocketchat_login'] = '1';
        $_POST['sesskey'] = sesskey();
        $_POST['rocketchat_username'] = 'jane';
        $_POST['rocketchat_password'] = 'secret';

        // LIFO: user verification first, admin client login (consumed first) last.
        \curl::mock_response($this->login_success_response('usertoken'));
        \curl::mock_response($this->login_success_response());

        $login = new login();

        $this->assertFalse($login->error);
        $this->assertSame('usertoken', get_user_preferences('local_rocketchat_external_token'));
        $this->assertSame('success', $login->messages[0]['type']);
    }
}
