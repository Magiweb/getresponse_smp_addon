<?php

use \MW\GetResponse\GetResponse;

add_action( 'swpm_front_end_registration_complete', 'swpm_do_getresponse_signup_rego_complete' ); //For core plugin signup
add_action( 'swpm_front_end_registration_complete_fb', 'swpm_do_getresponse_signup_form_builder' ); //For form builder addon signup
add_action( 'swpm_membership_changed', 'swpm_do_getresponse_signup_membership_changed' ); //For membership upgrade

add_action( 'swpm_admin_end_registration_complete_user_data', 'swpm_do_admin_add_user_getresponse_signup' ); //For member added via admin dashboard.
add_action( 'swpm_admin_end_edit_complete_user_data', 'swpm_do_admin_edit_user_getresponse_signup' ); //For member updated via admin dashboard.
add_action( 'swmp_wpimport_user_imported', 'swpm_do_imported_user_getresponse_signup' ); //For WP user import addon
//check if "Remove Email From List When Subscription Payment is Cancelled" is enabled.
$opts = get_option( 'swpm_getresponse_settings' );
if ( ! empty( $opts[ 'gr_remove_when_cancelled' ] ) ) {
    //it's enabled. Let's add hook handler to remove user from MC list if needed
    add_action( 'swpm_subscription_payment_cancelled', 'swpm_do_getresponse_subscription_payment_cancelled' );
}

function swpm_do_getresponse_subscription_payment_cancelled( $data ) {
    SwpmLog::log_simple_debug( "[GetResponse] Processing swpm_subscription_payment_cancelled hook.", true );
    if ( empty( $ipn_data[ 'member_id' ] ) ) {
	SwpmLog::log_simple_debug( "[GetResponse] No member_id available. Aborting", false );
	return false;
    }
    $member = SwpmMemberUtils::get_user_by_id( $ipn_data[ 'member_id' ] );

    if ( empty( $member ) ) {
	SwpmLog::log_simple_debug( sprintf( "[GetResponse] Can't find member with ID %s. Aborting", $ipn_data[ 'member_id' ] ), false );
	return false;
    }

    if ( empty( $member->membership_level ) ) {
	SwpmLog::log_simple_debug( sprintf( "[GetResponse] Member with ID %s has no membership levels associated with it. Aborting", $ipn_data[ 'member_id' ] ), false );
	return false;
    }

    $gr_list_name = SwpmMembershipLevelCustom::get_value_by_key( $member->membership_level, 'swpm_getresponse_list_name' );

    if ( empty( $gr_list_name ) ) {//This level has no getresponse list name specified for it
	SwpmLog::log_simple_debug( sprintf( "[GetResponse] Membership level %s has no GetResponse lists specified. Aborting", $member->membership_level ), false );
	return false;
    }

    include_once('lib/GetResponse.php');

    $swpm_gr_settings	 = get_option( 'swpm_getresponse_settings' );
    $api_key		 = $swpm_gr_settings[ 'gr_api_key' ];
    if ( empty( $api_key ) ) {
	SwpmLog::log_simple_debug( "[GetResponse] GetResponse API Key value is not saved in the settings. Go to GetResponse settings and enter the API Key.", false );
	return;
    }

    try {
	$api = new GetResponse( $api_key );
    } catch ( Exception $e ) {
	SwpmLog::log_simple_debug( "[GetResponse] GetResponse API error occured: " . $e->getMessage(), false );
	return;
    }

    // Let's check if we have list interest groups delimiter (|) present. Interest groups are entered like the following:
    // my-list-name | groupname1, groupname2, groupname3
    $res_array = explode( '|', $gr_list_name );
    if ( count( $res_array ) > 1 ) {
	// we have interest group(s) specified
	// first, let's set list name
	$gr_list_name = trim( $res_array[ 0 ] );
    }

    $target_list_name		 = $gr_list_name;
    $list_filter			 = array();
    $list_filter[ 'list_name' ]	 = $target_list_name;
    //$args				 = array( 'count' => 100, 'offset' => 0 ); //By default MC API v3.0 returns 10 lists only.
    //$all_lists			 = $api->getCampaigns( $args );
	$all_lists			 = $api->getCampaigns();

    $lists_data			 = $all_lists[ 'lists' ];
    $found_match			 = false;
    foreach ( $lists_data as $list ) {
	SwpmLog::log_simple_debug( "[GetResponse] Checking list name : " . $list[ 'name' ], true );
	if ( strtolower( $list[ 'name' ] ) == strtolower( $target_list_name ) ) {
	    $found_match	 = true;
	    $list_id	 = $list[ 'id' ];
	    SwpmLog::log_simple_debug( "[GetResponse] Found a match for the list name on GetResponse. List ID :" . $list_id, true );
	    break;
	}
    }
    if ( ! $found_match ) {
	SwpmLog::log_simple_debug( "[GetResponse] Could not find a list name in your GetResponse account that matches with the target list name: " . $target_list_name, false );
	return false;
    }
    SwpmLog::log_simple_debug( "[GetResponse] List ID found:" . $list_id . '. Unsubscribing member...', true );

    $member_hash = md5( strtolower( $member->email ) ); //The MD5 hash of the lowercase version of the list member’s email address.

    $api->delete( "lists/" . $list_id . "/members/" . $member_hash );

    if ( ! $api->success() ) {
	SwpmLog::log_simple_debug( "[GetResponse] Unable to delete member from the list.", false );
	SwpmLog::log_simple_debug( "\tError=" . $api->getLastError(), false );
	return false;
    }

    SwpmLog::log_simple_debug( "[GetResponse] Member has been unsubscribed.", true );

    return true;
}

