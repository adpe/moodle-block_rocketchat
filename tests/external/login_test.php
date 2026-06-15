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
 * Unit tests for the block_rocketchat login external function.
 *
 * @package    block_rocketchat
 * @category   test
 * @copyright  2026 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_rocketchat\external;

use core_external\external_api;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Unit tests for the block_rocketchat login external function.
 *
 * Rocket.Chat HTTP traffic is simulated through \curl::mock_response(). Note
 * that mocked responses form a stack (LIFO): the local_rocketchat client
 * constructor performs the first login request, the verification of the user
 * credentials the second one, the block client token login the third one and
 * the block rendering (me, groups.list, channels.list, subscriptions.get) the
 * remaining ones.
 *
 * @copyright  2026 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(login::class)]
final class login_test extends \core_external\tests\externallib_testcase {
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
     * The external function requires a logged in user.
     */
    public function test_execute_requires_login(): void {
        $this->resetAfterTest();
        $this->setup_client_config();

        $this->expectException(\require_login_exception::class);
        login::execute('jane', 'secret');
    }

    /**
     * A successful login returns the authenticated block content.
     */
    public function test_execute_success_returns_block(): void {
        $this->resetAfterTest();
        $this->setup_client_config();
        $this->setUser($this->getDataGenerator()->create_user());

        // LIFO: the responses are consumed bottom-up.
        \curl::mock_response(json_encode(['success' => true, 'update' => []]));
        \curl::mock_response(json_encode(['success' => true, 'channels' => [['_id' => 'c1', 'name' => 'general']]]));
        \curl::mock_response(json_encode(['success' => true, 'groups' => [['_id' => 'g1', 'name' => 'staff']]]));
        \curl::mock_response(json_encode(['success' => true, 'status' => 'online']));
        \curl::mock_response($this->login_success_response('usertoken'));
        \curl::mock_response($this->login_success_response('usertoken'));
        \curl::mock_response($this->login_success_response());

        $result = login::execute('jane', 'secret', 0);
        $result = external_api::clean_returnvalue(login::execute_returns(), $result);

        $this->assertTrue($result['authenticated']);
        $this->assertStringContainsString('general', $result['html']);
        $this->assertStringContainsString('staff', $result['html']);
        $this->assertSame('usertoken', get_user_preferences('local_rocketchat_external_token'));
    }

    /**
     * Invalid credentials return the login form with an error notification.
     */
    public function test_execute_failure_returns_login_form(): void {
        $this->resetAfterTest();
        $this->setup_client_config();
        $this->setUser($this->getDataGenerator()->create_user());

        // LIFO: user verification (error payload) first, admin client login (consumed first) last.
        \curl::mock_response(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
        \curl::mock_response($this->login_success_response());

        $result = login::execute('jane', 'wrongpassword', 0);
        $result = external_api::clean_returnvalue(login::execute_returns(), $result);

        $this->assertFalse($result['authenticated']);
        $this->assertStringContainsString(get_string('validationerror', 'block_rocketchat'), $result['html']);
        $this->assertNull(get_user_preferences('local_rocketchat_external_token'));
    }

    /**
     * Missing credentials short-circuit without any network access.
     */
    public function test_execute_with_empty_credentials(): void {
        $this->resetAfterTest();
        $this->setup_client_config();
        $this->setUser($this->getDataGenerator()->create_user());

        $result = login::execute('', '', 0);
        $result = external_api::clean_returnvalue(login::execute_returns(), $result);

        $this->assertFalse($result['authenticated']);
        $this->assertStringContainsString(get_string('credentialserror', 'block_rocketchat'), $result['html']);
    }
}
