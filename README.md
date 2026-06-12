# Moodle Rocket.Chat Block Plugin [![Moodle Plugin CI](https://github.com/adpe/moodle-block_rocketchat/actions/workflows/moodle-ci.yml/badge.svg)](https://github.com/adpe/moodle-block_rocketchat/actions/workflows/moodle-ci.yml) [![codecov](https://codecov.io/gh/adpe/moodle-block_rocketchat/graph/badge.svg)](https://codecov.io/gh/adpe/moodle-block_rocketchat)

The Rocket.Chat block plugin acts as an overview and quick access point between Moodle and Rocket.Chat. This block lists all groups and channels of the Rocket.Chat user.

## Main features

1. Channel overview of your public channels and private groups.
2. Open a channel or group in a detachable, resizable popup - drag it anywhere on screen, resize it, or minimise it - without leaving the page.
3. Choose, per block, how channels open: a floating popup, a fixed-size browser popup window, or a new browser tab (configurable in the block's settings).
4. Log in to Rocket.Chat directly from the block - the block updates dynamically, without reloading the page.
5. View and change your Rocket.Chat presence status (online, away, busy, offline) from the block.
6. Quick link to open your Rocket.Chat instance.

## Installation

This plugin has a dependency as the [`local_rocketchat`](https://github.com/adpe/moodle-local_rocketchat) plugin must be installed first. After that please do these steps:

1. Copy this Rocket.Chat plugin to the `blocks` directory of your Moodle instance: `git clone https://github.com/adpe/moodle-block_rocketchat.git public/blocks/rocketchat`
2. Visit the notifications' page to complete the installation process

For more information, visit [documentation](http://docs.moodle.org/en/Installing_contributed_modules_or_plugins) for installing contributed modules and plugins.

*Note* - you need a running Rocket.Chat server that you can point the plugin to. If you aren't sure how to do this, checkout
the [documentation](https://rocket.chat/docs/installation/) on Rocket.Chat.

## Configuration

In order to allow the IFrame-based single sign-on, your Rocket.Chat instance must permit being embedded
in an IFrame. Configure the *Iframe Integration* settings under `Administration` > `Settings` > `General` >
`Iframe Integration`, and make sure your instance's `X-Frame-Options` policy allows your Moodle site.

## Usage

Users can add this block anywhere blocks are allowed in Moodle, giving them access to their Rocket.Chat channels throughout the site.
