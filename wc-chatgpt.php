<?php

/**
 * Plugin Name: WC ChatGPT Generator
 * Description: Generate descriptions for WC products
 * Author: Oleh Sidun
 */

defined( 'ABSPATH' ) or die;

use WCChatGPT\GPTDescriptionGenerator;
use GuzzleHttp\Exception\RequestException;

/**
 * If WC isn't active
 */
if( ! in_array( 'woocommerce/woocommerce.php', get_option( 'active_plugins' ) ) ) return;

/**
 * Composer autoloader
 */
require_once __DIR__ . '/vendor/autoload.php';

/**
 * Display ChatGPT description field
 */
function wc_product_chatgtp_decs_field_display() {
    require_once __DIR__ . '/src/templates/chatgpt-desc-field.php';
}

add_action( 'woocommerce_product_options_general_product_data', 'wc_product_chatgtp_decs_field_display' );

/**
 * Save ChatGPT description field
 */
function wc_product_chatgtp_decs_field_save( $post_id ) {
    if ( ! isset( $_POST[ '_chatgpt_desc' ] ) ) return;
    
    update_post_meta(
        $post_id, 
        '_chatgpt_desc', 
        $_POST[ '_chatgpt_desc' ] 
    );
}

add_action( 'woocommerce_process_product_meta', 'wc_product_chatgtp_decs_field_save' );

/**
 * Add ChatGPT settings tab
 */
function wc_chatgpt_settings_tab_add() {
    require_once __DIR__ . '/src/templates/settings-tab.php';
}

add_action( 'woocommerce_settings_tabs', 'wc_chatgpt_settings_tab_add' );

/**
 * ChatGPT settings tab content
 */
function wc_chatgpt_settings_tab_content() {
    require_once __DIR__ . '/src/templates/settings-tab-content.php';
}

add_action( 'woocommerce_settings_chatgpt', 'wc_chatgpt_settings_tab_content' );

/**
 * Save ChatGPT settings
 */
function wc_chatgpt_settings_tab_save() {
    if ( isset( $_POST[ '_openai_key' ] ) ) {
        update_option( '_openai_key', $_POST[ '_openai_key' ] );
    }

    if ( isset( $_POST[ '_concurrent_requests' ] ) ) {
        update_option( '_concurrent_requests', $_POST[ '_concurrent_requests' ] );
    }

    if ( isset( $_POST[ '_query_desc' ] ) ) {
        update_option( '_query_desc', $_POST[ '_query_desc' ] );
    }

    if ( isset( $_POST[ '_query_meta_title' ] ) ) {
        update_option( '_query_meta_title', $_POST[ '_query_meta_title' ] );
    }

    if ( isset( $_POST[ '_query_meta_desc' ] ) ) {
        update_option( '_query_meta_desc', $_POST[ '_query_meta_desc' ] );
    }
}

add_action( 'woocommerce_update_options_chatgpt', 'wc_chatgpt_settings_tab_save' );

/**
 * Add ChatGPT page
 */
function wc_chatgpt_page_add() {
   add_submenu_page( 
       'edit.php?post_type=product', 
       __( 'ChatGPT', 'wc-chatgpt' ), 
       __( 'ChatGPT', 'wc-chatgpt' ), 
       'edit_products', 
       'wc-chatgpt', 
       'wc_chatgpt_page_display'
    );
}

add_action( 'admin_menu', 'wc_chatgpt_page_add', 100 );

/**
 * Display ChatGPT page
 */
function wc_chatgpt_page_display() {
   require_once __DIR__ . '/src/templates/chatgpt-page.php';
}

/**
 * Generate ChatGPT description for product
 */
function generate_chatgpt_desc_for_product() {
    $product_id = ( int ) $_GET[ 'product_id' ];

    try {
        $desc = ( new GPTDescriptionGenerator() )->generate( $product_id, [
            'desc', 'meta_title', 'meta_desc',
        ] );

        wp_send_json( [
            'desc' => $desc[ 'desc' ],
            'meta_title' => $desc[ 'meta_title' ],
            'meta_desc' => $desc[ 'meta_desc'] ,
        ] );
    } catch ( Exception $e ) {
        log_error( [
            'code' => $e->getCode(),
            'message' => $e->getMessage(),
            'product_id' => $product_id,
            'time' => date( 'Y-m-d H:i:s' ),
        ] );

        wp_send_json( [
            'error' => $e->getMessage(),
        ], 500 );
    }
}

add_action( 'wp_ajax_generate_product_desc', 'generate_chatgpt_desc_for_product' );

/**
 * Generate ChatGPT description for products
 */
function generate_chatgpt_desc_for_products() {
    set_time_limit(0);
    
    header( 'Content-Type: text/event-stream' );
    header( 'Cache-Control: no-cache' );
    header( 'Connection: keep-alive' );

    switch ( $_GET[ 'action' ] ) {
        case 'generate_empty_desc':
            $products = get_empty_fields();
        break;
        case 'generate_all_desc':
            $products = get_all_fields();
        break;
        case 'generate_fails_desc':
            $products = get_fail_attemps();
        break;
        default:
            $products = [];
    }

    $total = array_reduce( 
        $products, 
        fn ( $acc, $product ) => $acc += count( $product[ 'fields' ] ) 
    );
    
    $progress = [
        'total' => $total,
        'success' => 0,
        'fail' => 0,
    ];
    $fails = [];
    try {
        ( new GPTDescriptionGenerator() )->generateForAll( 
            $products,
            function ( $product_id, $field, $text ) use( &$progress ) {
                switch ( $field ) {
                    case 'desc':
                        update_gpt_desc( $product_id, $text );
                    break;
                    case 'meta_title':
                        update_meta_title( $product_id, $text );
                    break;
                    case 'meta_desc':
                        update_meta_desc( $product_id, $text );
                    break;
                }

                $progress[ 'success' ]++;

                sse( json_encode( [
                    'success' => true,
                    'id' => $product_id,
                    'title' => get_the_title( $product_id ),
                    'field' => $field,
                    'text' => nl2br( $text ),
                    'progress' => $progress,
                ] ), 'progress' );
            },
            function ( RequestException $e, $product_id, $field ) use( &$progress, &$fails ) {            
                log_error( [
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                    'product_id' => $product_id,
                    'time' => date( 'Y-m-d H:i:s' ),
                ] );

                $fails[] = [
                    'id' => $product_id,
                    'fields' => [ $field ],
                ];
                $progress[ 'fail' ]++;

                sse( json_encode( [
                    'success' => false,
                    'id' => $product_id,
                    'title' => get_the_title( $product_id ),
                    'field' => $field,
                    'progress' => $progress,
                ] ), 'progress' );
            } 
        );

        update_option( 'wc-chatgpt-fails', $fails );

        sse( json_encode( [
            'result' => $progress,
        ] ), 'end' );
    } catch ( Exception $e ) {
        log_error( [
            'code' => $e->getCode(),
            'message' => $e->getMessage(),
            'time' => date( 'Y-m-d H:i:s' ),
        ] );

        sse( json_encode( [
            'error' => $e->getMessage(),
        ] ), 'error' );
    }
}

add_action( 'wp_ajax_generate_empty_desc', 'generate_chatgpt_desc_for_products' );
add_action( 'wp_ajax_generate_all_desc', 'generate_chatgpt_desc_for_products' );
add_action( 'wp_ajax_generate_fails_desc', 'generate_chatgpt_desc_for_products' );
