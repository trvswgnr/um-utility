<?php
/**
 * Plugin Name: UnionCentrics - Utility
 * Plugin URI: https://github.com/icentrics/unioncentrics-utility
 * Description: Utility plugin for UnionCentrics
 * Version: 1.4.0
 * Author: UnionCentrics
 * Author URI: http://unioncentrics.com
 *
 * @package unioncentrics-utility
 * @author Travis Aaron Wagner
 *
 * @todo move functions to class.
 */

/**
 * Register settings
 */
function ucu_register_settings() {
	add_option( 'ucu_um_allow_edit_email', 'on' );
	add_option( 'ucu_add_profile_nav_buttons', 'on' );
	add_option( 'ucu_show_um_profile_nav_titles', 'on' );
	register_setting( 'ucu_options_group', 'ucu_um_allow_edit_email', 'ucu_callback' );
	register_setting( 'ucu_options_group', 'ucu_add_profile_nav_buttons', 'ucu_callback' );
	register_setting( 'ucu_options_group', 'ucu_show_um_profile_nav_titles', 'ucu_callback' );
}
add_action( 'admin_init', 'ucu_register_settings' );

/**
 * Register options page
 */
function ucu_register_options_page() {
	add_options_page( 'UnionCentrics Utilities', 'UnionCentrics Utilities', 'manage_options', 'ucu', 'ucu_options_page' );
}
add_action( 'admin_menu', 'ucu_register_options_page' );

/**
 * Remove user role
 *
 * @return false If submit not in post.
 */
function ucu_remove_member_role() {
	// phpcs:disable WordPress.Security.NonceVerification
	if ( empty( $_POST['submit_remove_member_role'] ) ) {
		return false;
	}

	$wp_roles = new WP_Roles();
	$um_roles = get_option( 'um_roles' );

	$notice_type = 'success';

	if ( empty( $_POST['remove_member_role'] ) ) {
		$notice_type = 'error no-user';
	}

	$role_to_remove = ! empty( $_POST['remove_member_role'] ) ? filter_input( INPUT_POST, 'remove_member_role', FILTER_SANITIZE_STRING ) : false;
	// phpcs:enable WordPress.Security.NonceVerification

	if ( strpos( $role_to_remove, 'admin' ) !== false ) {
		$notice_type = 'error no-admin';
	}

	if ( empty( $wp_roles->roles[ $role_to_remove ] ) && 'error no-user' !== $notice_type ) {
		$notice_type    = 'error no-role';
		$notice_message = 'Error removing user role "' . $role_to_remove . '": User role does not exist.';
	}

	if ( ! empty( $wp_roles->roles[ $role_to_remove ] ) && 'success' === $notice_type ) {
		try {
			$role_to_remove_name = $wp_roles->roles[ $role_to_remove ]['name'];

			// remove the role.
			$wp_roles->remove_role( $role_to_remove );

			$notice_message = 'User role "' . $role_to_remove . '" (' . $role_to_remove_name . ') was removed successfully.';

			if ( strpos( $role_to_remove, 'um_' ) !== false ) {
				$um_slug     = str_replace( 'um_', '', $role_to_remove );
				if ( in_array( $um_slug, $um_roles, true ) ) {
					$notice_type = 'success um-role';
					// drop role from the array.
					unset( $um_roles[ array_search( $um_slug, $um_roles, true ) ] );
					// add it back to update the roles.
					update_option( 'um_roles', $um_roles );
					$notice_message .= '<br>Also deleted Ultimate Member role "' . $um_slug . '".';
				} else {
					$notice_type     = 'warning no-matching-um-role';
					$notice_message .= '<br>No matching Ultimate Member role found, please contact your friendly developer for assistance.';
				}
			}
		} catch ( Exception $e ) {
			$notice_message = 'Critical error removing user role "' . $role_to_remove . '": ' . $e->getMessage() . '<br>Please contact your friendly developer for assistance.';
		}
	}

	if ( 'error no-user' === $notice_type ) {
		$notice_message = 'Please select a user role.';
	}

	if ( 'error no-admin' === $notice_type ) {
		$notice_message = "Can't remove administrator roles.";
	}

	$notice = '<div id="ucu_notice_remove_role" class="notice notice-' . $notice_type . ' is-dismissible"> 
	<p><strong>' . $notice_message . '</strong></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';

	echo wp_kses_post( $notice );
}

/**
 * Options page display
 */
