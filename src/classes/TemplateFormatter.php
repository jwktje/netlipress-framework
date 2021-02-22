<?php

namespace Netlipress;

use Parsedown;

class TemplateFormatter
{
    private function getCastsConfig()
    {
        $castsConfig = APP_ROOT . '/admin/casts.json';
        if (!file_exists($castsConfig)) {
            return false;
        }
        return json_decode(file_get_contents($castsConfig));
    }

    public function shouldFormatField($area, $fieldName, $collection = 'page')
    {
        $casts = $this->getCastsConfig();
        if (
            $casts &&
            isset($casts->{$collection}) &&
            isset($casts->{$collection}->{$area}) &&
            isset($casts->{$collection}->{$area}->{$fieldName})) {
            return $casts->{$collection}->{$area}->{$fieldName};
        }
        return false;
    }

    public function formatField($area, $fieldName, $data, $collection = 'page')
    {
        $type = $this->shouldFormatField($area, $fieldName, $collection);
        if ($type === 'markdown') {
            $Parsedown = new Parsedown();
            return $Parsedown->text($data);
        }
    }
}
