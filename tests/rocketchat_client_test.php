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
 * Unit tests for the block_rocketchat REST client.
 *
 * @package    block_rocketchat
 * @category   test
 * @copyright  2026 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_rocketchat;

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Unit tests for the block_rocketchat REST client.
 *
 * Rocket.Chat HTTP traffic is simulated through \curl::mock_response(). The
 * client constructor performs no network access, so each test only mocks the
 * responses for the API calls it makes, in LIFO order.
 *
 * @copyright  2026 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(rocketchat_client::class)]
final class rocketchat_client_test extends \advanced_testcase {
    /**
     * Configure local_rocketchat so the instance url can be built.
     *
     * @param int $protocol 0 for https, 1 for http
     * @param string $port an optional port
     */
    private function setup_client_config(int $protocol = 0, string $port = ''): void {
        set_config('host', 'chat.example.com', 'local_rocketchat');
        set_config('port', $port, 'local_rocketchat');
        set_config('protocol', $protocol, 'local_rocketchat');
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
     * The default protocol is https and an empty port is omitted.
     */
    public function test_instance_url_defaults_to_https(): void {
        $this->resetAfterTest();
        $this->setup_client_config();

        $this->assertSame('https://chat.example.com', rocketchat_client::instance_url());
    }

    /**
     * A non-default protocol and port are reflected in the url.
     */
    public function test_instance_url_with_http_and_port(): void {
        $this->resetAfterTest();
        $this->setup_client_config(1, '3000');

        $this->assertSame('http://chat.example.com:3000', rocketchat_client::instance_url());
    }

    /**
     * The instance url is fixed at construction time.
     */
    public function test_get_instance_url(): void {
        $this->resetAfterTest();
        $this->setup_client_config();

        $client = new rocketchat_client();

        $this->assertSame('https://chat.example.com', $client->get_instance_url());
    }

    /**
     * A successful resume login stores the credentials and reports success.
     */
    public function test_authenticate_with_token_success(): void {
        $this->resetAfterTest();
        $this->setup_client_config();

        \curl::mock_response($this->login_success_response());

        $client = new rocketchat_client();

        $this->assertTrue($client->authenticate_with_token('storedtoken'));
    }

    /**
     * A login response without a success status must fail.
     */
    public function test_authenticate_with_token_failure(): void {
        $this->resetAfterTest();
        $this->setup_client_config();

        \curl::mock_response(json_encode(['status' => 'error', 'message' => 'Unauthorized']));

        $client = new rocketchat_client();

        $this->assertFalse($client->authenticate_with_token('expiredtoken'));
    }

    /**
     * A non-JSON response (e.g. a proxy error page) must fail cleanly.
     */
    public function test_authenticate_with_token_invalid_json(): void {
        $this->resetAfterTest();
        $this->setup_client_config();

        \curl::mock_response('<html>502 Bad Gateway</html>');

        $client = new rocketchat_client();

        $this->assertFalse($client->authenticate_with_token('storedtoken'));
    }

    /**
     * A successful me() call exposes the user info including the status.
     */
    public function test_me_success(): void {
        $this->resetAfterTest();
        $this->setup_client_config();

        \curl::mock_response(json_encode(['success' => true, 'status' => 'away']));

        $client = new rocketchat_client();
        $me = $client->me();

        $this->assertNotNull($me);
        $this->assertSame('away', $me->status);
    }

    /**
     * An unsuccessful me() call returns null.
     */
    public function test_me_failure(): void {
        $this->resetAfterTest();
        $this->setup_client_config();

        \curl::mock_response(json_encode(['success' => false, 'error' => 'unauthorized']));

        $client = new rocketchat_client();

        $this->assertNull($client->me());
    }

    /**
     * The private groups are returned on success.
     */
    public function test_list_groups_success(): void {
        $this->resetAfterTest();
        $this->setup_client_config();

        \curl::mock_response(json_encode([
            'success' => true,
            'groups' => [['_id' => 'g1', 'name' => 'staff'], ['_id' => 'g2', 'name' => 'teachers']],
        ]));

        $client = new rocketchat_client();
        $groups = $client->list_groups();

        $this->assertCount(2, $groups);
        $this->assertSame('staff', $groups[0]->name);
    }

    /**
     * A failed groups listing returns an empty array.
     */
    public function test_list_groups_failure(): void {
        $this->resetAfterTest();
        $this->setup_client_config();

        \curl::mock_response(json_encode(['success' => false]));

        $client = new rocketchat_client();

        $this->assertSame([], $client->list_groups());
    }

    /**
     * The channels are returned on success.
     */
    public function test_list_channels_success(): void {
        $this->resetAfterTest();
        $this->setup_client_config();

        \curl::mock_response(json_encode([
            'success' => true,
            'channels' => [['_id' => 'c1', 'name' => 'general']],
        ]));

        $client = new rocketchat_client();
        $channels = $client->list_channels();

        $this->assertCount(1, $channels);
        $this->assertSame('general', $channels[0]->name);
    }

    /**
     * A failed channels listing returns an empty array.
     */
    public function test_list_channels_failure(): void {
        $this->resetAfterTest();
        $this->setup_client_config();

        \curl::mock_response(json_encode(['success' => false]));

        $client = new rocketchat_client();

        $this->assertSame([], $client->list_channels());
    }

    /**
     * A status outside of STATUSES is rejected without any network access.
     *
     * No response is mocked here on purpose: hitting the API would attempt a
     * real request and fail the test in a different way.
     */
    public function test_set_status_rejects_unknown_status(): void {
        $this->resetAfterTest();
        $this->setup_client_config();

        $client = new rocketchat_client();

        $this->assertFalse($client->set_status('invisible'));
    }

    /**
     * A valid status update reports success.
     */
    public function test_set_status_success(): void {
        $this->resetAfterTest();
        $this->setup_client_config();

        \curl::mock_response(json_encode(['success' => true]));

        $client = new rocketchat_client();

        $this->assertTrue($client->set_status('away'));
    }

    /**
     * An API failure on a valid status reports failure.
     */
    public function test_set_status_failure(): void {
        $this->resetAfterTest();
        $this->setup_client_config();

        \curl::mock_response(json_encode(['success' => false]));

        $client = new rocketchat_client();

        $this->assertFalse($client->set_status('away'));
    }

    /**
     * Once authenticated, the stored credentials are reused on subsequent requests.
     *
     * Exercises the authenticated request path (the auth headers are added once a
     * token and user id are known) by chaining a token login and a me() call.
     */
    public function test_authenticated_requests_reuse_credentials(): void {
        $this->resetAfterTest();
        $this->setup_client_config();

        // LIFO: me first, the token login (consumed first) last.
        \curl::mock_response(json_encode(['success' => true, 'status' => 'online']));
        \curl::mock_response($this->login_success_response());

        $client = new rocketchat_client();

        $this->assertTrue($client->authenticate_with_token('storedtoken'));
        $this->assertSame('online', $client->me()->status);
    }

    /**
     * A non-JSON me() response (e.g. a proxy error page) returns null cleanly.
     */
    public function test_me_invalid_json_returns_null(): void {
        $this->resetAfterTest();
        $this->setup_client_config();

        \curl::mock_response('<html>502 Bad Gateway</html>');

        $client = new rocketchat_client();

        $this->assertNull($client->me());
    }

    /**
     * A non-JSON groups listing returns an empty array.
     */
    public function test_list_groups_invalid_json_returns_empty(): void {
        $this->resetAfterTest();
        $this->setup_client_config();

        \curl::mock_response('<html>502 Bad Gateway</html>');

        $client = new rocketchat_client();

        $this->assertSame([], $client->list_groups());
    }
}
