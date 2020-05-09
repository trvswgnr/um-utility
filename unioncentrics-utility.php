<?php
/**
 * Plugin Name: UnionCentrics - Utility
 * Plugin URI: https://github.com/icentrics/unioncentrics-utility
 * Description: Utility plugin for UnionCentrics
 * Version: 1.0.0
 * Author: UnionCentrics
 * Author URI: http://unioncentrics.com
 *
 * @package unioncentrics-utility
 * @author Travis Aaron Wagner
 */

// @todo: document functions.
// @todo: move functions to class.

function ucu_register_settings() {
	add_option( 'ucu_um_allow_edit_email', 'on' );
	register_setting( 'ucu_options_group', 'ucu_um_allow_edit_email', 'ucu_callback' );
}
add_action( 'admin_init', 'ucu_register_settings' );

function ucu_register_options_page() {
	add_options_page( 'UnionCentrics Utilities', 'UnionCentrics', 'manage_options', 'ucu', 'ucu_options_page' );
}
add_action( 'admin_menu', 'ucu_register_options_page' );

function ucu_reset_um_general_member() {
	if ( ! isset( $_POST['reset_um_general_member'] ) ) {
		return false;
	}
	echo '<div id="setting-error-settings_updated" class="notice notice-success settings-error is-dismissible"> 
	<p><strong>General Member role reset.</strong></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
	$wp_roles = new WP_Roles();
	$wp_roles->remove_role( 'um_general-member' );
	// add_role( 'um_general-member', 'General Member', array( 'read' => true ) );
}

function ucu_options_page() {
	?>
	<div class="wrap">
		<h1 class="wp-heading-inline">UnionCentrics Utilities</h1>
		<?php ucu_reset_um_general_member(); ?>
		<form method="post" action="">
			<h3>Ultimate Member Fixes</h3>
			<table class="form-table">
			<tr>
				<th>
					<label for="reset_um_general_member">Remove UM General Member</label>
				</th>
				<td>
					<input type="submit" name="reset_um_general_member" id="reset_um_general_member" class="button" value="Remove">
					<p class="description">Remove the General Member role—this will need to be added back through Ultimate Member.<br>(Fixes an issue where General Members are unable to edit their profile)</p>
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
 * @return $arr_restricted_fields
 */
function ucu_um_restricted_fields( $arr_restricted_fields ) {
	if ( get_option( 'ucu_um_allow_edit_email' ) !== 'on' ) {
		return $arr_restricted_fields;
	}
	$arr_restricted_fields = array_diff( $arr_restricted_fields, array( 'user_email' ) );
	return $arr_restricted_fields;
}
add_filter( 'um_user_profile_restricted_edit_fields', 'ucu_um_restricted_fields' );
