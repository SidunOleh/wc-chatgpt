<div class="chatgpt-desc">
    <div class="chatgpt-desc-field">

        <?php 
        woocommerce_wp_textarea_input( [
            'id' => '_chatgpt_desc',
            'label' => __( 'ChatGPT description', 'wc-chatgpt' ),
        ] ) 
        ?>

    </div> 

    <div class="chatgpt-desc-btn">
        <button class="button button-primary button-large">
            <?php _e( 'Generate description', 'wc-chatgpt' ) ?>
        </button>
    </div>
</div>

<script>
    const btn = 
        document.querySelector('.chatgpt-desc-btn button')
    btn.addEventListener('click', function (e) {
        e.preventDefault()
        
        const chatGPTContainer = 
            document.querySelector('.chatgpt-desc')
        const chatGPTVal = 
            document.getElementById('_chatgpt_desc')
        const metaTitleContainer = 
            document.querySelector('.aioseo-row.aioseo-settings-row.snippet-title-row')
        const metaTitleVal = 
            document.querySelector('.aioseo-editor-single .ql-editor p')
        const metaDescContainer = 
            document.querySelector('.aioseo-row.aioseo-settings-row.snippet-description-row')
        const metaDescVal = 
            document.querySelector('.aioseo-editor-description .ql-editor p')
        
        chatGPTContainer.classList.add('loading')
        metaTitleContainer.classList.add('loading')
        metaDescContainer.classList.add('loading')

        const productID = 
            new URLSearchParams(window.location.search).get('post');
        fetch(`/wp-admin/admin-ajax.php?action=generate_product_desc&product_id=${productID}`)
            .then(async (res) => {
                const data = await res.json()

                if (! res.ok) {
                    throw new Error(data.error)
                }

                chatGPTVal.value = data.desc
                metaTitleVal.innerHTML = data.meta_title
                metaDescVal.innerHTML = data.meta_desc
                
                chatGPTContainer.classList.remove('loading')
                metaTitleContainer.classList.remove('loading')
                metaDescContainer.classList.remove('loading')

                alert('Generation is over. Update if you want to save changes.')
            }).catch(err => {
                chatGPTContainer.classList.remove('loading')
                metaTitleContainer.classList.remove('loading')
                metaDescContainer.classList.remove('loading')

                alert(err)
            })
    })
</script>

<style>
    #_chatgpt_desc {
        height: 200px;
    }
    .chatgpt-desc-btn {
        margin: 0px 0px 10px 160px;
    }
    .loading {
        position: relative;
    }
    .loading::before {
        content: "";
        position: absolute;
        z-index: 10;
        top: 0;
        left: 0;
        background: -webkit-gradient(linear, left top, right bottom, color-stop(40%, #eeeeee), color-stop(50%, #dddddd), color-stop(60%, #eeeeee));
        background: linear-gradient(to bottom right, #eeeeee 40%, #dddddd 50%, #eeeeee 60%);
        background-size: 200% 200%;
        background-repeat: no-repeat;
        -webkit-animation: placeholderShimmer 2s infinite linear;
                animation: placeholderShimmer 2s infinite linear;
        height: 108%;
        width: 100%;
        opacity: 0.6;
    }

    @-webkit-keyframes placeholderShimmer {
        0% {
            background-position: 100% 100%;
        }
        100% {
            background-position: 0 0;
        }
    }

    @keyframes placeholderShimmer {
        0% {
            background-position: 100% 100%;
        }
        100% {
            background-position: 0 0;
        }
    }
</style>