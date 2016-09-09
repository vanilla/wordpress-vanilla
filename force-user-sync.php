<?php
/**
 * Created by Mikhail.root
 * Date: 01.08.2016
 * Time: 14:36
 */
add_action('admin_footer', 'vf_users_bulk_update_vanilla_sso_user_data');
function vf_users_bulk_update_vanilla_sso_user_data() {
    $screen = get_current_screen();
    if ( $screen->id != "users" )   // Only add to users.php page
        return;
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('<option>').val('update_vanilla_sso_user_data').text('Update VanillaForum users data').appendTo("select[name='action']");
        });
    </script>
    <?php
}

add_action('load-users.php', 'vf_update_sso_users_data');
function vf_update_sso_users_data() {
    if(isset($_GET['action']) && $_GET['action'] === 'update_vanilla_sso_user_data') {  // Check if our custom action was selected
        $users = $_GET['users'];  // Get array of user id's which were selected for meta deletion
        if ($users) {  // If any users were selected
            // lets generate sso string and  try to force data update
            foreach($users as $user_id){
                vf_send_user_data_to_vanilla(intval($user_id));
            }

        }
    }
}
/*
 * Function to manually update user data on vanillaForum
 * */
function vf_send_user_data_to_vanilla($user_id){
    $options = get_option(VF_OPTIONS_NAME);
    $base_vanilla_url = vf_get_value('url', $options, '');
    $clientID = vf_get_value('sso-clientid', $options, '');
    $sso_string=vf_get_sso_string(intval($user_id));
    $data=array(
        'client_id'=>$clientID,
        'sso'=>$sso_string
    );

    $user_url=$base_vanilla_url.'entry/jsconnect?'.http_build_query($data);
    $result=wp_remote_get($user_url);
    return $result;
}

add_action( 'user_register', 'vf_create_vanilla_user_on_registration_save', 10, 1 );
/*
 * Hook when new user is created it will send it's data to vanilla to create it on vanillaForum automatically
 * */
function vf_create_vanilla_user_on_registration_save( $user_id ) {
    $options = get_option(VF_OPTIONS_NAME);
    if('on'===vf_get_value('sso-create-users-on-register', $options, '')){
        vf_send_user_data_to_vanilla($user_id);
    }

}
