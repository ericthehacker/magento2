<?php

namespace EW\ConfigScopeHints\Model;
use \Magento\Framework\Phrase;

class Plugin
{
    public function afterGetScopeLabel($intercepter, Phrase $value)
    {
        return $value . ': plugin test';
    }
}