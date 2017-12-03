<?php
/*
Plugin Name: Postpone Posts
Plugin URI; http://www.ekkart.de/computer/wordpress/plugins.html#postpone-posts
Description: Postpones all future (planned) posts by a selectable number of days.
Version: 0.1.0
Author: Ekkart Kleinod
Author URI: http://www.edgesoft.de/
License: GPL-3.0
License URI: https://opensource.org/licenses/GPL-3.0

Postpone Posts is a wordpress plugin that postpones all future (planned) posts by a selectable number of days.
Copyright (C) 2017 Ekkart Kleinod

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

if (!class_exists('PostponePosts')) {

	/**
	 * Class containing all plugin code.
	 *
	 * The class contains only static functions, as no instances are needed.
	 * This is just an encapsulation to easily avoid name collisions as is recommended in
	 * https://developer.wordpress.org/plugins/the-basics/best-practices/
	 */
	class PostponePosts {

		const OPTION_DAYS = 'postpone_posts_days';
		const OPTION_DAYS_DEFAULT = 1;

		/**
		 * Activation: sets up option of postpone days in database.
		 */
		public static function activation() {

			// if postpone days do not exist in options database
			if (!get_option(self::OPTION_DAYS)) {

				// create field and set number of days to 1
				add_option(self::OPTION_DAYS, self::OPTION_DAYS_DEFAULT);

			}

		}

		/**
		 * Deactivation: nothing to do at the moment.
		 */
		public static function deactivation() {
			// pass
		}

		/**
		 * Uninstall: remove option of postpone days in database.
		 */
		public static function uninstall() {

			// if postpone days exist in options database
			if (get_option(self::OPTION_DAYS)) {

				// remove field
				delete_option(self::OPTION_DAYS);

			}

		}

	}

	// maintenance
	register_activation_hook(__FILE__, 'PostponePosts::activation');
	register_deactivation_hook(__FILE__, 'PostponePosts::deactivation');
	register_uninstall_hook(__FILE__, 'PostponePosts::uninstall');

} else {
	// display error
}
