<?php

defined( 'ABSPATH' ) or die();

add_action( 'admin_menu', 'er_li_oauth_create_plugin_settings_page' );
add_action( 'admin_init', 'er_li_oauth_setup_sections' );
add_action( 'admin_init', 'er_li_oauth_setup_fields' );

function er_li_oauth_create_plugin_settings_page($li_args) {
    
    $page_title = 'Linkedin Oauth';
    $menu_title = 'Linkedin Oauth';
    $capability = 'manage_options';
    $slug       = 'er_li_oauth_fields';
    $callback   = 'er_li_oauth_settings_page_content';
    $icon       = 'dashicons-admin-plugins';
    $position   = 153;
    
    add_menu_page( $page_title, $menu_title, $capability, $slug, $callback, $icon, $position );
}

function er_li_oauth_settings_page_content() { ?>
    
    <div class="wrap">
        
        <h2>Linkedin OAuth Settings</h2>

        <?php
        
            if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ){
                
                er_li_oauth_admin_notice();
            } 
        ?>
        <form method="POST" action="options.php">
            <?php
                
                settings_fields( 'er_li_oauth_fields' );
                do_settings_sections( 'er_li_oauth_fields' );
                submit_button();
            ?>
        </form>
    </div>

    <?php
}

function er_li_oauth_admin_notice() { ?>

    <div class="notice notice-success is-dismissible">
        
        <p>Your settings have been updated!</p>
    </div><?php
}

function er_li_oauth_setup_sections() {
    
    add_settings_section( 'er_li_oauth_first_section', '', 'er_li_oauth_section_callback', 'er_li_oauth_fields' );
   
}

function er_li_oauth_section_callback( $arguments ) {
    
    switch( $arguments['id'] ){
        
        case 'er_li_oauth_first_section':

            global $li_args;

            $generate_token_url = 'https://www.linkedin.com/uas/oauth2/authorization?response_type=code&client_id=' . $li_args['li_client_id'] . '&redirect_uri=' . $li_args['li_redirect_uri'] . '&state=' . $li_args['li_state_val'];
            echo '<a href="' . $generate_token_url . '">Click here</a> to request a new OAuth token, copy it, then return to this page and pasted it into the field below, then click "Save Changes".';
            break;
    }
}

function er_li_oauth_setup_fields() {

    $time                           = time();
    $updated_datetime               = date( 'm/d/Y g:i:s A', get_option( 'er_li_updated_timestamp' ) );
    $updated_datetime_placeholder   = date( 'm/d/Y g:i:s A', time() );
    $updated_timestamp              = get_option( 'er_li_updated_timestamp' );
    
    $fields = array (
        array (
            'uid'           => 'er_li_oauth_token',
            'label'         => 'Linkedin OAuth Token',  
            'type'          => 'text',
            'section'       => 'er_li_oauth_first_section',
            'placeholder'    => 'Enter OAuth Key Here.'
        ),
        array (
            'uid'           => 'er_li_updated_datetime',   
            'label'         => 'Last Updated Date and Time',
            'section'       => 'er_li_oauth_first_section',
            'type'          => 'text',
            'placeholder'    => $updated_datetime_placeholder,
            'value'         => $updated_datetime,
            
        ),
        array (
            'uid'           => 'er_li_current_timestamp',   
            'label'         => '',
            'section'       => 'er_li_oauth_first_section',
            'type'          => 'hidden',
            'placeholder'    => $time,
        ),

        array (
            'uid'           => 'er_li_updated_timestamp',   
            'label'         => '',
            'section'       => 'er_li_oauth_first_section',
            'type'          => 'hidden',
            'placeholder'    => $time,
            'value'         => $updated_timestamp,
            
        ),

    );       
    
    foreach( $fields as $field ) {


        
        add_settings_field( 
            $field['uid'], 
            $field['label'], 
            'er_li_oauth_field_callback', 
            'er_li_oauth_fields', 
            $field['section'], 
            $field 
        );
        register_setting( 'er_li_oauth_fields', $field['uid'] );
    }
}

function er_li_oauth_field_callback( $arguments ) {
    
    $value = get_option( $arguments['uid'] );
    
    if ( ! $value ) {
        $value = $arguments['default'];
    }

    switch( $arguments['uid'] ) {

        case 'er_li_updated_timestamp': 

            $value = get_option( 'er_li_current_timestamp');
            break;

        case 'er_li_updated_datetime': 
            
            $value = date( 'm/d/Y g:i:s A', absint( get_option( 'er_li_updated_timestamp' ) ) );
            break;
        
        case 'er_li_current_timestamp':
            $value = time();
            break;
    }

    
    printf( '<input size="75" name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" value="%4$s" />', $arguments['uid'], $arguments['type'], $arguments['placeholder'], $value );
}