<?php

function gtm_head()
{
    ?>
    <!-- Google Tag Manager -->
    <script>
        (function (w, d, s, l, i) {
            w[l] = w[l] || [];
            w[l].push({'gtm.start': new Date().getTime(), event: 'gtm.js'});
            var f = d.getElementsByTagName(s)[0], j = d.createElement(s), dl = l != 'dataLayer' ? '&l=' + l : '';
            j.async = true;
            j.src = 'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
            f.parentNode.insertBefore(j, f);
        })(window, document, 'script', 'dataLayer', '<?=GTM_CONTAINER_ID;?>');
    </script>
    <!-- End Google Tag Manager -->
    <?php
}

function gtm_body()
{?>
<!-- Google Tag Manager (noscript) -->
    <noscript>
        <iframe src="https://www.googletagmanager.com/ns.html?id=<?= GTM_CONTAINER_ID; ?>"
                height="0" width="0" style="display:none;visibility:hidden"></iframe>
    </noscript>
    <!-- End Google Tag Manager (noscript) -->
    <?php
}


function recaptcha_hide_badge($lang = 'en')
{
    if($lang == 'en') :
    ?>
        <div class="attribution">
            This site is protected by reCAPTCHA and the Google
            <a href="https://policies.google.com/privacy">Privacy Policy</a> and
            <a href="https://policies.google.com/terms">Terms of Service</a> apply.
        </div>
    <?php
    elseif($lang == 'nl') :
    ?>
        <div class="attribution">
            Deze site wordt beschermd door reCAPTCHA en het Google
            <a href="https://policies.google.com/privacy">Privacybeleid</a> en
            <a href="https://policies.google.com/terms">Servicevoorwaarden</a> zijn van toepassing.
        </div>
    <?php endif;?>
    <style>
        .grecaptcha-badge {
            visibility: hidden;
        }
    </style>

}

function recaptcha_output_field()
{
    global $FormIndex;
    //Only output the script tag on the first form index
    if (empty($FormIndex)) {
        echo '<script src="https://www.google.com/recaptcha/api.js?render=' . RECAPTCHA_KEY . '"></script>';
    }
    ?>

    <input type="hidden" name="recaptcha_response" id="recaptchaResponse-<?= $FormIndex; ?>">
    <script>
        grecaptcha.ready(function () {
            grecaptcha.execute('<?=RECAPTCHA_KEY;?>', {action: 'contact'}).then(function (token) {
                document.getElementById('recaptchaResponse-<?=$FormIndex;?>').value = token;
            });
        });
    </script>
<?php }