function ucu_options_page() {
	?>
	<div class="wrap">
		<h1 class="wp-heading-inline">UnionCentrics Utilities</h1>
		<?php
		ucu_remove_member_role();
		$wp_roles = ( new WP_Roles() )->roles;
		?>
		<form method="post" action="">
			<h3>Ultimate Member Fixes</h3>
			<table class="form-table">
			<tr>
				<th>
					<label for="remove_member_role">Remove User Role</label>
				</th>
				<td>
					<select name="remove_member_role" id="remove_member_role">
						<option value="" selected="selected">Select a role</option>
						<?php foreach ( $wp_roles as $slug => $role ) : ?>
							<?php
							$disabled = '';
							if ( strpos( $slug, 'admin' ) !== false ) {
								$disabled = 'disabled';
							}
							?>
								<option value="<?php echo esc_attr( $slug ); ?>" <?php echo esc_attr( $disabled ); ?>><?php echo esc_html( $role['name'] ); ?> (<?php echo esc_html( $slug ); ?>)</option>
						<?php endforeach; ?>
					</select>
					<input type="submit" name="submit_remove_member_role" id="submit_remove_member_role" class="button" value="Remove" onclick="return getElementById('remove_member_role').value ? confirm( 'Are you sure you want to delete the role ' + getElementById('remove_member_role').value + '?' ) : '';">
					<p class="description">Remove a user role—this will need to be added back through Ultimate Member or otherwise.<br>(Fixes an issue where General Members are unable to edit their profile)</p>
				</td>
			</tr>
			</table>
		</form>
		<form method="post" action="options.php">
			<?php settings_fields( 'ucu_options_group' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row">Email on UM Profile</th>
					<td>
					<label for="ucu_um_allow_edit_email">
							<input type="checkbox" id="ucu_um_allow_edit_email" name="ucu_um_allow_edit_email" <?php echo get_option( 'ucu_um_allow_edit_email' ) === 'on' ? 'checked' : ''; ?> />
							Allow user to edit email on Profile page</label>
							<p class="description">Ultimate Member hides the email field from the profile by default, even if it's in the profile form.</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Edit Password on UM Profile Nav</th>
					<td>
					<label for="ucu_add_profile_nav_buttons">
						<input type="checkbox" id="ucu_add_profile_nav_buttons" name="ucu_add_profile_nav_buttons" <?php echo get_option( 'ucu_add_profile_nav_buttons' ) === 'on' ? 'checked' : ''; ?> />
						Add Password and Privacy Edit buttons to Profile navigation.</label>
					</td>
				</tr>
				<tr>
					<th scope="row">Titles UM Profile Nav Buttons</th>
					<td>
					<label for="ucu_show_um_profile_nav_titles">
						<input type="checkbox" id="ucu_show_um_profile_nav_titles" name="ucu_show_um_profile_nav_titles" <?php echo get_option( 'ucu_show_um_profile_nav_titles' ) === 'on' ? 'checked' : ''; ?> />
						Show nav titles on UM Profile</label>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/**
 * Fix email not showing on profile page
 *
 * @param array $arr_restricted_fields Fields to hide from profile page.
 * @return array $arr_restricted_fields Array of restricted fields.
 */
function ucu_um_restricted_fields( $arr_restricted_fields ) {
	if ( get_option( 'ucu_um_allow_edit_email' ) !== 'on' ) {
		return $arr_restricted_fields;
	}
	$arr_restricted_fields = array_diff( $arr_restricted_fields, array( 'user_email' ) );
	return $arr_restricted_fields;
}
add_filter( 'um_user_profile_restricted_edit_fields', 'ucu_um_restricted_fields' );


/**
 * Add buttons to navigation of User Profile section
 *
 * @return false If option not set.
 */
function ucu_add_profile_nav_buttons() {
	if ( get_option( 'ucu_add_profile_nav_buttons' ) !== 'on' ) {
		return false;
	}
	$change_password_html = '<div class="um-profile-nav-item um-profile-nav-password"><a data-tab="password" href="' . get_site_url() . '/account/password/" class="um-account-link current"><span class="um-account-icontip uimob800-show um-tip-n" original-title="Change Password"><i class="um-faicon-asterisk"></i></span><span class="um-account-icon uimob800-hide"><i class="um-faicon-asterisk"></i></span><span class="um-account-title uimob800-hide title">Change Password</span></a></div>';

	$account_privacy_html = '<div class="um-profile-nav-item um-profile-nav-password"><a data-tab="privacy" href="' . get_site_url() . '/account/privacy/" class="um-account-link"><span class="um-account-icontip uimob800-show um-tip-n" original-title="Privacy"><i class="um-faicon-lock"></i></span><span class="um-account-icon uimob800-hide"><i class="um-faicon-lock"></i></span><span class="um-account-title uimob800-hide title">Privacy</span></a></div>';
	if ( function_exists( 'um_profile_id' ) ) {
		if ( um_profile_id() === get_current_user_id() ) :
			?>
			<script>
				jQuery('.um-profile-nav-item').last().after('<?php echo wp_kses_post( $change_password_html . $account_privacy_html ); ?>');
				</script>
			<?php
		endif;
	}
}
add_action( 'wp_footer', 'ucu_add_profile_nav_buttons', 1000 );

/**
 * Show Ultimate Member profile nav titles
 *
 * @return false If option not set.
 */
function ucu_show_um_profile_nav_titles() {
	if ( get_option( 'ucu_show_um_profile_nav_titles' ) !== 'on' ) {
		return false;
	}
	?>
	<style>
	.um-page-user .um-profile-nav-item .title {
		display: block !important;
	}
	</style>
	<?php
}
add_action( 'wp_head', 'ucu_show_um_profile_nav_titles' );
