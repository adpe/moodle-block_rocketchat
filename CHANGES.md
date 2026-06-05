# Changelog

All notable changes to the Rocket.Chat block plugin are documented in this file.
Releases use Moodle-style names (e.g. `v5.1-r3`); see the Git tags for the full history.

## v5.1-r3

Requires Moodle 5.1 (`2025041400`) and `local_rocketchat` (`2026011802`).

### Added

- Open a channel or group in an embedded **popup** instead of a new tab. The popup is **detachable**
  (drag it anywhere by its header), **resizable** (bottom-right grip) and can be minimised; it follows
  Moodle's Bootstrap styling.
- **Per-block display mode** preference (block settings): open channels in a floating popup, a fixed-size
  browser popup window, or a new browser tab.
- Log in to Rocket.Chat directly from the block and have the block content update **dynamically, without
  reloading the page** (new `block_rocketchat_login` web service driving an in-place swap of the block).
- **"Go to Rocket.Chat"** link in the status dropdown that opens the Rocket.Chat instance in a new tab.

### Changed

- Login and validation feedback now renders **inside the block** as a dismissible alert, instead of as a
  site-wide page notification.
- The login form is now CSRF-protected with a session key and only reacts to its own submissions.
- Logout now reads the course id from its URL parameter and lives at `blocks/rocketchat/logout.php`
  (previously under `classes/`).
- Presence-status and login interactions are handled by a single delegated AMD module
  (`block_rocketchat/control`), so they keep working after the block content is swapped.
- The Rocket.Chat instance URL is derived from configuration without performing an extra network login
  on every page load.

### Fixed

- Submitting an unrelated form on a page that contains the block no longer triggers a missing-parameter error.
- Grammar and wording of the language strings (e.g. "Successfully logged in to Rocket.Chat.").
- The product name is now displayed consistently as "Rocket.Chat".

### Removed

- Obsolete `locallib.php` and unused language strings.

## Earlier releases

- **v5.1-r1 / v5.1-r2** (2026-01-18) - Moodle 5.1 support; Bootstrap 5 markup and user status fix.
- **v4.1 - v4.5** (2024-2026) - Moodle 4.x support.
- **v3.9 - v3.11** (2021) - initial public releases.
