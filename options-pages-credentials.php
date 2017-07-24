<?php

defined( 'ABSPATH' ) or die();

add_action( 'admin_menu', 'er_li_creds_create_plugin_settings_page' );
add_action( 'admin_init', 'er_li_creds_setup_sections' );
add_action( 'admin_init', 'er_li_creds_setup_fields' );

function er_li_creds_create_plugin_settings_page() {
    
    $page_title = 'Linkedin Credentials';
    $menu_title = 'Linkedin Credentials';
    $capability = 'manage_options';
    $slug       = 'er_li_creds_fields';
    $callback   = 'er_li_creds_settings_page_content';
    $icon       = 'dashicons-admin-plugins';
    $position   = 152;
    
    add_menu_page( $page_title, $menu_title, $capability, $slug, $callback, $icon, $position );
}

function er_li_creds_settings_page_content() { ?>
    
    <div class="wrap">
        
        <h2>Linkedin Credentials</h2>

        <?php
        
            if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ){
                
                er_li_creds_admin_notice();
            } 
        ?>
        <form method="POST" action="options.php">
            <?php
                
                settings_fields( 'er_li_creds_fields' );
                do_settings_sections( 'er_li_creds_fields' );
                submit_button();
            ?>
        </form>
    </div>

    <?php
}

function er_li_creds_admin_notice() { ?>

    <div class="notice notice-success is-dismissible">
        
        <p>Your settings have been updated!</p>
    </div><?php
}

function er_li_creds_setup_sections() {
    
    add_settings_section( 'er_li_creds_first_section', '', 'er_li_creds_section_callback', 'er_li_creds_fields' );
   
}

function er_li_creds_section_callback( $arguments ) {
    
    switch( $arguments['id'] ){
        
        case 'er_li_creds_first_section':
            //echo 'Section text';
            break;
    }
}
function er_li_creds_setup_fields() {
    
    $fields = array(
        
        array(
            'uid'           => 'er_li_creds_client_id',
            'label'         => 'Linkedin Client ID',
            'section'       => 'er_li_creds_first_section',
            'type'          => 'text',
            'placeholder'   => 'Linkedin Client ID',
        ),
        array(
            'uid'           => 'er_li_creds_api_secret',
            'label'         => 'Linkedin API Secret',
            'section'       => 'er_li_creds_first_section',
            'type'          => 'text',
            'placeholder'   => 'Linkedin API Secret',
        ),
        array(
            'uid'           => 'er_li_creds_company_id',
            'label'         => 'Company ID',
            'section'       => 'er_li_creds_first_section',
            'type'          => 'text',
            'placeholder'   => 'Company ID',
        ),
        array(
            'uid'           => 'er_li_state_value',
            'label'         => 'Linkedin State Value',
            'section'       => 'er_li_creds_first_section',
            'type'          => 'text',
            'placeholder'   => 'Enter a random string of characters',
        ),

    );
    
    foreach( $fields as $field ) {
        
        add_settings_field( $field['uid'], $field['label'], 'er_li_creds_field_callback', 'er_li_creds_fields', $field['section'], $field );
        register_setting( 'er_li_creds_fields', $field['uid'] );
    }
}

function er_li_creds_field_callback( $arguments ) {
    
    $value = get_option( $arguments['uid'] );
    
    if( ! $value ) {
        $value = $arguments['default'];
    }
    
    switch( $arguments['type'] ){
        
        case 'text':
            printf( '<input size="75" name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" value="%4$s" />', $arguments['uid'], $arguments['type'], $arguments['placeholder'], $value );
            break;
    }
}