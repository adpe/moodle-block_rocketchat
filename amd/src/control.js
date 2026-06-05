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
 * Drives the Rocket.Chat block: dynamic login, presence status changes and the channel popups.
 *
 * Listeners are delegated from the document so they survive the block content being swapped out.
 *
 * @module      block_rocketchat/control
 * @copyright   2026 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import Templates from 'core/templates';

const SELECTORS = {
    region: '[data-region="rocketchat"]',
    form: 'form#rocketchat_login',
    statusAction: '[data-action="rocketchat-set-status"]',
    statusIcon: '[data-region="rocketchat-status-icon"] [data-status]',
    viewChannel: '[data-action="view-channel"]',
    popup: '[data-region="rocketchat-popup"]',
    popupHeader: '.card-header',
    popupResize: '.rocketchat-popup-resize',
    popupClose: '[data-action="rocketchat-popup-close"]',
    popupMinimize: '[data-action="rocketchat-popup-minimize"]',
};

const MIN_WIDTH = 280;
const MIN_HEIGHT = 240;
const MINIMIZED_WIDTH = 260;
// Horizontal gap between docked (minimized) tabs so their shadows don't visually overlap.
const MINIMIZED_GAP = 16;
// Vertical gap left between an open popup and the docked (minimized) tab strip below it.
const OPEN_BOTTOM_GAP = 16;

let initialised = false;
let topZindex = 1041;

/**
 * Replace a block region with freshly rendered content returned by the web service.
 *
 * @param {HTMLElement} region The block region to replace.
 * @param {String} html The rendered block content (a single [data-region] root element).
 */
const replaceRegion = (region, html) => {
    const parsed = document.createElement('template');
    parsed.innerHTML = html.trim();

    const replacement = parsed.content.firstElementChild;
    if (replacement) {
        // The display mode is a client-only setting the server re-render doesn't know about; keep it.
        if (region.dataset.displaymode) {
            replacement.dataset.displaymode = region.dataset.displaymode;
        }
        region.replaceWith(replacement);
    }
};

/**
 * Submit the login form to Rocket.Chat and swap in the returned block content.
 *
 * @param {HTMLElement} region The block region wrapping the login form.
 * @param {HTMLFormElement} form The submitted login form.
 */
const submitLogin = (region, form) => {
    const courseid = Number(region.dataset.courseid) || 0;
    const username = form.querySelector('[name="rocketchat_username"]').value;
    const password = form.querySelector('[name="rocketchat_password"]').value;

    Ajax.call([
        {
            methodname: 'block_rocketchat_login',
            args: {username, password, courseid},
        },
    ])[0]
        .then((response) => {
            replaceRegion(region, response.html);
            return response;
        })
        .catch(Notification.exception);
};

/**
 * Reflect the new status on the dropdown toggle icon.
 *
 * @param {HTMLElement} region The block region wrapping a single Rocket.Chat block.
 * @param {String} status The status to display.
 */
const updateIcon = (region, status) => {
    region.querySelectorAll(SELECTORS.statusIcon).forEach((icon) => {
        icon.classList.toggle('d-none', icon.dataset.status !== status);
    });
};

/**
 * Send the requested status to Rocket.Chat and update the icon on success.
 *
 * @param {HTMLElement} region The block region wrapping a single Rocket.Chat block.
 * @param {String} status The requested status.
 */
const setStatus = (region, status) => {
    Ajax.call([
        {
            methodname: 'block_rocketchat_set_status',
            args: {status},
        },
    ])[0]
        .then((response) => {
            if (response.success) {
                updateIcon(region, response.status);
            }
            return response;
        })
        .catch(Notification.exception);
};

/**
 * Bring a popup in front of the others.
 *
 * @param {HTMLElement} popup The popup to raise.
 */
const raise = (popup) => {
    topZindex += 1;
    popup.style.zIndex = topZindex.toString();
};

/**
 * Pin a popup to explicit top/left coordinates so it follows the pointer when dragged or resized.
 *
 * Until the user interacts with it the popup is anchored via right/bottom; this converts that to a
 * fixed left/top once, leaving later moves untouched.
 *
 * @param {HTMLElement} popup The popup to pin.
 */
const pinPosition = (popup) => {
    if (popup.style.left) {
        return;
    }
    const rect = popup.getBoundingClientRect();
    popup.style.left = `${Math.round(rect.left)}px`;
    popup.style.top = `${Math.round(rect.top)}px`;
    popup.style.right = 'auto';
    popup.style.bottom = 'auto';
};

