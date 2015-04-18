<?php

namespace EW\ConfigScopeHints\Helper;
use \Magento\Config\Model\Config\Structure\Element\Field;

class Data extends  \Magento\Framework\App\Helper\AbstractHelper
{
    public function getOverridenLevels(Field $field) {
        return [];
    }
}