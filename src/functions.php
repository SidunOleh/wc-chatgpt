<?php

/**
 * Log error
 */
function log_error( array $err ) {
    file_put_contents( 
        __DIR__ . '/logs/error_log', 
        json_encode( $err ) . PHP_EOL, 
        FILE_APPEND 
    );
}

/**
 * Get ChatGPT description
 */
function gpt_desc( int $product_id ) {
    return get_post_meta( $product_id, '_chatgpt_desc', true );
}

/**
 * Update ChatGPT description
 */
function update_gpt_desc( int $product_id, string $desc ) {
    return update_post_meta( $product_id, '_chatgpt_desc',  $desc );
}

/**
 * Get meta title
 */
function get_meta_title( int $product_id ): string|null {
    global $wpdb;

    $meta_title = $wpdb->get_var( "SELECT `title` 
        FROM `{$wpdb->prefix}aioseo_posts`  
        WHERE `post_id` = {$product_id}" );

    return $meta_title;
}

/**
 * Update meta title
 */
function update_meta_title( int $product_id, string $meta_title ) {
    global $wpdb;
    
    $result = $wpdb->update( $wpdb->prefix . 'aioseo_posts', [
        'title' => $meta_title,
    ], [ 'post_id' => $product_id ] );

    if (! $result) {
        $wpdb->insert( $wpdb->prefix . 'aioseo_posts', [
            'post_id' => $product_id,
            'title' => $meta_title,
        ] );
    }
}

/**
 * Get meta title
 */
function get_meta_desc( int $product_id ): string|null {
    global $wpdb;

    $meta_desc = $wpdb->get_var( "SELECT `description` 
        FROM `{$wpdb->prefix}aioseo_posts`
        WHERE `post_id` = {$product_id}" );

    return $meta_desc;
}

/**
 * Update meta desc
 */
function update_meta_desc( int $product_id, string $meta_desc ) {
    global $wpdb;
    
    $result = $wpdb->update( $wpdb->prefix . 'aioseo_posts', [
        'description' => $meta_desc,
    ], [ 'post_id' => $product_id ] );
    

    if (! $result) {
        $wpdb->insert( $wpdb->prefix . 'aioseo_posts', [
            'post_id' => $product_id,
            'description' => $meta_desc,
        ] );
    }
}

/**
 * Get all fields
 */
function get_all_fields(): array {
    $products_ids = wc_get_products( [ 
        'limit' => -1, 
        'return' => 'ids', 
    ] );
    $products = [];
    foreach ( $products_ids as $i => $product_id ) {
        $products[ $i ][ 'id' ] = 
            $product_id;
        $products[ $i ][ 'fields' ] = 
            [ 'desc', 'meta_title', 'meta_desc', ];
    }

    return $products;
}

/**
 * Get empty fields
 */
function get_empty_fields(): array {
    $products_ids = wc_get_products( [ 
        'limit' => -1, 
        'return' => 'ids', 
    ] );
    $products = [];
    foreach ( $products_ids as $i => $product_id ) {
        $products[ $i ][ 'id' ] = 
            $product_id;

        $products[ $i ][ 'fields' ] = [];

        if ( ! gpt_desc( $product_id ) ) {
            $products[ $i ][ 'fields' ][] = 'desc';
        }

        if ( ! get_meta_title( $product_id ) ) {
            $products[ $i ][ 'fields' ][] = 'meta_title';
        }

        if ( ! get_meta_desc( $product_id ) ) {
            $products[ $i ][ 'fields' ][] = 'meta_desc';
        }
    }

    return $products;
}

/**
 * Get fail attemps
 */
function get_fail_attemps(): array {
    return get_option( 'wc-chatgpt-fails' ) ?? [];
}

/**
 * Send SSE
 */
function sse( string $msg, string|null $event = null ): void {
    ob_end_clean();

    if ( $event ) echo "event: {$event}" . PHP_EOL;

    echo "data: {$msg}" . PHP_EOL;
    echo PHP_EOL;
    
    ob_flush();
    flush();
}