/**
 * Wire a pointer-driven gesture to a handle: pin and raise the popup, then stream pointer moves to
 * the callback until the pointer is released. Pointer capture keeps the gesture alive over the iframe.
 *
 * @param {HTMLElement} handle The element that starts the gesture.
 * @param {HTMLElement} popup The popup being moved or resized.
 * @param {Function} canStart Receives the pointerdown event; return false to ignore it.
 * @param {Function} onMove Receives (moveEvent, startRect, startEvent) for each pointer move.
 */
const onPointerDrag = (handle, popup, canStart, onMove) => {
    handle.addEventListener('pointerdown', (e) => {
        if (e.button !== 0 || !canStart(e)) {
            return;
        }
        e.preventDefault();
        raise(popup);
        pinPosition(popup);
        popup.classList.add('dragging');

        const startRect = popup.getBoundingClientRect();
        const move = (ev) => onMove(ev, startRect, e);
        const stop = () => {
            popup.classList.remove('dragging');
            handle.removeEventListener('pointermove', move);
            handle.removeEventListener('pointerup', stop);
        };

        handle.setPointerCapture(e.pointerId);
        handle.addEventListener('pointermove', move);
        handle.addEventListener('pointerup', stop);
    });
};

/**
 * Let the user drag a popup around the screen by its header.
 *
 * @param {HTMLElement} popup The popup being made draggable.
 * @param {HTMLElement} handle The element used to start the drag (the header).
 */
const makeDraggable = (popup, handle) => onPointerDrag(
    handle,
    popup,
    (e) => !e.target.closest('button') && !popup.classList.contains('minimized'),
    (ev, startRect, start) => {
        popup.style.left = `${Math.round(ev.clientX - (start.clientX - startRect.left))}px`;
        popup.style.top = `${Math.round(ev.clientY - (start.clientY - startRect.top))}px`;
    },
);

/**
 * Let the user resize a popup from its bottom-right grip.
 *
 * @param {HTMLElement} popup The popup being made resizable.
 * @param {HTMLElement} grip The element used to start the resize.
 */
const makeResizable = (popup, grip) => onPointerDrag(
    grip,
    popup,
    () => true,
    (ev, startRect, start) => {
        popup.style.width = `${Math.round(Math.max(MIN_WIDTH, startRect.width + (ev.clientX - start.clientX)))}px`;
        popup.style.height = `${Math.round(Math.max(MIN_HEIGHT, startRect.height + (ev.clientY - start.clientY)))}px`;
    },
);

/**
 * Remember a popup's current position and size so it can be restored after minimizing.
 *
 * @param {HTMLElement} popup The popup whose geometry to store.
 */
const saveGeometry = (popup) => {
    popup.dataset.geometry = JSON.stringify({
        left: popup.style.left,
        top: popup.style.top,
        right: popup.style.right,
        bottom: popup.style.bottom,
        width: popup.style.width,
        height: popup.style.height,
    });
};

/**
 * Restore a popup to the position and size captured by {@see saveGeometry}.
 *
 * @param {HTMLElement} popup The popup to restore.
 */
const restoreGeometry = (popup) => {
    const geometry = JSON.parse(popup.dataset.geometry || '{}');
    popup.style.left = geometry.left || '';
    popup.style.top = geometry.top || '';
    popup.style.right = geometry.right || '';
    popup.style.bottom = geometry.bottom || '';
    popup.style.width = geometry.width || '';
    popup.style.height = geometry.height || '';
};

/**
 * Dock every minimized popup along the bottom-right edge, side by side like chat tabs.
 */
const reflowMinimized = () => {
    let offset = 20;
    document.querySelectorAll(`${SELECTORS.popup}.minimized`).forEach((popup) => {
        popup.style.left = '';
        popup.style.top = '';
        popup.style.right = `${offset}px`;
        popup.style.bottom = '0';
        offset += MINIMIZED_WIDTH + MINIMIZED_GAP;
    });
};

/**
 * Toggle a popup between its minimized (docked, header only) and restored state.
 *
 * Minimizing docks the popup to the bottom-right corner; restoring returns it to where it was.
 *
 * @param {HTMLElement} popup The popup to toggle.
 * @param {HTMLElement} button The minimize button (its title is kept in sync).
 */
