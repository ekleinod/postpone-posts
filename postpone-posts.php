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

		const ACTION_CANCEL = "cancel-postpone";
		const ACTION_POSTPONE = "do-postpone";
		const ACTION_PREVIEW = "preview-postpone";

		const FIELD_DAYS = "days-postpone";

		// this should be set in options or localization or such
		const DATE_FORMAT = "Y-m-d";

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

		/**
		 * Adds option page to tools menu.
		 */
		public static function addOptionPage() {

			add_management_page('Postpone Posts', 'Postpone', 'edit_posts', 'postpone_posts', 'PostponePosts::showOptionPage');

		}

		/**
		 * Adds help tab.
		 */
		public static function addContextHelp() {

			get_current_screen()->add_help_tab(array(
				'id'      => 'overview',
				'title'   => __('Overview'),
				'content' => '<p>' . __('You can postpone all future posts shown in the box by the given number of days.') . '</p>',
			));

		}

		/**
		 * Option page: show input fields and affected posts.
		 */
		public static function showOptionPage() {

			// check user capabilities
			if (!current_user_can('edit_posts')) {
				return;
			}

			// get affected posts
			$post_args = array(
					'post_status' => array('future'),
					'numberposts' => -1,
					'order' => 'ASC',
			);

			$future_posts = get_posts($post_args);

			?>

				<div class="wrap">
				<h1><?= esc_html(get_admin_page_title()); ?></h1>

			<?php

			if (isset($_GET[self::ACTION_POSTPONE])) {

				self::showActionPage($future_posts, $_GET[self::FIELD_DAYS]);

			} else if (isset($_GET[self::ACTION_PREVIEW])) {

				self::showPreviewPage($future_posts, $_GET[self::FIELD_DAYS]);

			} else {

				self::showDaysInputPage($future_posts);

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

					<p><?php echo(__('You can postpone all future posts shown in the box by the given number of days.')) ?></p>

					<h2><?php echo(__('Postpone settings')); ?></h2>

					<p>
						<label for="postpone_posts_days" class="label-responsive"><?php echo(__('Days to postpone:')); ?></label>
						<input type="number" name="<?php echo(self::FIELD_DAYS); ?>" min="1" value="<?php echo(get_option(self::OPTION_DAYS)); ?>" autofocus="autofocus" />
					</p>

					<h2><?php echo(__('Affected posts (display only)')); ?></h2>

					<textarea rows="5" cols="60" disabled="disabled" readonly="readonly" placeholder="<?php echo(__('No posts to postpone.')); ?>"><?php

					foreach ($thePosts as $post) {
						$postDate = new DateTime($post->post_date);
						$postponedDate = self::getPostponedDate($postDate, get_option(self::OPTION_DAYS));
						echo(sprintf("%s -> %s: \"%s\"\n", $postDate->format(self::DATE_FORMAT), $postponedDate->format(self::DATE_FORMAT), $post->post_title));
					}

					?></textarea>

					<?php

						$submit_args = array();
						if (count($thePosts) < 1) {
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
		 * @param theDays days to postpone
		 */
		private static function showPreviewPage($thePosts, $theDays) {

			global $plugin_page;

			?>

				<form method="get">

					<input type="hidden" name="page" value="<?php echo($plugin_page); ?>" />
					<input type="hidden" name="<?php echo(self::FIELD_DAYS); ?>" value="<?php echo($theDays); ?>" />

					<p><?php echo(__('Start postponing by clicking "Postpone Posts". You can cancel the operation by clicking "Cancel".')) ?></p>

					<h2><?php echo(__('Preview')); ?></h2>

					<p>Days to postpone: <?php echo($theDays); ?></p>

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
									$postDate = new DateTime($post->post_date);
									$postponedDate = self::getPostponedDate($postDate, $theDays);

									?>

										<tr>
											<td><?php echo($postDate->format(self::DATE_FORMAT)); ?></td>
											<td><?php echo($postponedDate->format(self::DATE_FORMAT)); ?></td>
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
		 * @param theDays days to postpone
		 */
		private static function showActionPage($thePosts, $theDays) {

			echo('<ul>');

			$count_success = 0;
			$count_error = 0;

			foreach ($thePosts as $post) {

				$postDate = new DateTime($post->post_date);
				$postponedDate = self::getPostponedDate($postDate, $theDays);
				$postGMTDate = new DateTime($post->post_date_gmt);
				$postponedGMTDate = self::getPostponedDate($postGMTDate, $theDays);

				$postUpdate = array(
						'ID' => $post->id,
						'post_date' => $postponedDate,
						'post_date_gmt' => $postponedGMTDate,
				);

				// debug
				$postOriginal = array(
						'ID' => $post->id,
						'post_date' => $post_date,
						'post_date_gmt' => $post_date_gmt,
				);
				echo(sprintf("<li>Original %s</li>\n", $postOriginal));
				echo(sprintf("<li>Postponed %s</li>\n", $postUpdate));
				// /debug

				//$post_id =  wp_update_post($postUpdate, true);

				if (!is_wp_error($post_id)) {

					$message = '&check;';
					$count_success++;

				} else {

					$message = sprintf('&cross;: %s', implode('.', $post_id->get_error_messages()));
					$count_error++;

				}

				echo(sprintf("<li>Postponing from %s (GMT: %s) to %s (GMT: %s) for %s - %s</li>\n",
						 $postDate->format(self::DATE_FORMAT),
						 $postGMTDate->format(self::DATE_FORMAT),
						 $postponedDate->format(self::DATE_FORMAT),
						 $postponedGMTDate->format(self::DATE_FORMAT),
						 $post->post_title,
						 $message
				));

			}

			echo('</ul>');

			echo(sprintf("<p>Finished postponing with %d successfully postponed posts and %d errors.</p>\n", $count_success, $count_error));

		}

		/**
		 * Compute postponed date.
		 *
		 * @param theDate date to postpone
		 * @param theDays number of days to postpone
		 *
		 * @return postponed date
		 */
		private static function getPostponedDate($theDate, $theDays) {

			$dteReturn = clone $theDate;
			return $dteReturn->add(new DateInterval(sprintf('P%sD', $theDays)));

		}

	} // end of class PostponePosts

	// maintenance
	register_activation_hook(__FILE__, 'PostponePosts::activation');
	register_deactivation_hook(__FILE__, 'PostponePosts::deactivation');
	register_uninstall_hook(__FILE__, 'PostponePosts::uninstall');

	add_action('admin_menu', 'PostponePosts::addOptionPage');

	add_filter('contextual_help', 'PostponePosts::addContextHelp', 5, 3);

} else {

	?>

		<p>Error: class "PostponePosts" exists.</p>

	<?php

}
