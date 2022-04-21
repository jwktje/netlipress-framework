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

        //Set git gateway based on environment
        $cmsEnvironment = getenv('NETLIFY') ? 'production' : 'local';
        if ($cmsEnvironment == 'production' && isset($config->config->backend_production)) {
            unset($config->config->backend);
            $config->config->backend = $config->config->backend_production;
            unset($config->config->backend_production);
        } elseif ($cmsEnvironment == 'local' && isset($config->config->backend_local)) {
            unset($config->config->backend);
            $config->config->backend = $config->config->backend_local;
            unset($config->config->backend_local);
        }

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

            //Load setting files in a settings collection from separate file
            if (file_exists($settingsFile)) {
                $settingsFilesConfig = json_decode(file_get_contents($settingsFile));
                $config->config->collections[] = [
                    "name" => "settings",
                    "label" => "Settings",
                    "label_singular" => "Setting",
                    "format" => "json",
                    "editor" => [
                        "preview" => false
                    ],
                    "files" => $settingsFilesConfig
                ];
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

            //Scan all block template files for a PHPDoc that has field definitions. And if they are unique, add them to the shared field group
            foreach (new \DirectoryIterator(TEMPLATE_BLOCKS_DIR) as $blockFile) {
                //Only handle php files
                if (!$blockFile->isDot() && $blockFile->isFile() && $blockFile->getExtension() == 'php') {
                    //Scan all PHP files in TEMPLATE_BLOCKS_DIR
                    $tokens = token_get_all(file_get_contents($blockFile->getPathname()));
                    foreach ($tokens as $token) {
                        //Check all tokens in the file
                        if ($token[0] === T_DOC_COMMENT) {
                            //We have found a language token in a block file that holds PHPDoc
                            $phpDocData = self::parseDocBlock($token[1], basename($blockFile->getFilename(), '.php'));
                            if (!empty($phpDocData)) {
                                //Add found PHPDoc fields to the blocks field on page and post type
                                foreach (['page', 'post'] as $collection) {
                                    $collectionIndex = array_search($collection, array_column($config->config->collections, 'name'));
                                    if ($collectionIndex !== false) {
                                        //The collection exists, so find the index of the blocks field on this collection
                                        $blocksFieldIndex = array_search('blocks', array_column($config->config->collections[$collectionIndex]->fields, 'name'));
                                        if ($blocksFieldIndex !== false) {
                                            //If no types are set in the config json, add types as an array
                                            if(!isset($config->config->collections[$collectionIndex]->fields[$blocksFieldIndex]->types)) {
                                                $config->config->collections[$collectionIndex]->fields[$blocksFieldIndex]->types = [];
                                            }
                                            //There is a blocks field. So check if this PHPDoc has a unique section, so we don't overwrite an explicit config
                                            $sectionTypeIndex = array_search($phpDocData['name'], array_column($config->config->collections[$collectionIndex]->fields[$blocksFieldIndex]->types, 'name'));
                                            if ($sectionTypeIndex === false) {
                                                //It is unique, so add block to blocks widget
                                                $config->config->collections[$collectionIndex]->fields[$blocksFieldIndex]->types[] = (object) $phpDocData;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $config;
    }

    private static function parseDocBlock($docBlock, $filename)
    {
        $returnData = [];
        $docBlockData = [];
        // split at each line
        foreach (preg_split("/(\r?\n)/", $docBlock) as $line) {
            // if starts with an asterisk
            if (preg_match('/^(?=\s+?\*[^\/])(.+)/', $line, $matches)) {
                $info = $matches[1];
                // remove wrapping whitespace
                $info = trim($info);
                // remove leading asterisk
                $info = preg_replace('/^(\*\s+?)/', '', $info);
                // if it doesn't start with an "@" symbol then add to the description
                if ($info[0] == "@") {
                    // get the name of the param
                    preg_match('/@(\w+)/', $info, $matches);
                    $param_name = $matches[1];
                    // remove the param from the string
                    $value = str_replace("@$param_name ", '', $info);
                    // if the param hasn't been added yet, create a key for it
                    if (!isset($docBlockData[$param_name])) {
                        $docBlockData[$param_name] = [];
                    }
                    // push the param value into place
                    $docBlockData[$param_name][] = $value;
                }
            }
        }
        if (isset($docBlockData['netlipress'])) {
            $returnData['name'] = $filename;
            $returnData['label'] = $docBlockData['netlipress'][0];

            if (isset($docBlockData['var'])) {
                $nestedFieldIndex = false;
                foreach ($docBlockData['var'] as $idx => $var) {
                    $varArray = explode(' ', $var);
                    //Extract required from array and fix the array
                    $required = true;
                    if (($key = array_search('optional', $varArray)) !== false) {
                        $required = false;
                        unset($varArray[$key]);
                        $varArray = array_values($varArray);
                    }

                    //Get widget type. Could be of type nest
                    $widgetType = $varArray[0];

                    //Extract nested parameter and fix the array
                    if ($nestedFieldIndex && $varArray[0] === '-') {
                        unset($varArray[0]);
                        $varArray = array_values($varArray);
                        $nestedWidgetType = $varArray[0];
                        $widgetType = 'nested';
                    } else {
                        $nestedFieldIndex = false;
                    }

                    //Get fieldname
                    $fieldName = $varArray[1];

                    //Fill config by field type;
                    switch($widgetType) {
                        case 'select':
                            $returnData['fields'][$idx] = [
                                'name' => $fieldName,
                                'widget' => 'select',
                                'options' => explode('|', $varArray[2]),
                                'required' => $required
                            ];
                            break;
                        case 'list':
                            $nestedFieldIndex = $idx;
                            $returnData['fields'][$idx] = [
                                'name' => $fieldName,
                                'widget' => 'list',
                                'required' => $required,
                                'fields' => []
                            ];
                            break;
                        case 'nested':
                            $returnData['fields'][$nestedFieldIndex]['fields'][] = [
                                'name' => $fieldName,
                                'widget' => $nestedWidgetType,
                                'required' => $required,
                            ];
                            break;
                        default:
                            $returnData['fields'][$idx] = [
                                'name' => $fieldName,
                                'widget' => $widgetType,
                                'required' => $required
                            ];
                            break;
                    }
                }
            }
        }

        return $returnData;
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
