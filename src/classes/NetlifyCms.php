<?php

namespace Netlipress;

class NetlifyCms
{
    private static function getConfig()
    {
        return json_decode(file_get_contents(APP_ROOT . '/web/admin/config.json'));
    }

    public static function render()
    {
        $minifiedNetlifyCmsConfig = json_encode(self::getConfig());
        include(__DIR__ . './../includes/templates/netlify-cms.php');
    }

    public static function usesNetlifyIdentity()
    {
        $config = self::getConfig();
        return isset($config->config->backend->name) && $config->config->backend->name === 'git-gateway';
    }

    public static function outputNetlifyIdentityWidget()
    {
        //https://www.netlifycms.org/docs/add-to-your-site/#add-the-netlify-identity-widget
        if (self::usesNetlifyIdentity()) {
            echo '<script src="https://identity.netlify.com/v1/netlify-identity-widget.js"></script>';
        }
    }

    public static function outputNetlifyIdentityScript()
    {
        //https://www.netlifycms.org/docs/add-to-your-site/#add-the-netlify-identity-widget
        if (self::usesNetlifyIdentity()) {
            echo '
            <script>
                if (window.netlifyIdentity) {
                    window.netlifyIdentity.on("init", user => {
                        if (!user) {
                            window.netlifyIdentity.on("login", () => { document.location.href = "/admin/"; });
                        }
                    });
                }
            </script>';
        }
    }

    public static function getMinifiedConfig()
    {
        return json_encode(self::getConfig());
    }
}
