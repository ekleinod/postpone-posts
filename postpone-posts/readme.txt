=== Postpone Posts ===

Contributors: ekleinod
Tags: post, schedule
Requires at least: 3.0.1
Tested up to: 4.9.1
Requires PHP: 5.2.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Postpones all future (planned) posts by a selectable number of days.


== Description ==

Imagine you wrote a lot of planned posts that will be published in the future. Now an event occurs that forces you to react for one or more days. Either there are overlapping posts or you have to postpone (i.e. reschedule) all your future posts.

This is where *Postpone Posts* comes in handy. It takes all planned posts and postpones them by a selectable number of days.

Postponing can only be done by users with edit capabilities.

**Important:** this plugin is created and tested for a one user installation of wordpress. Currently, there are no extensive safety measures for access rights etc.


== Installation ==

1. Upload `postpone-posts` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress


== Frequently Asked Questions ==

= Can I postpone just some posts? =

No, currently there is no selection of posts to postpone.


== Screenshots ==

1. Start page: set number of days to postpone
2. Preview page: check consequences
3. Result page: see a summary of the postpone result


== Changelog ==

= 0.1.0 =

* first version of the plugin
* basic functionality
* not tested against misuse concerning roles, capabilities etc. (see ToDo)


== Upgrade Notice ==

= 0.1.0 =

* first version, no upgrade possible


== ToDo ==

= Functionality =

* selection of posts to postpone
* option page for setting default postpone days

= User Experience =

* nicer preview of postpone dates and posts on start page
* interactive preview of postpone dates and posts on start page
* localization

= Hardening =

* check for false input in days field
* concept for rights for postponing
	* can only users postpone their own posts?
	* can admin postpone all posts?

= Technics =

* use WP functions
	* error handling and display
