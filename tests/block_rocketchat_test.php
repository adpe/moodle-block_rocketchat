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
 * Unit tests for the main block_rocketchat block.
 *
 * @package    block_rocketchat
 * @category   test
 * @copyright  2026 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_rocketchat;

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Unit tests for the main block_rocketchat block.
 *
 * get_content() renders the channel view from a short-lived per-user cache, falling back to the
 * login view (with an outage notice when Rocket.Chat is unreachable). Rocket.Chat HTTP traffic is
 * simulated through \curl::mock_response(); the mocked responses form a stack (LIFO): the token
 * login is consumed first, then the block rendering issues me, groups.list, channels.list and
 * subscriptions.get.
 *
 * @copyright  2026 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(\block_rocketchat::class)]
final class block_rocketchat_test extends \advanced_testcase {
    /**
     * Configure local_rocketchat so the block client can be constructed.
     */
    private function setup_client_config(): void {
        set_config('host', 'chat.example.com', 'local_rocketchat');
        set_config('port', '', 'local_rocketchat');
        set_config('protocol', 0, 'local_rocketchat');
    }

    /**
     * Build a block instance bound to a system-context page.
     *
     * @return \block_rocketchat
     */
    private function get_block(): \block_rocketchat {
        $block = block_instance('rocketchat');

        $page = new \moodle_page();
        $page->set_context(\context_system::instance());
        $block->page = $page;
        $block->config = (object) ['displaymode' => 'popup'];

        return $block;
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
     * init() sets the default block title.
     */
    public function test_init_sets_title(): void {
        $this->resetAfterTest();

        $block = block_instance('rocketchat');

        $this->assertSame(get_string('defaulttitle', 'block_rocketchat'), $block->title);
    }

    /**
     * Without a stored token the block renders the login view.
     */
    public function test_get_content_renders_login_without_token(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());

        $content = $this->get_block()->get_content();

        $this->assertSame('', $content->footer);
        $this->assertStringContainsString('rocketchat_login', $content->text);
    }

    /**
     * When Rocket.Chat is unreachable the user sees an outage notice, not a bare login form.
     */
    public function test_get_content_shows_notice_when_unreachable(): void {
        $this->resetAfterTest();
        $this->setup_client_config();
        $this->setUser($this->getDataGenerator()->create_user());
        set_user_preference('local_rocketchat_external_token', 'storedtoken');

        \curl::mock_response('<html>502 Bad Gateway</html>');

        $content = $this->get_block()->get_content();

        $this->assertStringContainsString(get_string('serviceunavailable', 'block_rocketchat'), $content->text);
        $this->assertStringContainsString('rocketchat_login', $content->text);
    }

    /**
     * A usable token renders the channel view and caches the room data for later renders.
     */
    public function test_get_content_renders_block_and_caches(): void {
        global $USER;

        $this->resetAfterTest();
        $this->setup_client_config();
        $this->setUser($this->getDataGenerator()->create_user());
        set_user_preference('local_rocketchat_external_token', 'storedtoken');

        // LIFO: subscriptions, channels, groups, me, then the token login (consumed first).
        \curl::mock_response(json_encode(['success' => true, 'update' => [['name' => 'general', 'unread' => 7]]]));
        \curl::mock_response(json_encode(['success' => true, 'channels' => [['_id' => 'c1', 'name' => 'general']]]));
        \curl::mock_response(json_encode(['success' => true, 'groups' => [['_id' => 'g1', 'name' => 'staff']]]));
        \curl::mock_response(json_encode(['success' => true, 'status' => 'online']));
        \curl::mock_response($this->login_success_response());

        $content = $this->get_block()->get_content();

        $this->assertStringContainsString('general', $content->text);
        $this->assertStringContainsString('staff', $content->text);

        $cached = \core_cache\cache::make('block_rocketchat', 'blockdata')->get($USER->id);
        $this->assertIsArray($cached);
        $this->assertSame('general', $cached['public'][0]['name']);
    }

    /**
     * A second render is served from the cache without any further Rocket.Chat requests.
     */
    public function test_get_content_serves_from_cache(): void {
        $this->resetAfterTest();
        $this->setup_client_config();
        $this->setUser($this->getDataGenerator()->create_user());
        set_user_preference('local_rocketchat_external_token', 'storedtoken');

        // First render populates the cache (token login, me, groups, channels, subscriptions).
        \curl::mock_response(json_encode(['success' => true, 'update' => []]));
        \curl::mock_response(json_encode(['success' => true, 'channels' => [['_id' => 'c1', 'name' => 'general']]]));
        \curl::mock_response(json_encode(['success' => true, 'groups' => []]));
        \curl::mock_response(json_encode(['success' => true, 'status' => 'online']));
        \curl::mock_response($this->login_success_response());

        $this->get_block()->get_content();

        // No further responses are mocked: a fresh instance must render from the cache only.
        $content = $this->get_block()->get_content();

        $this->assertStringContainsString('general', $content->text);
    }
}