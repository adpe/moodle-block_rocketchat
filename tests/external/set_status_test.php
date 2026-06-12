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
 * Unit tests for the block_rocketchat set_status external function.
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
 * Unit tests for the block_rocketchat set_status external function.
 *
 * Rocket.Chat HTTP traffic is simulated through \curl::mock_response(). Note
 * that mocked responses form a stack (LIFO): the token login request is the
 * first one, the users.setStatus request the second one.
 *
 * @copyright  2026 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(set_status::class)]
final class set_status_test extends \core_external\tests\externallib_testcase {
    /**
     * Configure local_rocketchat so the block client can be constructed.
     */
    private function setup_client_config(): void {
        set_config('host', 'chat.example.com', 'local_rocketchat');
        set_config('port', '', 'local_rocketchat');
        set_config('protocol', 0, 'local_rocketchat');
    }

    /**
     * The JSON body of a successful /api/v1/login response.
     *
     * @return string
     */
    private function login_success_response(): string {
        return json_encode([
            'status' => 'success',
            'data' => ['authToken' => 'token123', 'userId' => 'someid'],
        ]);
    }

    /**
     * The external function requires a logged in user.
     */
    public function test_execute_requires_login(): void {
        $this->resetAfterTest();
        $this->setup_client_config();

        $this->expectException(\require_login_exception::class);
        set_status::execute('online');
    }

    /**
     * Without a stored Rocket.Chat token the status cannot be updated.
     */
    public function test_execute_without_token(): void {
        $this->resetAfterTest();
        $this->setup_client_config();
        $this->setUser($this->getDataGenerator()->create_user());

        $result = set_status::execute('online');
        $result = external_api::clean_returnvalue(set_status::execute_returns(), $result);

        $this->assertFalse($result['success']);
        $this->assertSame('', $result['status']);
    }

    /**
     * An expired or revoked token fails the update.
     */
    public function test_execute_with_invalid_token(): void {
        $this->resetAfterTest();
        $this->setup_client_config();
        $this->setUser($this->getDataGenerator()->create_user());
        set_user_preference('local_rocketchat_external_token', 'expiredtoken');

        \curl::mock_response(json_encode(['status' => 'error', 'message' => 'Unauthorized']));

        $result = set_status::execute('online');
        $result = external_api::clean_returnvalue(set_status::execute_returns(), $result);

        $this->assertFalse($result['success']);
        $this->assertSame('', $result['status']);
    }

    /**
     * A status Rocket.Chat does not accept is rejected after authentication.
     */
    public function test_execute_with_unknown_status(): void {
        $this->resetAfterTest();
        $this->setup_client_config();
        $this->setUser($this->getDataGenerator()->create_user());
        set_user_preference('local_rocketchat_external_token', 'storedtoken');

        // Only the token login is mocked: the unknown status must not reach the API.
        \curl::mock_response($this->login_success_response());

        $result = set_status::execute('invisible');
        $result = external_api::clean_returnvalue(set_status::execute_returns(), $result);

        $this->assertFalse($result['success']);
        $this->assertSame('', $result['status']);
    }

    /**
     * A valid status update reports the new status back.
     */
    public function test_execute_success(): void {
        $this->resetAfterTest();
        $this->setup_client_config();
        $this->setUser($this->getDataGenerator()->create_user());
        set_user_preference('local_rocketchat_external_token', 'storedtoken');

        // LIFO: users.setStatus first, token login (consumed first) last.
        \curl::mock_response(json_encode(['success' => true]));
        \curl::mock_response($this->login_success_response());

        $result = set_status::execute('away');
        $result = external_api::clean_returnvalue(set_status::execute_returns(), $result);

        $this->assertTrue($result['success']);
        $this->assertSame('away', $result['status']);
    }

    /**
     * An API failure on a valid status reports failure.
     */
    public function test_execute_api_failure(): void {
        $this->resetAfterTest();
        $this->setup_client_config();
        $this->setUser($this->getDataGenerator()->create_user());
        set_user_preference('local_rocketchat_external_token', 'storedtoken');

        // LIFO: users.setStatus (failure) first, token login (consumed first) last.
        \curl::mock_response(json_encode(['success' => false]));
        \curl::mock_response($this->login_success_response());

        $result = set_status::execute('busy');
        $result = external_api::clean_returnvalue(set_status::execute_returns(), $result);

        $this->assertFalse($result['success']);
        $this->assertSame('', $result['status']);
    }
}
