# Moodle Rocket.Chat Block Plugin [![Moodle Plugin CI](https://github.com/adpe/moodle-block_rocketchat/actions/workflows/moodle-ci.yml/badge.svg)](https://github.com/adpe/moodle-block_rocketchat/actions/workflows/moodle-ci.yml)

The Rocket.Chat block plugin acts as overview and quick access between Moodle and Rocket.Chat. This block lists all groups and channels of the Rocket.Chat user.

## Main features

1. Channel overview (public and private)
2. Access to Rocket.Chat channels and groups.

## Installation

This plugin has a dependency as the [`local_rocketchat`](https://github.com/adpe/moodle-local_rocketchat) plugin must be installed first. After that please do these steps:

1. Copy this Rocket.Chat plugin to the `blocks` directory of your Moodle instance: `git clone https://github.com/adpe/moodle-block_rocketchat.git public/blocks/rocketchat`
2. Run `composer install` inside the `public/blocks/rocketchat` directory to install the dependencies
3. Visit the notifications' page to complete the installation process

For more information, visit [documentation](http://docs.moodle.org/en/Installing_contributed_modules_or_plugins) for installing contributed modules and plugins.

*Note* - you need a running Rocket.Chat server that you can point the plugin to. If you aren't sure how to do this, checkout
the [documentation](https://rocket.chat/docs/installation/) on Rocket.Chat.

## Configuration

In order to allow IFrame based Single Sign On you must have configured your Rocket.Chat instance
under `Administration` > `General` > `Restrict access insid any Iframe/Options to X-Frame-Options`.

## Usage

The user can add this block in all Moodle areas where blocks can be added. So he has over all Moodle access to his channels.
