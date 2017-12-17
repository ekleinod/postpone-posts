<?php
/*
Plugin Name: Postpone Posts
Plugin URI; https://github.com/ekleinod/postpone-posts
Description: Postpones all future (planned) posts by a selectable number of days.
Version: 0.2.0
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

		const ID = 'postpone_posts';
		const PLUGIN_NAME = 'Postpone Posts';

		const NAME_OPTION_DAYS = self::ID . '_days';
		const OPTION_DAYS_DEFAULT = 1;

		const ID_SETTING_GROUP = self::ID;
		const ID_SETTINGS_SECTION_DAYS = self::ID . '_section_days';
		const ID_SETTINGS_FIELD_DAYS = self::ID . '_field_days';

		const ACTION_CANCEL = "cancel-" . self::ID;
		const ACTION_POSTPONE = "do-" . self::ID;
		const ACTION_PREVIEW = "preview-" . self::ID;

		const INPUT_FIELD_DAYS = "days-postpone";

		const DAYS_MIN = 1;
		const DAYS_MAX = 365;

		/**
		 * Activation: sets up option of postpone days in database.
		 */
		public static function activation() {

			// if postpone days do not exist in options database
			if (!get_option(self::NAME_OPTION_DAYS)) {

				// create field and set number of days to 1
				add_option(self::NAME_OPTION_DAYS, self::OPTION_DAYS_DEFAULT);

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
			if (get_option(self::NAME_OPTION_DAYS)) {

				// remove field
				delete_option(self::NAME_OPTION_DAYS);

			}

		}

		/**
		 * Adds page to tools menu.
		 */
		public static function addToolsPage() {

			add_management_page(
					self::PLUGIN_NAME, // page title
					self::PLUGIN_NAME, // menu title
					'edit_posts', // capability
					'postpone_posts_action', // menu slug, i.e. url param "page"
					'PostponePosts::showToolsPage' // function to display page
			);

		}

		/**
		 * Adds help tab.
		 */
		public static function addContextHelp() {

			get_current_screen()->add_help_tab(array(
				'id'      => 'overview',
				'title'   => __('Overview', self::ID),
				'content' => sprintf('<p>%s</p>', sprintf(__('You can postpone all future posts shown in the box by the given number of days. The number of days has to be between %d and %d.', self::ID), self::DAYS_MIN, self::DAYS_MAX)),
			));

		}

		/**
		 * Tool page: show input fields and affected posts, execute action.
		 */
		public static function showToolsPage() {

			// check user capabilities
			if (!current_user_can('edit_posts')) {
				return;
			}

			// get affected posts
			$postArgs = array(
					'post_status' => array('future'),
					'numberposts' => -1,
					'order' => 'ASC',
			);

			$futurePosts = get_posts($postArgs);

			?>

				<div class="wrap">
				<h1><?= esc_html(get_admin_page_title()); ?></h1>

				<?php

				if ((count($futurePosts) > 0) && isset($_GET[self::INPUT_FIELD_DAYS])) {

					$popopoDays = $_GET[self::INPUT_FIELD_DAYS];
					if (self::checkDays($popopoDays)) {

						// trigger actions depending on sending form
						if (isset($_GET[self::ACTION_POSTPONE])) {

							self::showActionPage($futurePosts, $_GET[self::INPUT_FIELD_DAYS]);

						} else if (isset($_GET[self::ACTION_PREVIEW])) {

							self::showPreviewPage($futurePosts, $_GET[self::INPUT_FIELD_DAYS]);

						} else {

							self::printError(__('Unrecognized action.', self::ID));
							self::showDaysInputPage($futurePosts);

						}

					} else {

						foreach (get_settings_errors(self::ID_SETTINGS_FIELD_DAYS) as $error) {

							self::printError($error['message']);

						}

						self::showDaysInputPage($futurePosts);

					}

				} else {

					self::showDaysInputPage($futurePosts);

				}


				?>

				</div>

			<?php
		}

		/**
		 * Days input page.
		 *
		 * @param thePosts posts to show
		 */
		private static function showDaysInputPage($thePosts) {

			global $plugin_page;

			?>

				<form method="get">

					<input type="hidden" name="page" value="<?php echo($plugin_page); ?>" />

					<p><?php echo(__('You can postpone all future posts shown in the box by the given number of days.', self::ID)) ?></p>

					<h2><?php echo(__('Postpone settings', self::ID)); ?></h2>

					<p>
						<label for="<?php echo(self::INPUT_FIELD_DAYS); ?>" class="label-responsive"><?php echo(__('Days to postpone:', self::ID)); ?></label>
						<input type="number" name="<?php echo(self::INPUT_FIELD_DAYS); ?>" id="<?php echo(self::INPUT_FIELD_DAYS); ?>" min="<?php echo(self::DAYS_MIN); ?>" max="<?php echo(self::DAYS_MAX); ?>" value="<?php echo(get_option(self::NAME_OPTION_DAYS)); ?>" autofocus="autofocus" />
					</p>

					<h2><?php echo(__('Affected posts (display only)', self::ID)); ?></h2>

					<textarea rows="5" cols="60" disabled="disabled" readonly="readonly" placeholder="<?php echo(__('No posts to postpone.', self::ID)); ?>"><?php

					foreach ($thePosts as $post) {
						$postUpdate = self::getUpdatePost($post, get_option(self::NAME_OPTION_DAYS));
						echo(sprintf("%s -> %s: \"%s\"\n", self::formatDateShort($post->post_date), self::formatDateShort($postUpdate['post_date']), $post->post_title));
					}

					?></textarea>

					<?php

						$submit_args = array();
						if (count($thePosts) <= 0) {
							$submit_args['disabled'] = 'disabled';
						}
						submit_button('Preview Postponing', 'primary large', self::ACTION_PREVIEW, true, $submit_args);

					?>

				</form>

			<?php

		}

		/**
		 * Preview page.
		 *
		 * @param thePosts posts to show
		 * @param theDays days to postpone (sanitized number > 0)
		 */
		private static function showPreviewPage($thePosts, $theInput) {

			global $plugin_page;

			?>

				<form method="get">

					<input type="hidden" name="page" value="<?php echo($plugin_page); ?>" />
					<input type="hidden" name="<?php echo(self::INPUT_FIELD_DAYS); ?>" value="<?php echo($theInput); ?>" />

					<p><?php echo(__('Start postponing by clicking "Postpone Posts". You can cancel the operation by clicking "Cancel".', self::ID)) ?></p>

					<h2><?php echo(__('Preview', self::ID)); ?></h2>

					<p>Days to postpone: <?php echo($theInput); ?></p>

					<table>
						<thead>
							<tr>
								<th>Date</th>
								<th>Postpone to</th>
								<th>Title</th>
							</tr>
						</thead>
						<tfoot>
							<tr>
								<th>Date</th>
								<th>Postpone to</th>
								<th>Title</th>
							</tr>
						</tfoot>
						<tbody>

							<?php

								foreach ($thePosts as $post) {

									$postUpdate = self::getUpdatePost($post, $theInput);

									?>

										<tr>
											<td><?php echo(self::formatDateShort($post->post_date)); ?></td>
											<td><?php echo(self::formatDateShort($postUpdate['post_date'])); ?></td>
											<td><?php echo($post->post_title); ?></td>
										</tr>

									<?php

								}

							?>

						</tbody>
					</table>

					<?php

						$submit_args = array();
						if (count($thePosts) < 1) {
							$submit_args['disabled'] = 'disabled';
						}
						submit_button('Postpone Posts', 'primary large', self::ACTION_POSTPONE, true, $submit_args);

					?>

				</form>

				<form method="get">

					<input type="hidden" name="page" value="<?php echo($plugin_page); ?>" />

					<?php
						submit_button('Cancel', 'secondary large', self::ACTION_CANCEL);
					?>
				</form>

			<?php

		}

		/**
		 * Action page (executes postponing).
		 *
		 * @param thePosts posts to show
		 * @param theDays days to postpone (sanitized number > 0)
		 */
		private static function showActionPage($thePosts, $theInput) {

			echo('<ul>');

			$count_success = 0;
			$count_error = 0;

			foreach ($thePosts as $post) {

				$postUpdate = self::getUpdatePost($post, $theInput);

				$post_id =  wp_update_post($postUpdate, true);

				if (!is_wp_error($post_id)) {

					$message = '&check;';
					$count_success++;

				} else {

					$message = sprintf('&cross;: %s', implode('.', $post_id->get_error_messages()));
					$count_error++;

				}

				echo(sprintf("<li>Postponing from %s (GMT: %s) to %s (GMT: %s) for %s - %s</li>\n",
						 self::formatDateShort($post->post_date),
						 self::formatDateShort($post->post_date_gmt),
						 self::formatDateShort($postUpdate['post_date']),
						 self::formatDateShort($postUpdate['post_date_gmt']),
						 $post->post_title,
						 $message
				));

			}

			echo('</ul>');

			echo(sprintf("<p>Finished postponing with %d successfully postponed posts and %d errors.</p>\n", $count_success, $count_error));

		}

		/**
		 * Compute array for postponed post.
		 *
		 * @param thePost original post
		 * @param theDays number of days to postpone (sanitized number > 0)
		 *
		 * @return postponed post data
		 */
		private static function getUpdatePost($thePost, $theInput) {

			$postDate = new DateTime($thePost->post_date);
			$postponedDate = self::getPostponedDate($postDate, $theInput);

			$postGMTDate = new DateTime($thePost->post_date_gmt);
			$postponedGMTDate = self::getPostponedDate($postGMTDate, $theInput);

			return array(
					'ID' => $thePost->ID,
					'post_date' => $postponedDate->format('Y-m-d H:i:s'),
					'post_date_gmt' => $postponedGMTDate->format('Y-m-d H:i:s'),
			);

		}

		/**
		 * Compute postponed date.
		 *
		 * @param theDate date to postpone
		 * @param theDays number of days to postpone (sanitized number > 0)
		 *
		 * @return postponed date
		 */
		private static function getPostponedDate($theDate, $theInput) {

			$dteReturn = clone $theDate;
			return $dteReturn->add(new DateInterval(sprintf('P%sD', $theInput)));

		}

		/**
		 * Returns date in short format (for preview).
		 *
		 * Short format should be set in options or localization or such.
		 *
		 * @param theDate date (string representation)
		 *
		 * @return date in short strin representation
		 */
		private static function formatDateShort($theDate) {

			$postDate = new DateTime($theDate);
			return $postDate->format("Y-m-d");

		}

		/**
		 * Print error message.
		 *
		 * Wrapper, because I don't know how to output errors in wp yet.
		 *
		 * @param theMessage message to print
		 */
		private static function printError($theMessage) {

			?>

				<div class="error notice">
					<p><?php echo(esc_html($theMessage)); ?></p>
				</div>

			<?php

		}


		/**
		 * Init settings and options.
		 */
		public static function initSettings() {

			register_setting(
					self::ID_SETTING_GROUP,
					self::NAME_OPTION_DAYS,
					[
						'sanitize_callback' => 'PostponePosts::validateDays'
					]
			);

			add_settings_section(
					self::ID_SETTINGS_SECTION_DAYS, // section id
					__('Basic configuration', self::ID), // title
					'PostponePosts::showSettingsSectionDays', // function to display settings section
					self::ID_SETTING_GROUP // option group id
			);

			add_settings_field(
					self::ID_SETTINGS_FIELD_DAYS, // field id (only used internally)
					__('Days to postpone', self::ID), // title
					'PostponePosts::showSettingsFieldDays', // function to display field input form
					self::ID_SETTING_GROUP, // option group id
					self::ID_SETTINGS_SECTION_DAYS, // section id
					[
						'label_for' => self::NAME_OPTION_DAYS,
						'type' => 'number',
						'min_value' => self::DAYS_MIN,
						'max_value' => self::DAYS_MAX,
						'default_value' => self::OPTION_DAYS_DEFAULT,
						'description' => __('Default number of days to postpone. Can be overriden for each postponing operation.', self::ID)
					]
			);

		}

		/**
		 * Callback function for settings section "days".
		 *
		 * @param args array, have the following keys defined: title, id, callback.
		 *             the values are defined at the add_settings_section() function.
		 */
		public static function showSettingsSectionDays($args) {

			?>

				<p><?php echo(__('Set the default number of days to postpone in this section.', self::ID)); ?></p>

			<?php

		}

		/**
		 * Callback function for settings field "days".
		 *
		 * @param args array, is defined at the add_settings_field() function.
		 */
		public static function showSettingsFieldDays($args) {

			?>

				<input type="<?php echo(esc_attr($args['type'])); ?>"
					id="<?php echo(esc_attr($args['label_for'])); ?>"
					name="<?php echo(esc_attr($args['label_for'])); ?>"
					min="<?php echo(esc_attr($args['min_value'])); ?>"
					max="<?php echo(esc_attr($args['max_value'])); ?>"
					value="<?php echo(esc_attr(get_option($args['label_for'], $args['default_value']))); ?>"
				/>

				<p class="description">
					<?php echo(esc_attr($args['description'])); ?>
				</p>

			<?php

		}

		/**
		 * Entry in options menu.
		 */
		public static function addOptionsPage() {

			add_options_page(
					self::PLUGIN_NAME, // page title
					self::PLUGIN_NAME, // menu title
					'manage_options', // capability
					'postpone_posts_options', // menu slug, i.e. url param "page"
					'PostponePosts::showOptionsPage' // function to display page
			);

		}

		/**
		 * Shows options page.
		 */
		public static function showOptionsPage() {

			if (!current_user_can('manage_options')) {
				return;
			}

			?>

				<div class="wrap">

					<h1><?php echo(esc_html(get_admin_page_title())); ?></h1>

					<p><?php echo(sprintf(__('%s postpones all future (planned) posts by a selectable number of days.'), self::PLUGIN_NAME)); ?></p>

					<form action="options.php" method="post">

						<?php

							// output security fields for the registered setting
							settings_fields(self::ID_SETTING_GROUP);

							// output setting sections and their fields
							do_settings_sections(self::ID_SETTING_GROUP);

							submit_button();

						?>

					</form>
				</div>

			<?php
		}

		/**
		 * Validate days input from options page.
		 *
		 * I cannot stop options from being saved in the database,
		 * thus a valid value has to be transmitted to the database.
		 *
		 * That's why the check of the input is separated from the validation for
		 * the options page.
		 *
		 * @param theInput input value of number of days to postpone
		 *
		 * @return number of days to store in the database
		 */
		public static function validateDays($theInput) {

			$validValue = get_option(self::NAME_OPTION_DAYS, self::OPTION_DAYS_DEFAULT);

			if (self::checkDays($theInput)) {
				$validValue = $theInput;
			}

			return $validValue;

		}

		/**
		 * Check days input.
		 *
		 * - has to be a number
		 * - has to be larger than min number of days
		 * - has to be smaller than max number of days, that input is probably a typo
		 *
		 * @param theInput input value of number of days to postpone
		 *
		 * @return correct input (true) or erroneous input (false)
		 */
		private static function checkDays($theInput) {

			// check if input is a number
			if (!is_numeric($theInput)) {
				add_settings_error(
						self::ID_SETTINGS_FIELD_DAYS,
						self::ID_SETTINGS_FIELD_DAYS,
						sprintf(__('"%s" is no numeric value.', self::ID), $theInput)
				);
				return false;
			}

			// check if input is larger than min number of days
			if ($theInput < self::DAYS_MIN) {
				add_settings_error(
						self::ID_SETTINGS_FIELD_DAYS,
						self::ID_SETTINGS_FIELD_DAYS,
						sprintf(__('"%d" is smaller than the minimal number of days (%d).', self::ID), $theInput, self::DAYS_MIN)
				);
				return false;
			}

			// check if input is smaller than max number of days
			if ($theInput > self::DAYS_MAX) {
				add_settings_error(
						self::ID_SETTINGS_FIELD_DAYS,
						self::ID_SETTINGS_FIELD_DAYS,
						sprintf(__('"%d" is larger than the maximal number of days (%d).', self::ID), $theInput, self::DAYS_MAX)
				);
				return false;
			}

			// valid input
			return true;

		}

	} // end of class PostponePosts

	// maintenance
	register_activation_hook(__FILE__, 'PostponePosts::activation');
	register_deactivation_hook(__FILE__, 'PostponePosts::deactivation');
	register_uninstall_hook(__FILE__, 'PostponePosts::uninstall');

	add_action('admin_init', 'PostponePosts::initSettings');

	add_action('admin_menu', 'PostponePosts::addToolsPage');
	add_action('admin_menu', 'PostponePosts::addOptionsPage');

	add_filter('contextual_help', 'PostponePosts::addContextHelp', 5, 3);

} else {

	?>

		<p>Error: class "PostponePosts" exists.</p>

	<?php

}
