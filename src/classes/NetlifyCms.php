<?php

namespace Netlipress;

class NetlifyCms
{
    private static function getConfig($includeFieldGroups = true)
    {
        $configFile = APP_ROOT . '/web/admin/config.json';
        $fieldsFile = APP_ROOT . '/web/admin/fields.json';
        $config = json_decode(file_get_contents($configFile));

        if($includeFieldGroups) {
            if(file_exists($fieldsFile)) {
                $fieldsConfig = json_decode(file_get_contents($fieldsFile));
                //Copy shared fields to all relevant collections
                foreach($fieldsConfig ?? [] as $sharedFieldGroup) {
                    foreach($sharedFieldGroup->collections ?? [] as $collection) {
                        $collectionIndex = array_search($collection, array_column($config->config->collections, 'name'));
                        if($collectionIndex !== false) {
                            //We found a matching collection in the NetlifyCMS config so we should fill the fields from our shared group
                            $config->config->collections[$collectionIndex]->fields = $sharedFieldGroup->fields;
                        }
                    }
                }
            }
        }

        return $config;
    }

    public static function render()
    {
        include(__DIR__ . './../includes/templates/netlify-cms.php');
    }

    public static function usesNetlifyIdentity()
    {
        $config = self::getConfig(false);
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
