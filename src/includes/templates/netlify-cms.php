<!doctype html>
<html>
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <?php \Netlipress\NetlifyCms::outputNetlifyIdentityWidget(); ?>
    <title>Content Manager</title>
</head>
<body>

<script>
    window.CMS_MANUAL_INIT = true;
</script>

<script src="https://unpkg.com/netlify-cms@^2.0.0/dist/netlify-cms.js"></script>

<script>
    const {CMS, initCMS: init} = window
    init(<?=\Netlipress\NetlifyCms::getMinifiedConfig();?>);
</script>
</body>
</html>