function swpm_do_getresponse_signup_rego_complete() {
    $first_name		 = sanitize_text_field( $_POST[ 'first_name' ] );
    $last_name		 = sanitize_text_field( $_POST[ 'last_name' ] );
    $email			 = sanitize_email( $_POST[ 'email' ] );
    $membership_level	 = sanitize_text_field( $_POST[ 'membership_level' ] );

    //Do the signup
    $args = array( 'first_name' => $first_name, 'last_name' => $last_name, 'email' => $email, 'membership_level' => $membership_level );
    SwpmLog::log_simple_debug( "[GetResponse] Mailchimp integration addon. After registration hook", true );
    swpm_do_getresponse_signup( $args );
}

function swpm_do_getresponse_signup_form_builder( $data ) {
    $first_name		 = sanitize_text_field( isset( $data[ 'first_name' ] ) ? $data[ 'first_name' ] : '' );
    $last_name		 = sanitize_text_field( isset( $data[ 'last_name' ] ) ? $data[ 'last_name' ] : '' );
    $email			 = sanitize_email( $data[ 'email' ] );
    $membership_level	 = sanitize_text_field( $data[ 'membership_level' ] );

    //Do the signup
    $args = array( 'first_name' => $first_name, 'last_name' => $last_name, 'email' => $email, 'membership_level' => $membership_level );
    SwpmLog::log_simple_debug( "[GetResponse] Mailchimp integration addon. After registration hook (form builder)", true );
    swpm_do_getresponse_signup( $args );
}

function swpm_do_getresponse_signup_membership_changed( $data ) {
    if ( ! isset( $data ) && ! is_array( $data ) ) {
	return false;
    }
    //let's check if membership level has changed
    if ( $data[ 'from_level' ] !== $data[ 'to_level' ] ) {
	//it has.
	$member_info				 = $data[ 'member_info' ];
	$member_info[ 'prev_membership_level' ]	 = $data[ 'from_level' ];
	$member_info[ 'membership_level' ]	 = $data[ 'to_level' ];
	SwpmLog::log_simple_debug( "[GetResponse] Mailchimp integration addon. Membership upgrade hook.", true );
	swpm_do_getresponse_signup( $member_info );
	return true;
    }
    return false;
}

function swpm_do_admin_edit_user_getresponse_signup( $member_info ) {
    if ( ! isset( $member_info ) || ! is_array( $member_info ) ) {
	return false;
    }
    //let's check if membership level has changed
    if ( $member_info[ 'membership_level' ] !== $member_info[ 'prev_membership_level' ] ) {
	// it has. Let's proceed with GetResponse signup
	SwpmLog::log_simple_debug( "[GetResponse] Mailchimp integration addon. Admin edit member hook.", true );
	swpm_do_getresponse_signup( $member_info );
	return true;
    }
    return false;
}

