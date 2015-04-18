<?php

namespace EW\ConfigScopeHints\Model;
use \Magento\Config\Model\Config\Structure\Element\Field;
use \Magento\Framework\Phrase;

class Plugin
{
    /** @var \EW\ConfigScopeHints\Helper\Data */
    protected $_helper;

    public function __construct(\EW\ConfigScopeHints\Helper\Data $helper) {
        $this->_helper = $helper;
    }

    public function aroundGetScopeLabel($intercepter, \Closure $getScopeLabel, Field $field)
    {
        $overriddenLevels = $this->_helper->getOverridenLevels($field);

        /* @var $returnPhrase Phrase */
        $labelPhrase = $getScopeLabel($field);

        if(empty($overriddenLevels)) {
            $scopeHintText = $labelPhrase->getText() . ': phrase plugin text';
        }

        // reconstruct phrase with new text without rendering
        $labelPhrase = new Phrase($scopeHintText, $labelPhrase->getArguments());

        return $labelPhrase;
    }
}