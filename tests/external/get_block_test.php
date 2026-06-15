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
 * Unit tests for the block_rocketchat get_block external function.
 *
 * @package    block_rocketchat
 * @category   test
 * @copyright  2026 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_rocketchat\external;

use core_cache\cache;
use core_external\external_api;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Unit tests for the block_rocketchat get_block external function.
 *
 * Rocket.Chat HTTP traffic is simulated through \curl::mock_response(). Note that mocked responses
 * form a stack (LIFO): the token login request is consumed first, then the block rendering issues
 * me, groups.list, channels.list and subscriptions.get.
 *
 * @copyright  2026 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(get_block::class)]
final class get_block_test extends \core_external\tests\externallib_testcase {
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
        get_block::execute(0);
    }

    /**
     * Without a stored token the login view is returned, not flagged as unreachable.
     */
    public function test_execute_without_token(): void {
        $this->resetAfterTest();
        $this->setup_client_config();
        $this->setUser($this->getDataGenerator()->create_user());

        $result = get_block::execute(0);
        $result = external_api::clean_returnvalue(get_block::execute_returns(), $result);

        $this->assertFalse($result['authenticated']);
        $this->assertFalse($result['unreachable']);
        $this->assertStringContainsString('rocketchat_login', $result['html']);
    }

    /**
     * An expired token (a valid rejection) is reported as not authenticated but reachable.
     */
    public function test_execute_with_rejected_token(): void {
        $this->resetAfterTest();
        $this->setup_client_config();
        $this->setUser($this->getDataGenerator()->create_user());
        set_user_preference('local_rocketchat_external_token', 'expiredtoken');

        \curl::mock_response(json_encode(['status' => 'error', 'message' => 'Unauthorized']));

        $result = get_block::execute(0);
        $result = external_api::clean_returnvalue(get_block::execute_returns(), $result);

        $this->assertFalse($result['authenticated']);
        $this->assertFalse($result['unreachable']);
    }

    /**
     * A non-JSON response flags the instance as unreachable.
     */
    public function test_execute_with_unreachable_instance(): void {
        $this->resetAfterTest();
        $this->setup_client_config();
        $this->setUser($this->getDataGenerator()->create_user());
        set_user_preference('local_rocketchat_external_token', 'storedtoken');

        \curl::mock_response('<html>502 Bad Gateway</html>');

        $result = get_block::execute(0);
        $result = external_api::clean_returnvalue(get_block::execute_returns(), $result);

        $this->assertFalse($result['authenticated']);
        $this->assertTrue($result['unreachable']);
    }

    /**
     * A usable token returns the channel view and warms the per-user cache.
     */
    public function test_execute_success_returns_block_and_warms_cache(): void {
        global $USER;

        $this->resetAfterTest();
        $this->setup_client_config();
        $this->setUser($this->getDataGenerator()->create_user());
        set_user_preference('local_rocketchat_external_token', 'storedtoken');

        // LIFO: subscriptions, channels, groups, me, then the token login (consumed first).
        \curl::mock_response(json_encode(['success' => true, 'update' => [['name' => 'general', 'unread' => 2]]]));
        \curl::mock_response(json_encode(['success' => true, 'channels' => [['_id' => 'c1', 'name' => 'general']]]));
        \curl::mock_response(json_encode(['success' => true, 'groups' => [['_id' => 'g1', 'name' => 'staff']]]));
        \curl::mock_response(json_encode(['success' => true, 'status' => 'online']));
        \curl::mock_response($this->login_success_response());

        $result = get_block::execute(0);
        $result = external_api::clean_returnvalue(get_block::execute_returns(), $result);

        $this->assertTrue($result['authenticated']);
        $this->assertFalse($result['unreachable']);
        $this->assertStringContainsString('general', $result['html']);
        $this->assertStringContainsString('staff', $result['html']);

        $cached = cache::make('block_rocketchat', 'blockdata')->get($USER->id);
        $this->assertIsArray($cached);
        $this->assertSame('general', $cached['public'][0]['name']);
        $this->assertSame(2, $cached['public'][0]['unread']);
    }
}