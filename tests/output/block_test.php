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
 * Unit tests for the block_rocketchat templatable.
 *
 * @package    block_rocketchat
 * @category   test
 * @copyright  2026 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_rocketchat\output;

use block_rocketchat\rocketchat_client;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Unit tests for the block_rocketchat templatable.
 *
 * Rocket.Chat HTTP traffic is simulated through \curl::mock_response(). Note
 * that mocked responses form a stack (LIFO): export_for_block() requests me,
 * groups.list, channels.list and subscriptions.get in that order.
 *
 * @copyright  2026 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(block::class)]
final class block_test extends \advanced_testcase {
    /**
     * Configure local_rocketchat so the block client can be constructed.
     */
    private function setup_client_config(): void {
        set_config('host', 'chat.example.com', 'local_rocketchat');
        set_config('port', '', 'local_rocketchat');
        set_config('protocol', 0, 'local_rocketchat');
    }

    /**
     * A renderer to pass as export target (the exports do not use it).
     *
     * @return \renderer_base
     */
    private function get_renderer(): \renderer_base {
        $page = new \moodle_page();
        $page->set_context(\context_system::instance());

        return new \core_renderer($page, RENDERER_TARGET_GENERAL);
    }

    /**
     * The logged in view exposes the urls, presence and channel lists.
     */
    public function test_export_for_block(): void {
        $this->resetAfterTest();
        $this->setup_client_config();
        $this->setUser($this->getDataGenerator()->create_user());
        set_user_preference('local_rocketchat_external_token', 'storedtoken');

        // LIFO: subscriptions.get first, channels.list, groups.list, me (consumed first) last.
        \curl::mock_response(json_encode([
            'success' => true,
            'update' => [['name' => 'staff', 'unread' => 2], ['name' => 'general', 'unread' => 5]],
        ]));
        \curl::mock_response(json_encode(['success' => true, 'channels' => [['_id' => 'c1', 'name' => 'general']]]));
        \curl::mock_response(json_encode(['success' => true, 'groups' => [['_id' => 'g1', 'name' => 'staff']]]));
        \curl::mock_response(json_encode(['success' => true, 'status' => 'busy']));

        $block = new block();
        $data = $block->export_for_block($this->get_renderer(), new rocketchat_client(), 42, 'popup');

        $this->assertSame(42, $data['courseid']);
        $this->assertSame('popup', $data['displaymode']);
        $this->assertSame('https://chat.example.com', $data['instanceurl']);
        $this->assertSame('https://chat.example.com/home?resumeToken=storedtoken', $data['loginurl']);
        $this->assertStringContainsString('/blocks/rocketchat/logout.php?id=42', (string) $data['logouturl']);

        $this->assertTrue($data['user'][0]['status-busy']);
        $this->assertFalse($data['user'][0]['status-online']);
        $this->assertFalse($data['user'][0]['status-away']);
        $this->assertFalse($data['user'][0]['status-offline']);

        $this->assertSame('staff', $data['private'][0]['name']);
        $this->assertSame('https://chat.example.com/group/', $data['private'][0]['href']);
        $this->assertSame(2, $data['private'][0]['unread']);
        $this->assertTrue($data['private'][0]['hasunread']);
        $this->assertSame('general', $data['public'][0]['name']);
        $this->assertSame('https://chat.example.com/channel/', $data['public'][0]['href']);
        $this->assertSame(5, $data['public'][0]['unread']);
        $this->assertTrue($data['public'][0]['hasunread']);
    }

    /**
     * When the user info cannot be fetched no presence flag is set.
     */
    public function test_export_for_block_without_user_info(): void {
        $this->resetAfterTest();
        $this->setup_client_config();
        $this->setUser($this->getDataGenerator()->create_user());
        set_user_preference('local_rocketchat_external_token', 'storedtoken');

        // LIFO: empty subscriptions first, then empty channel and group listings, me (failure) last.
        \curl::mock_response(json_encode(['success' => true, 'update' => []]));
        \curl::mock_response(json_encode(['success' => true, 'channels' => []]));
        \curl::mock_response(json_encode(['success' => true, 'groups' => []]));
        \curl::mock_response(json_encode(['success' => false]));

        $block = new block();
        $data = $block->export_for_block($this->get_renderer(), new rocketchat_client(), 0, 'popup');

        $this->assertFalse($data['user'][0]['status-online']);
        $this->assertFalse($data['user'][0]['status-away']);
        $this->assertFalse($data['user'][0]['status-busy']);
        $this->assertFalse($data['user'][0]['status-offline']);
        $this->assertSame([], $data['private']);
        $this->assertSame([], $data['public']);
    }

    /**
     * compose() builds the logged in view from cached room data without any network access.
     */
    public function test_compose_from_cached_rooms(): void {
        $this->resetAfterTest();
        $this->setup_client_config();
        $this->setUser($this->getDataGenerator()->create_user());
        set_user_preference('local_rocketchat_external_token', 'storedtoken');

        $rooms = [
            'status' => 'away',
            'private' => [['name' => 'staff', 'href' => 'https://chat.example.com/group/', 'unread' => 1, 'hasunread' => true]],
            'public' => [],
        ];

        // No \curl::mock_response(): compose() must not touch the network.
        $data = (new block())->compose($rooms, 7, 'newtab');

        $this->assertSame(7, $data['courseid']);
        $this->assertSame('newtab', $data['displaymode']);
        $this->assertSame('https://chat.example.com/home?resumeToken=storedtoken', $data['loginurl']);
        $this->assertTrue($data['user'][0]['status-away']);
        $this->assertSame('staff', $data['private'][0]['name']);
        $this->assertSame([], $data['public']);
    }

    /**
     * The logged out view exposes the course and a session key for the form.
     */
    public function test_export_for_login(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());

        $block = new block();
        $data = $block->export_for_login($this->get_renderer(), 42);

        $this->assertSame(42, $data['courseid']);
        $this->assertSame(sesskey(), $data['sesskey']);
        $this->assertSame('', $data['tmpusername']);
    }

    /**
     * A previously submitted username is carried back into the login form.
     */
    public function test_export_for_login_reflects_submitted_username(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());

        $_POST['rocketchat_username'] = 'jane';

        $block = new block();
        $data = $block->export_for_login($this->get_renderer(), 0);

        $this->assertSame('jane', $data['tmpusername']);
    }

    /**
     * The generic templatable export carries no data of its own.
     */
    public function test_export_for_template_is_empty(): void {
        $this->resetAfterTest();

        $block = new block();

        $this->assertSame([], $block->export_for_template($this->get_renderer()));
    }
}
