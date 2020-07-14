<?php
/**
 * Plugin Name: UnionCentrics - Utility
 * Plugin URI: https://github.com/icentrics/unioncentrics-utility
 * Description: Utility plugin for UnionCentrics
 * Version: 1.1.0
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
	add_option( 'ucu_add_profile_nav_buttons', 'on' );
	add_option( 'ucu_show_um_profile_nav_titles', 'on' );
	register_setting( 'ucu_options_group', 'ucu_um_allow_edit_email', 'ucu_callback' );
	register_setting( 'ucu_options_group', 'ucu_add_profile_nav_buttons', 'ucu_callback' );
	register_setting( 'ucu_options_group', 'ucu_show_um_profile_nav_titles', 'ucu_callback' );
}
add_action( 'admin_init', 'ucu_register_settings' );

function ucu_register_options_page() {
	add_options_page( 'UnionCentrics Utilities', 'UnionCentrics Utilities', 'manage_options', 'ucu', 'ucu_options_page' );
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


/**
 * Add buttons to navigation of User Profile section
 */
add_action( 'wp_footer', 'ucu_add_profile_nav_buttons', 1000 );
function ucu_add_profile_nav_buttons() {
	if ( get_option('ucu_add_profile_nav_buttons') !== 'on' ) return false;
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

add_action( 'wp_head', 'ucu_show_um_profile_nav_titles' );
function ucu_show_um_profile_nav_titles() {
	if ( get_option('ucu_show_um_profile_nav_titles') !== 'on' ) return false;
	?>
	<style>
	.um-page-user .um-profile-nav-item .title {
		display: block !important;
	}
	</style>
	<?php
}
