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
 * Minimal Rocket.Chat REST client built on Moodle's own curl helper.
 *
 * @package   block_rocketchat
 * @copyright 2026 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_rocketchat;

use curl;
use dml_exception;
use stdClass;

/**
 * Talks to the Rocket.Chat REST API using {@see \curl}.
 *
 * Only the handful of endpoints the block needs are implemented. Authentication is
 * held as instance state (auth token + user id) and sent on each request, so a single
 * client instance should be reused for the lifetime of a request.
 */
class rocketchat_client {
    /**
     * The presence statuses Rocket.Chat accepts on users.setStatus.
     */
    public const STATUSES = ['online', 'away', 'busy', 'offline'];

    /**
     * The REST API root path, relative to the instance url.
     */
    private const API_ROOT = '/api/v1/';

    /**
     * The Rocket.Chat instance base url (no trailing slash), e.g. https://chat.example.com.
     *
     * @var string
     */
    private string $baseurl;

    /**
     * The REST API base url, including the version path.
     *
     * @var string
     */
    private string $api;

    /**
     * The authenticated user's auth token, once {@see self::authenticate_with_token()} succeeds.
     *
     * @var string|null
     */
    private ?string $authtoken = null;

    /**
     * The authenticated user's id, once {@see self::authenticate_with_token()} succeeds.
     *
     * @var string|null
     */
    private ?string $userid = null;

    /**
     * Constructor.
     */
    public function __construct() {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $this->baseurl = self::instance_url();
        $this->api = $this->baseurl . self::API_ROOT;
    }

    /**
     * Build the Rocket.Chat instance base url from the local_rocketchat configuration.
     *
     * This is derived purely from config and performs no network access.
     *
     * @return string the instance base url without trailing slash
     * @throws dml_exception
     */
    public static function instance_url(): string {
        $host = get_config('local_rocketchat', 'host');
        $port = get_config('local_rocketchat', 'port');
        $protocol = (int) get_config('local_rocketchat', 'protocol') === 0 ? 'https' : 'http';

        return $protocol . '://' . $host . (!empty($port) ? ':' . $port : '');
    }

    /**
     * The Rocket.Chat instance base url this client talks to.
     *
     * @return string the instance base url without trailing slash
     */
    public function get_instance_url(): string {
        return $this->baseurl;
    }

    /**
     * Perform a JSON request against the API and return the decoded body.
     *
     * @param  string $method 'get' or 'post'
     * @param  string $endpoint the endpoint relative to the API root, e.g. 'users.setStatus'
     * @param  array  $data the request body for POST requests
     * @return stdClass|null the decoded response body, or null on transport/decoding failure
     */
    private function request(string $method, string $endpoint, array $data = []): ?stdClass {
        $curl = new curl();

        $headers = ['Content-Type: application/json'];
        if ($this->authtoken !== null && $this->userid !== null) {
            $headers[] = 'X-Auth-Token: ' . $this->authtoken;
            $headers[] = 'X-User-Id: ' . $this->userid;
        }
        $curl->setHeader($headers);

        $url = $this->api . $endpoint;

        if ($method === 'post') {
            $response = empty($data) ? $curl->post($url) : $curl->post($url, json_encode($data));
        } else {
            $response = $curl->get($url);
        }

        $decoded = json_decode($response);

        return $decoded instanceof stdClass ? $decoded : null;
    }

    /**
     * Authenticate as a user via their stored resume/auth token.
     *
     * On success the auth token and user id are stored on this instance and sent
     * with every subsequent request.
     *
     * @param  string $token the stored Rocket.Chat resume/auth token
     * @return bool whether authentication succeeded
     */
    public function authenticate_with_token(string $token): bool {
        $response = $this->request('post', 'login', ['resume' => $token]);

        if (isset($response->status) && $response->status === 'success') {
            $this->authtoken = $response->data->authToken;
            $this->userid = $response->data->userId;

            return true;
        }

        return false;
    }

    /**
     * Quick information about the authenticated user.
     *
     * @return stdClass|null the user info (including the `status` field), or null on failure
     */
    public function me(): ?stdClass {
        $response = $this->request('get', 'me');

        return (isset($response->success) && $response->success) ? $response : null;
    }

    /**
     * List the private groups the authenticated user is part of.
     *
     * @return array each entry exposes `_id` and `name`
     */
    public function list_groups(): array {
        $response = $this->request('get', 'groups.list');

        return (isset($response->success) && $response->success) ? $response->groups : [];
    }

    /**
     * List the channels the authenticated user has access to.
     *
     * @return array each entry exposes `_id` and `name`
     */
    public function list_channels(): array {
        $response = $this->request('get', 'channels.list');

        return (isset($response->success) && $response->success) ? $response->channels : [];
    }

    /**
     * Update the authenticated user's presence status.
     *
     * @param  string $status one of {@see self::STATUSES}
     * @return bool whether the status was updated
     */
    public function set_status(string $status): bool {
        if (!in_array($status, self::STATUSES, true)) {
            return false;
        }

        $response = $this->request('post', 'users.setStatus', ['status' => $status]);

        return isset($response->success) && $response->success;
    }
}
