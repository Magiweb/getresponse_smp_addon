<?php
add_action( 'swpm_after_main_admin_menu', 'swpm_gr_do_admin_menu' );

function swpm_gr_do_admin_menu( $menu_parent_slug ) {
    add_submenu_page( $menu_parent_slug, __( "GetResponse", 'simple-membership' ), __( "GetResponse", 'simple-membership' ), 'manage_options', 'swpm-getresponse', 'swpm_gr_admin_interface' );
}

function swpm_gr_admin_interface() {
    echo '<div class="wrap">';
    echo '<h1>GetResponse Integration</h1>';

    if ( isset( $_POST[ 'swpm_gr_save_settings' ] ) ) {
	$options = array(
	    'gr_api_key'			 => $_POST[ 'gr_api_key' ],
            'gr_enable_double_optin'	 => isset( $_POST[ 'gr_enable_double_optin' ] ) ? 1 : 0,
	    'gr_remove_when_cancelled'	 => isset( $_POST[ 'gr_remove_when_cancelled' ] ) ? 1 : 0,
	);
	update_option( 'swpm_getresponse_settings', $options ); //store the results in WP options table
	echo '<div id="message" class="updated fade">';
	echo '<p>GetResponse Settings Saved!</p>';
	echo '</div>';
    }
    $swpm_gr_settings = get_option( 'swpm_getresponse_settings' );

    echo '<div id="poststuff"><div id="post-body">';
    ?>

    <p style="background: #fff6d5; border: 1px solid #d1b655; color: #3f2502; margin: 10px 0;  padding: 5px 5px 5px 10px;">
        Read the <a href="https://simple-membership-plugin.com/signup-members-getresponse-list/" target="_blank">usage documentation</a> to learn how to use the getresponse integration addon
    </p>
    <p>Enter the GetResponse API details below.</p>

    <form action="" method="POST">

        <div class="postbox">
    	<h3 class="hndle"><label for="title">GetResponse Integration Settings</label></h3>
    	<div class="inside">
    	    <table class="form-table">
    		<tr valign="top">
    		    <th scope="row">GetResponse API Key:</th>
    		    <td>
    			<input type="text" name="gr_api_key" value="<?php echo $swpm_gr_settings[ 'gr_api_key' ]; ?>" size="60" />
    			<p class="description">The API Key of your GetResponse account (you can find it under the "Account" tab). Make sure to activate it first.</p>
    		    </td>
    		</tr>
    		<tr valign="top">
    		    <th scope="row"><?php echo __( "Enable Double Opt-In", 'simple-membership' ); ?></th>
    		    <td>
    			<input type="checkbox" name="gr_enable_double_optin" value="1"<?php echo ! empty( $swpm_gr_settings[ 'gr_enable_double_optin' ] ) ? ' checked' : ''; ?> />
    			<p class="description"><?php echo __( "Use this checkbox if you want to use the double opt-in option.", 'simple-membership' ); ?></p>
    		    </td>
    		</tr>                
    		<tr valign="top">
    		    <th scope="row"><?php echo __( "Remove Email From List When Subscription Payment is Cancelled", 'simple-membership' ); ?></th>
    		    <td>
    			<input type="checkbox" name="gr_remove_when_cancelled" value="1"<?php echo ! empty( $swpm_gr_settings[ 'gr_remove_when_cancelled' ] ) ? ' checked' : ''; ?> />
    			<p class="description"><?php echo __( "Remove member's email from GetResponse list when subscription is cancelled.", 'simple-membership' ); ?></p>
    		    </td>
    		</tr>
    	    </table>
    	</div></div>
        <input type="submit" name="swpm_gr_save_settings" value="Save" class="button-primary" />

    </form>


    <?php
    echo '</div></div>'; //end of poststuff and post-body
    echo '</div>'; //end of wrap
}
