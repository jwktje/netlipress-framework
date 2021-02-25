<?php
function recaptcha_hide_badge() {
    ?>
    <div class="attribution">
        This site is protected by reCAPTCHA and the Google
        <a href="https://policies.google.com/privacy">Privacy Policy</a> and
        <a href="https://policies.google.com/terms">Terms of Service</a> apply.
    </div>
    <style>
        .grecaptcha-badge { visibility: hidden; }
    </style>
    <?php
}

function recaptcha_output_field() {
    global $FormIndex;
    //Only output the script tag on the first form index
    if(empty($FormIndex)) {
        echo '<script src="https://www.google.com/recaptcha/api.js?render=' . RECAPTCHA_KEY . '"></script>';
    }
?>

<input type="hidden" name="recaptcha_response" id="recaptchaResponse-<?=$FormIndex;?>">
<script>
    grecaptcha.ready(function () {
        grecaptcha.execute('<?=RECAPTCHA_KEY;?>', { action: 'contact' }).then(function (token) {
            document.getElementById('recaptchaResponse-<?=$FormIndex;?>').value = token;
        });
    });
</script>
<?php }
