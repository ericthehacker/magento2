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

    public function aroundGetScopeLabel(\Magento\Config\Block\System\Config\Form $form, \Closure $getScopeLabel, Field $field)
    {
        $currentScopeId = null;
        switch($form->getScope()) {
            case 'websites':
                $currentScopeId = $form->getWebsiteCode();
                break;
            case 'stores':
                $currentScopeId = $form->getStoreCode();
                break;
        }
        $overriddenLevels = $this->_helper->getOverridenLevels($field->getPath(), $form->getScope(), $currentScopeId);

        /* @var $returnPhrase Phrase */
        $labelPhrase = $getScopeLabel($field);

        if(!empty($overriddenLevels)) {
            $scopeHintText = $labelPhrase->getText() . $this->_helper->formatOverriddenScopes($form, $overriddenLevels);

            // reconstruct phrase with new text without rendering
            $labelPhrase = new Phrase($scopeHintText, $labelPhrase->getArguments());
        }

        return $labelPhrase;
    }
}