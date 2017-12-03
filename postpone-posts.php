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

/**
 * Activation: sets up option of postpone days in database.
 */
function popo_activation() {
	// if postpone days do not exist in options database
	// create field and set number of days to 1
}

// register activation routine
register_activation_hook(__FILE__, 'popo_activation');

/**
 * Deactivation: nothing to do at the moment.
 */
function popo_deactivation() {
	// pass
}

// register deactivation routine
register_deactivation_hook(__FILE__, 'popo_deactivation');

/**
 * Uninstall: remove option of postpone days in database.
 */
function popo_activation() {
	// if postpone days exist in options database
	// remove field
}

// register uninstall routine
register_uninstall_hook(__FILE__, 'popo_uninstall');
