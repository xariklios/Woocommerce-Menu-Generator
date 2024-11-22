<?php
ini_set('max_execution_time', '300');

require_once("../../../wp-load.php");
define( 'DOING_AJAX', true );


send_origin_headers();

header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
header( 'X-Robots-Tag: noindex' );

if ( empty( $_REQUEST['action'] ) || ! is_scalar( $_REQUEST['action'] ) ) {
    wp_die( '0', 400 );
}

require_once ABSPATH . 'wp-admin/includes/admin.php';
require_once ABSPATH . 'wp-admin/includes/ajax-actions.php';

send_nosniff_header();
nocache_headers();

// do_action( 'admin_init' );
$action = $_REQUEST['action'];

if ( is_user_logged_in() ) {
    
    if ( ! has_action( "wmg_ajax_{$action}" ) ) {
        wp_die( '0', 400 );
    }

    do_action( "wmg_ajax_{$action}" );
}
wp_die( '0' );