const toggleMinimize = (popup, button) => {
    if (popup.classList.contains('minimized')) {
        popup.classList.remove('minimized');
        restoreGeometry(popup);
        raise(popup);
    } else {
        saveGeometry(popup);
        popup.classList.add('minimized');
        popup.style.left = '';
        popup.style.top = '';
        popup.style.width = '';
        popup.style.height = '';
    }

    if (button) {
        const label = popup.classList.contains('minimized') ? button.dataset.maximizeStr : button.dataset.minimizeStr;
        button.title = label;
        button.setAttribute('aria-label', label);
    }

    reflowMinimized();
};

/**
 * Open a channel in a detachable, resizable popup, or focus it if already open.
 *
 * @param {String} name The channel name.
 * @param {String} url The channel URL.
 */
const openChannelPopup = (name, url) => {
    const existing = Array.from(document.querySelectorAll(SELECTORS.popup)).find((p) => p.dataset.url === url);
    if (existing) {
        if (existing.classList.contains('minimized')) {
            toggleMinimize(existing, existing.querySelector(SELECTORS.popupMinimize));
        }
        raise(existing);
        return;
    }

    const offset = document.querySelectorAll(`${SELECTORS.popup}:not(.minimized)`).length * 24;

    Templates.render('block_rocketchat/popup', {name, url})
        .then((html, js) => {
            const popup = Templates.appendNodeContents(document.body, html, js)[0];
            popup.dataset.url = url;

            // Lift the popup clear of the docked tab strip, measuring the real tab (header) height.
            const header = popup.querySelector(SELECTORS.popupHeader);
            const tabHeight = header ? Math.ceil(header.getBoundingClientRect().height) : 48;
            popup.style.right = `${20 + offset}px`;
            popup.style.bottom = `${tabHeight + OPEN_BOTTOM_GAP + offset}px`;
            raise(popup);

            const grip = popup.querySelector(SELECTORS.popupResize);
            if (header) {
                makeDraggable(popup, header);
            }
            if (grip) {
                makeResizable(popup, grip);
            }
            return popup;
        })
        .catch(Notification.exception);
};

/**
 * Open a channel using the block's configured display mode.
 *
 * @param {String} mode One of 'popup', 'window' or 'newtab'.
 * @param {String} name The channel name.
 * @param {String} url The channel URL.
 */
const openChannel = (mode, name, url) => {
    if (mode === 'newtab') {
        window.open(url, 'rocketchat');
    } else if (mode === 'window') {
        window.open(url, 'rocketchat', 'popup=yes,width=420,height=700');
    } else {
        openChannelPopup(name, url);
    }
};

export const init = () => {
    if (initialised) {
        return;
    }
    initialised = true;

    document.addEventListener('submit', (e) => {
        const form = e.target.closest(SELECTORS.form);
        const region = form && form.closest(SELECTORS.region);

        if (!region) {
            return;
        }

        e.preventDefault();
        submitLogin(region, form);
    });

    document.addEventListener('click', (e) => {
        const statusTrigger = e.target.closest(SELECTORS.statusAction);
        if (statusTrigger) {
            const region = statusTrigger.closest(SELECTORS.region);
            if (region) {
                e.preventDefault();
                setStatus(region, statusTrigger.dataset.status);
            }
            return;
        }

        const channelTrigger = e.target.closest(SELECTORS.viewChannel);
        if (channelTrigger) {
            e.preventDefault();
            const region = channelTrigger.closest(SELECTORS.region);
            const mode = (region && region.dataset.displaymode) || 'popup';
            openChannel(mode, channelTrigger.dataset.name, channelTrigger.href);
            return;
        }

        const closeTrigger = e.target.closest(SELECTORS.popupClose);
        if (closeTrigger) {
            e.preventDefault();
            closeTrigger.closest(SELECTORS.popup).remove();
            reflowMinimized();
            return;
        }

        const minimizeTrigger = e.target.closest(SELECTORS.popupMinimize);
        if (minimizeTrigger) {
            e.preventDefault();
            toggleMinimize(minimizeTrigger.closest(SELECTORS.popup), minimizeTrigger);
            return;
        }

        // Clicking a docked (minimized) popup's header bar restores it.
        const header = e.target.closest(SELECTORS.popupHeader);
        const minimizedPopup = header && !e.target.closest('button') && header.closest(`${SELECTORS.popup}.minimized`);
        if (minimizedPopup) {
            e.preventDefault();
            toggleMinimize(minimizedPopup, minimizedPopup.querySelector(SELECTORS.popupMinimize));
        }
    });
};
