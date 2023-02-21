<?php

function gtm_head()
{
    ?>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?=GTM_CONTAINER_ID;?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', '<?=GTM_CONTAINER_ID;?>');
    </script>
    <!-- End Google tag (gtag.js) -->
    <?php
}
