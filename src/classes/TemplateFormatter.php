<?php

namespace Netlipress;

use Parsedown;

class TemplateFormatter
{
    private $widgetsToFormat = ['markdown'];

    public function shouldFormatField($fieldName, $blockName = null)
    {
        $config = NetlifyCms::getConfig();
        $collection = get_post_type();
        $collectionIndex = array_search($collection, array_column($config->config->collections, 'name'));
        if($collectionIndex !== false) {
            //We found a matching collection in the NetlifyCMS config
            $collectionFieldsConfig = $config->config->collections[$collectionIndex]->fields;
        } else {
            return false;
        }
        if(!is_null($blockName)) {
            $blocksFieldIndex = array_search('blocks', array_column($collectionFieldsConfig, 'name'));
            if($blocksFieldIndex !== false) {
                $blocksFieldTypesConfig = $collectionFieldsConfig[$blocksFieldIndex]->types;
                $currentBlockFieldsIndex = array_search($blockName, array_column($blocksFieldTypesConfig, 'name'));
                if($currentBlockFieldsIndex !== false) {
                    $currentBlockFieldsConfig = $blocksFieldTypesConfig[$currentBlockFieldsIndex]->fields;
                    $currentBlockCurrentFieldIndex = array_search($fieldName, array_column($currentBlockFieldsConfig, 'name'));
                    if($currentBlockCurrentFieldIndex !== false) {
                        $fieldConfig = $currentBlockFieldsConfig[$currentBlockCurrentFieldIndex];
                    }
                }
            } else {
                return false;
            }
        } else {
            $fieldConfigIndex = array_search($fieldName, array_column($collectionFieldsConfig, 'name'));
            if($fieldConfigIndex !== false) {
                //We found a matching collection in the NetlifyCMS config
                $fieldConfig = $collectionFieldsConfig[$fieldConfigIndex];
            } else {
                return false;
            }
        }
        if(in_array($fieldConfig->widget ?? null, $this->widgetsToFormat)) {
            return $fieldConfig->widget;
        }
        return false;
    }

    public function formatField($fieldName, $data, $blockName = null)
    {
        $type = $this->shouldFormatField($fieldName, $blockName);
        if ($type === 'markdown') {
            return (new \Parsedown)->text($data);
        }
        return $data;
    }
}
