<?php

    $parse_uri = explode( 'wp-content', $_SERVER['SCRIPT_FILENAME'] );
    require_once( $parse_uri[0] . 'wp-load.php' );

    $li_state_val = 'McCannNYL1nk3d1nt0k3nstat3'
    $url = '';

    // Get 'em outta here if state isn't set and isn't set right
    if ( ! isset( $_GET['state'] ) || $_GET['state'] != $li_state_val ) {
        
        http_response_code( 401 );
        exit;
    }

    require_once 'vendor/autoload.php';
    session_name('linkedin');
    session_start();

    $redirect_uri       = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['SCRIPT_NAME'];
    $li_oauth_page      = 'http://' . $_SERVER['SERVER_NAME'] . '/wp-admin/admin.php?page=linkedin_oauth';
    $api_key       = get_field( 'linkedin_client_id', 'option' );
    $api_secret    = get_field( 'linkedin_api_secred', 'option' );
    
    $client = new \Splash\LinkedIn\Client( $api_key, $api_secret );

    if ( isset($_GET['logout'] ) ) {
    
        unset( $_SESSION['creds'] );
        echo '<h1>Reset Session</h1>';
    } 
    elseif ( isset( $_GET['error'] ) ) {
        
        echo $error_description = $_GET["error_description"];
        wp_mail();
    } 
    elseif ( isset( $_GET['code'] ) && ! isset( $_SESSION['creds'] ) ) {

        $access_token = $client->fetchAccessToken( $_GET['code'], $redirect_uri );
        $_SESSION['creds'] = $access_token; 
    } 
    elseif ( ! isset( $_SESSION['creds'] ) ) {
    
        $url = $client->getAuthorizationUrl( $redirect_uri );
    }
    if ( isset( $_SESSION['creds'] ) ) {
    
        $oauth_token = $_SESSION['creds']['access_token'];

        //Uncomment these lines to test // '/v1/companies/2529801/updates';
        //$client->setAccessToken( $_SESSION['creds']['access_token'] );
        //$response = $client->fetch('/v1/companies/2529801/updates');
        //var_dump($response);
}

?>
<html>
<style>
    * {
        font-family: sans-serif;
        font-size: 20px;
    }

    div {

        width: 50%;
        margin: 0 auto;
    }

    textarea, button {

        display: block;
        margin-top: 20px;
        width: 100%;
    } 
</style>
<body>
    <div>

        <?php if ( $url ) { ?>

             <form method="post" action="<?php echo $url; ?>">
                <button type="submit">Click to Generate OAuth Token</button>
            </form>
        <?php } ?>
        
        <?php if ( $oauth_token ) { ?>

            <textarea id="oauth-token"><?php echo $oauth_token; ?></textarea>
            <button data-copytarget="#oauth-token">Copy to Clipboard</button>
            <form method="post" action="<?php echo $li_oauth_page; ?>">
                <button type="submit">Go to OAuth Options Page</button>
            </form>
        <?php } ?>
    </div>
    
    <script>
        (function() {

            'use strict';

            // click events
            document.body.addEventListener('click', copy, true);

            // event handler
            function copy(e) {

                // find target element
                var
                t = e.target,
                c = t.dataset.copytarget,
                inp = (c ? document.querySelector(c) : null);

                // is element selectable?
                if (inp && inp.select) {

                // select text
                    inp.select();

                    try {
                    // copy text
                        document.execCommand('copy');
                        inp.blur();
                    }
                    catch (err) {
                        alert('please press Ctrl/Cmd+C to copy');
                    }

                }

            }

        })();
    </script>
</body>
</html>