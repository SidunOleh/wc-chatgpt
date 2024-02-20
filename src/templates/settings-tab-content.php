<div class="chatgpt-tab">

    <?php
    woocommerce_wp_text_input( [
        'id' => '_openai_key',
        'value' => wp_unslash( get_option( '_openai_key' ) ),
        'label' => __( 'OpenAI key', 'wc-chatgpt' ),
    ] )
    ?>

    <?php
    woocommerce_wp_text_input( [
        'id' => '_concurrent_requests',
        'type' => 'number',
        'custom_attributes' => [
            'min' => 1,
        ],
        'value' => get_option( '_concurrent_requests' ),
        'label' => __( 'Concurrent requests', 'wc-chatgpt' ),
    ] )
    ?>

    <h2><?php _e( 'Product description', 'wc-chatgpt' ) ?></h2>

    <?php
    woocommerce_wp_textarea_input( [
        'id' => '_query_desc',
        'value' => wp_unslash ( get_option( '_query_desc' ) ),
        'label' => __( 'Query', 'wc-chatgpt' ),
        'rows' => 10,
    ] );
    ?>

    <h2><?php _e( 'Product meta title', 'wc-chatgpt' ) ?></h2>

    <?php
    woocommerce_wp_textarea_input( [
        'id' => '_query_meta_title',
        'value' => wp_unslash( get_option( '_query_meta_title' ) ),
        'label' => __( 'Query', 'wc-chatgpt' ),
        'rows' => 10,
    ] );
    ?>

    <h2><?php _e( 'Product meta description', 'wc-chatgpt' ) ?></h2>

    <?php
    woocommerce_wp_textarea_input( [
        'id' => '_query_meta_desc',
        'value' => wp_unslash ( get_option( '_query_meta_desc' ) ),
        'label' => __( 'Query', 'wc-chatgpt' ),
        'rows' => 10,
    ] );
    ?>
    
</div>

<style>
    .chatgpt-tab {
        margin-top: 18px;
    }
    .chatgpt-tab .form-field {
        display: flex;
        flex-direction: column;
    }
    .chatgpt-tab label {
        font-weight: 600;
        color: black;
        margin-bottom: 5px;
    }
    .chatgpt-tab input, .chatgpt-tab textarea {
        max-width: 600px;
    }
</style> 
