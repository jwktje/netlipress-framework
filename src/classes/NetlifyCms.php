<?php

namespace Netlipress;

class NetlifyCms
{
    public static function getConfig($mergeAllConfigFiles = true)
    {
        $configFile = APP_ROOT . '/config/cms/config.json';
        $collectionsFile = APP_ROOT . '/config/cms/collections.json';
        $fieldsFile = APP_ROOT . '/config/cms/fields.json';
        $menuFile = APP_ROOT . '/config/cms/menu.json';
        $settingsFile = APP_ROOT . '/config/cms/settings.json';

        //Get base config file
        $config = json_decode(file_get_contents($configFile));

        if ($mergeAllConfigFiles) {
            //Load collections from separate file
            if (file_exists($collectionsFile)) {
                $collectionsConfig = json_decode(file_get_contents($collectionsFile));
                $config->config->collections = $collectionsConfig;
            }

            //Load menu collection from separate file
            if (file_exists($menuFile)) {
                $menuCollectionConfig = json_decode(file_get_contents($menuFile));
                $config->config->collections[] = $menuCollectionConfig;
            }

            //Load settings collection from separate file
            if (file_exists($settingsFile)) {
                $settingsCollectionConfig = json_decode(file_get_contents($settingsFile));
                $config->config->collections[] = $settingsCollectionConfig;
            }

            //Merge Field groups to collections
            if (file_exists($fieldsFile)) {
                $fieldsConfig = json_decode(file_get_contents($fieldsFile));
                //Copy shared fields to all relevant collections
                foreach ($fieldsConfig ?? [] as $sharedFieldGroup) {
                    foreach ($sharedFieldGroup->collections ?? [] as $collection) {
                        $collectionIndex = array_search($collection, array_column($config->config->collections, 'name'));
                        if ($collectionIndex !== false) {
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
