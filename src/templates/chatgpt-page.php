<div class="wrap">
    <div class="chatgpt">
        <div class="chatgpt-options">
            <label for="for-empty">
                <?php _e( 'Generate for empty', 'wc-chatgpt' ) ?>
            </label>
            <input
                checked 
                type="radio" 
                name="action"
                value="generate_empty_desc"
                id="for-empty">
            <label for="for-all">
                <?php _e( 'Generate for all', 'wc-chatgpt' ) ?>
            </label>
            <input 
                type="radio" 
                name="action"
                value="generate_all_desc"
                id="for-all">
        </div>
        <div 
            class="chatgpt-btn" 
            data-action="generate_empty_desc">
            <div class="chatgpt-progress">
            </div>
            <span class="chatgpt-btn-text">
                <?php _e( 'Generate descriptions', 'wc-chatgpt' ) ?>
            </span>
        </div>
        <div class="chatgpt-result">
        </div>
    </div>
</div>

<script>
    const btn = document.querySelector('.chatgpt-btn')
    const btnText = 
        document.querySelector('.chatgpt-btn-text')
    const progressBar = 
        document.querySelector('.chatgpt-progress')
    const result = 
        document.querySelector('.chatgpt-result')
    const radioInputs = 
        document.querySelectorAll('input[name=action]')

    radioInputs.forEach(radio => {
        radio.addEventListener(
            'change', 
            e => btn.setAttribute('data-action', e.currentTarget.value)
        )
    })

    btn.addEventListener('click', function (e) {
        if (
            e.currentTarget.classList.contains('loading') || 
            e.currentTarget.classList.contains('progress')
        ) {
            return
        }

        if (! confirm('ARE YOU SURE YOU WANT TO GENERATE NEW CONTENT?')) {
            return
        }

        btn.classList.add('loading')
        btnText.innerHTML = 'Generation is starting'
        progressBar.style.width = 0
        result.innerHTML = ''

        const source = 
            new EventSource(`/wp-admin/admin-ajax.php?action=${btn.getAttribute('data-action')}`)

        source.addEventListener('progress', function (e) {
            btn.classList.remove('loading')
            btn.classList.add('progress')

            const data = JSON.parse(e.data)
            const field = data.field == 'desc' ? 'Description' : data.field == 'meta_title' ? 'Meta title' : 'Meta description'
            const resultItem = `
                    <div 
                        class="result-item ${data.success ? 'success' : 'fail'}"
                        data-product_id="${data.id}">
                        ${field} ✦ ${data.title}
                        <div class="desc">
                            ${data.text ?? ''}
                        </div>
                    </div>
                `

            result.innerHTML = resultItem + result.innerHTML

            const resultItems = document.querySelectorAll('.result-item')
            for (let i in resultItems) {
                if (i > 19) {
                    resultItems[i].remove()
                }
            }

            const progress = data.progress
            progressBar.style.width = 
                ((progress.success + progress.fail) / progress.total) * 100 + '%'
            btnText.innerHTML = `
                ${progress.success + progress.fail}/${progress.total} 
                <br> 
                Success: ${progress.success}, Fail: ${progress.fail}
            `
        }, false)

        source.addEventListener('end', function (e) {
            source.close()

            const data = JSON.parse(e.data)
            if (data.result.fail > 0) {
                btn.setAttribute('data-action', 'generate_fails_desc')
                btnText.innerHTML += '<br><br>Generate for fails'
            } else {
                btnText.innerHTML += '<br><br>Regenerate'
            }

            btn.classList.remove('loading')
            btn.classList.remove('progress')

            alert('Generation is over.')
        }, false)

        source.addEventListener('error', function(e) {
            source.close()

            const data = JSON.parse(e.data)
            
            btn.classList.remove('loading')
            btn.classList.remove('progress')
            btnText.innerHTML += '<br><br>Retry'

            alert(`Error: ${data.error}`)
        }, false)
    })
</script>

<style>
    .chatgpt {
        font-family: sans-serif;
        font-weight: 600;
    }
    .chatgpt-btn {
        padding: 16px 32px;
        border: 3px solid white;
        border-radius: 5px;
        text-align: center;
        position: relative;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    .chatgpt-progress {
        position: absolute;
        top: 0;
        left: 0;
        width: 0;
        height: 100%;
        border-radius: 5px;
        background-color: #e4e4e4;
        transition: 0.5s width linear;
    }
    .chatgpt-btn:not(.progress):hover .chatgpt-progress {
        background-color: white;
    }
    .chatgpt-btn:not(.loading):not(.progress):hover {
        background-color: white;
        cursor: pointer;
    }
    .chatgpt-btn-text {
        font-size: 18px !important;
        color: black;
        position: relative;
    }
    .chatgpt-result .result-item {
        padding: 8px;
        margin: 5px 0;
        border: 3px solid white;
        border-radius: 5px;
        font-size: 12px;
        color: black;
        transition: 0.2s all linear;
    }
    .chatgpt-result .result-item:not(.loading):hover {
        font-size: 13px;
        cursor: pointer;
    }
    .chatgpt-result .result-item:first-child {
        animation: insertItem 0.5s 1 linear;
    }
    @keyframes insertItem {
        0% {
            opacity: 0;
        }
        100% {
            opacity: 1;
        }
    }
    .chatgpt-result .success {
        background-color: #64c4b7;
    }
    .chatgpt-result .desc {
        opacity: 0;
        visibility: hidden;
        height: 0;
    }
    .chatgpt-result .result-item:not(.loading):hover .desc {
        opacity: 1;
        visibility: visible;
        height: auto;
        cursor: auto !important;
    }
    .chatgpt-result > .success:not(.loading):hover .desc {
        margin-top: 30px;
    }
    .chatgpt-result .fail {
        background-color: #ffb591;
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
    .chatgpt-options {
        margin-bottom: 10px;
    }
</style>