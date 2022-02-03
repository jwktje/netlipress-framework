<?php

namespace Netlipress;

use Parsedown;

class TemplateFormatter
{
    private $widgetsToFormat = ['markdown'];

    public function shouldFormatField($fieldName, $sectionName = null)
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
        if(!is_null($sectionName)) {
            $sectionsFieldIndex = array_search('sections', array_column($collectionFieldsConfig, 'name'));
            if($sectionsFieldIndex !== false) {
                $sectionsFieldTypesConfig = $collectionFieldsConfig[$sectionsFieldIndex]->types;
                $currentSectionFieldsIndex = array_search($sectionName, array_column($sectionsFieldTypesConfig, 'name'));
                if($currentSectionFieldsIndex !== false) {
                    $currentSectionFieldsConfig = $sectionsFieldTypesConfig[$currentSectionFieldsIndex]->fields;
                    $currentSectionCurrentFieldIndex = array_search($fieldName, array_column($currentSectionFieldsConfig, 'name'));
                    if($currentSectionCurrentFieldIndex !== false) {
                        $fieldConfig = $currentSectionFieldsConfig[$currentSectionCurrentFieldIndex];
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

    public function formatField($fieldName, $data, $sectionName = null)
    {
        $type = $this->shouldFormatField($fieldName, $sectionName);
        if ($type === 'markdown') {
            return (new \Parsedown)->text($data);
        }
        return $data;
    }
}
