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
 * Unit tests for the block_rocketchat privacy provider.
 *
 * @package    block_rocketchat
 * @category   test
 * @copyright  2026 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_rocketchat\privacy;

use core_privacy\local\metadata\null_provider;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Unit tests for the block_rocketchat privacy provider.
 *
 * The block stores no personal data of its own, so it implements the null
 * provider and only needs to point at the language string explaining why.
 *
 * @copyright  2026 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(provider::class)]
final class provider_test extends \advanced_testcase {
    /**
     * The provider declares itself as storing no personal data.
     */
    public function test_implements_null_provider(): void {
        $this->assertInstanceOf(null_provider::class, new provider());
    }

    /**
     * The reason points at the metadata language string.
     */
    public function test_get_reason_identifier(): void {
        $this->assertSame('privacy:metadata', provider::get_reason());
    }

    /**
     * The reason language string is actually defined for the component.
     */
    public function test_get_reason_string_exists(): void {
        $this->resetAfterTest();

        $reason = get_string(provider::get_reason(), 'block_rocketchat');

        $this->assertIsString($reason);
        $this->assertNotEmpty($reason);
    }
}