function swpm_do_admin_add_user_getresponse_signup( $member_info ) {
    $args = array( 'email' => $member_info[ 'email' ], 'first_name' => $member_info[ 'first_name' ], 'last_name' => $member_info[ 'last_name' ], 'membership_level' => $member_info[ 'membership_level' ] );
    SwpmLog::log_simple_debug( "[GetResponse] Mailchimp integration addon. Admin add member hook.", true );
    swpm_do_getresponse_signup( $args );
}

function swpm_do_imported_user_getresponse_signup( $args ) {
    SwpmLog::log_simple_debug( "[GetResponse] Mailchimp integration addon. Import user hook.", true );
    swpm_do_getresponse_signup( $args );
}

/* This function will do the MC signup given the appropriate args have been passed to it */

function swpm_do_getresponse_signup( $args ) {
    $member_info = $args;
    $first_name = sanitize_text_field( $args[ 'first_name' ] );
    $last_name = sanitize_text_field( $args[ 'last_name' ] );
    $email = sanitize_email( $args[ 'email' ] );
    $membership_level = sanitize_text_field( $args[ 'membership_level' ] );

    $level_id = $membership_level;
    $key = 'swpm_getresponse_list_name';
    $gr_list_name = SwpmMembershipLevelCustom::get_value_by_key( $level_id, $key );

    SwpmLog::log_simple_debug( "[GetResponse] Debug data: " . $gr_list_name . "|" . $email . "|" . $first_name . "|" . $last_name, true );

    if ( empty( $gr_list_name ) ) {//This level has no getresponse list name specified for it
	return;
    }

    SwpmLog::log_simple_debug( "[GetResponse] Doing list signup.", true );

    include_once('lib/GetResponse.php');

    $swpm_gr_settings = get_option( 'swpm_getresponse_settings' );
    $api_key = $swpm_gr_settings[ 'gr_api_key' ];
    if ( empty( $api_key ) ) {
	SwpmLog::log_simple_debug( "[GetResponse] GetResponse API Key value is not saved in the settings. Go to GetResponse settings and enter the API Key.", false );
	return;
    }

    try {
	$api = new GetResponse( $api_key );
    } catch ( Exception $e ) {
	SwpmLog::log_simple_debug( "[GetResponse] GetResponse API error occured: " . $e->getMessage(), false );
	return;
    }

    // Let's check if we have list interest groups delimiter (|) present. Interest groups are entered like the following:
    // my-list-name | groupname1, groupname2, groupname3
    $res_array = explode( '|', $gr_list_name );
    if ( count( $res_array ) > 1 ) {
	// we have interest group(s) specified
	// first, let's set list name
	$gr_list_name		 = trim( $res_array[ 0 ] );
	// now let's get interest group(s). We'll deal with those later.
	$interest_group_names	 = explode( ',', $res_array[ 1 ] );
    }

    $target_list_name		 = $gr_list_name;
    $list_filter			 = array();
    $list_filter[ 'list_name' ]	 = $target_list_name;
//    $args				 = array( 'count' => 100, 'offset' => 0 ); //By default MC API v3.0 returns 10 lits only.
    $all_lists			 = $api->getCampaings();

//    $lists_data			 = $all_lists[ 'lists' ];
    $found_match			 = false;
    foreach ( $all_lists as $list ) {
        SwpmLog::log_simple_debug( "[GetResponse] Checking list name : " . $list[ 'name' ], true );
        if ( strtolower( $list[ 'name' ] ) === strtolower( $target_list_name ) ) {
            $found_match = true;
            $list_id = $list->campaignId;
            SwpmLog::log_simple_debug( "[GetResponse] Found a match for the list name on GetResponse. List ID :" . $list_id, true );
            break;
        }
    }
    if ( ! $found_match ) {
	SwpmLog::log_simple_debug( "[GetResponse] Could not find a list name in your GetResponse account that matches with the target list name: " . $target_list_name, false );
	return;
    }
    SwpmLog::log_simple_debug( "[GetResponse] List ID to subscribe to:" . $list_id, true );

    //If interest groups data is present then prepare the $interests array so it can be used in the API call.
//    if ( isset( $interest_group_names ) ) {
//	//get categories first
//	SwpmLog::log_simple_debug( "[GetResponse] Getting interest categories...", true );
//	$retval = $api->get( "lists/" . $list_id . "/interest-categories/" );
//	if ( ! $api->success() ) {
//	    SwpmLog::log_simple_debug( "[GetResponse] Unable to get interest categories.", false );
//	    SwpmLog::log_simple_debug( "\tError=" . $api->getLastError(), false );
//	    return false;
//	}
//	$categories	 = $retval[ 'categories' ];
//	//get groups for each category
//	$groups		 = array();
//	SwpmLog::log_simple_debug( "[GetResponse] Getting interest groups...", true );
//	foreach ( $categories as $category ) {
//	    $retval = $api->get( "lists/" . $list_id . "/interest-categories/" . $category[ 'id' ] . "/interests/" );
//	    if ( ! $api->success() ) {
//		SwpmLog::log_simple_debug( "[GetResponse] Unable to get interest groups.", false );
//		SwpmLog::log_simple_debug( "\tError=" . $api->getLastError(), false );
//		return false;
//	    }
//	    foreach ( $retval[ 'interests' ] as $group ) {
//		unset( $group[ '_links' ] ); // we don't need that
//		// might be a good idea to store this data in the settings, in order to lower number of requests to API upon signup?
//		$groups[] = $group;
//	    }
//	}
//	$interests = array();
//	//let's compare interest groups provided by user and get their IDs on match
//	foreach ( $interest_group_names as $interest_group_name ) {
//	    $interest_group_name = trim( $interest_group_name );
//	    foreach ( $groups as $group ) {
//		if ( $group[ 'name' ] == $interest_group_name ) {
//		    //name matches, let's add it to interests array
//		    $interests[ $group[ 'id' ] ] = true;
//		}
//	    }
//	}
//    }

    //Enable double opt-in is controlled by status field. Set it to "pending" for double opt-in.
    $status = 'subscribed'; //Don't use double opt-in
    if (! empty ($swpm_gr_settings[ 'gr_enable_double_optin' ])) {
        $status = 'pending'; //Use double opt-in
        SwpmLog::log_simple_debug( "[GetResponse] Double opt-in is enabled. Setting status to: ".$status, true );
    }
    
    //Create the merge_vars data
//    $merge_vars = array( 'FNAME' => $first_name, 'LNAME' => $last_name, 'INTERESTS' => '' );
//
//    $api_arr = array( 'email_address' => $email, 'status_if_new' => $status, 'status' => $status, 'merge_fields' => $merge_vars );
//    if ( isset( $interests ) ) {
//	$api_arr[ 'interests' ] = $interests;
//    }
//
//    $member_hash = md5( strtolower( $email ) ); //The MD5 hash of the lowercase version of the list member’s email address.
//
////    $retval = $api->put( "lists/" . $list_id . "/members/" . $member_hash, $api_arr );

//    if ( ! $api->success() ) {
//	SwpmLog::log_simple_debug( "[GetResponse] Unable to subscribe.", false );
//	SwpmLog::log_simple_debug( "\tError=" . $api->getLastError(), false );
//	return false;
//    }

    $api->addContact([
        'name' => $first_name . " " . $last_name,
        'email' => $email,
        'campaign' => array('campaignId' => $list_id),
    ]);

    // let's check if member level was changed
//    if ( isset( $member_info[ 'prev_membership_level' ] ) && $member_info[ 'prev_membership_level' ] !== false ) {
//	if ( $member_info[ 'prev_membership_level' ] !== $member_info[ 'membership_level' ] ) {
	    //it has. Now let's check if interests are matching those set for current level
//	    if ( $retval[ 'interests' ] != $interests ) {
//		//no match. Let's swtich off interests that aren't in this group
//		foreach ( $retval[ 'interests' ] as $key => $value ) {
//		    $retval[ 'interests' ][ $key ] = false;
//		}
//		SwpmLog::log_simple_debug( "[GetResponse] Modifying interest groups...", true );
//
//		$api_arr[ 'interests' ]	 = $interests + $retval[ 'interests' ];
//		$retval			 = $api->put( "lists/" . $list_id . "/members/" . $member_hash, $api_arr );
//
//		if ( ! $api->success() ) {
//		    SwpmLog::log_simple_debug( "[GetResponse] Unable to modify interest groups.", false );
//		    SwpmLog::log_simple_debug( "\tError=" . $api->getLastError(), false );
//		    return false;
//		}
//	    }
//	}
//    }

    SwpmLog::log_simple_debug( "[GetResponse] GetResponse Signup was successful.", true );
}
