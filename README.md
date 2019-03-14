# Moodle Rocket.Chat Block Plugin [![Build Status](https://travis-ci.org/adpe/moodle-block_rocketchat.svg?branch=master)](https://travis-ci.org/adpe/moodle-block_rocketchat)

The Rocket.Chat block plugin acts as overview and quick access between Moodle and Rocket.Chat. This block lists all groups and channels of the Rocket.Chat user.

## Main features
1. Channel overview (public and private)
2. Access to Rocket.Chat channels and groups.

## Installation
This plugin has a dependency as the [`local_rocketchat`](https://github.com/adpe/moodle-local_rocketchat) plugin must be installed first. After that please do these steps:
1. Copy this Rocket.Chat plugin to the `blocks` directory of your Moodle instance: `git clone https://github.com/adpe/moodle-blocks_rocketchat.git blocks/rocketchat`
2. Visit the notifications page to complete the install process

For more information, visit [documentation](http://docs.moodle.org/en/Installing_contributed_modules_or_plugins) for installing contributed modules and plugins.

*Note* - you need a running Rocket.Chat server that you can point the plugin to. If you aren't sure how to do this, checkout the [documentation](https://rocket.chat/docs/installation/) on Rocket.Chat. I also added a bit of [code](https://github.com/getsmarter/rocketchat-api-rest) to Rocket.Chat to make integration a little easier. This unfortunately will require a custom build of the Rocket.Chat source code. 

## Usage
The user can add this block in all Moodle areas where blocks can be added. So he has over all Moodle access to his channels.

## Todo
- Create public channel direct from the block.
- Create private group direct from the block.
- Change the status of the user.
- Open new chat inside popup and not as new tab.
- Fix logout out of block.
- Redesign block.