<?php

namespace EW\ConfigScopeHints\Helper;
use \Magento\Store\Model\Website;
use \Magento\Store\Model\Store;

class Data extends  \Magento\Framework\App\Helper\AbstractHelper
{
    /** @var \Magento\Framework\App\Helper\Context */
    protected $_context;
    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $_storeManger;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_storeManger = $storeManager;
        $this->_context = $context;
    }

    public function getScopeTree() {
        $tree = array('websites' => array());

        $websites = $this->_storeManger->getWebsites();

        /* @var $website Website */
        foreach($websites as $website) {
            $tree['websites'][$website->getId()] = array('stores' => array());

            /* @var $store Store */
            foreach($website->getStores() as $store) {
                $tree['websites'][$website->getId()]['stores'][] = $store->getId();
            }
        }

        return $tree;
    }

    protected function _getConfigValue($path, $contextScope, $contextScopeId) {
        return $this->_context->getScopeConfig()->getValue($path, $contextScope, $contextScopeId);
    }

    public function getOverridenLevels($path, $contextScope, $contextScopeId) {
        $tree = $this->getScopeTree();

        $currentValue = $this->_getConfigValue($path, $contextScope, $contextScopeId);

        if(is_null($currentValue)) {
            return array(); //something is off, let's bail gracefully.
        }

        $overridden = array();

        switch($contextScope) {
            case 'websites':
                $stores = array_values($tree['websites'][$contextScopeId]['stores']);
                foreach($stores as $storeId) {
                    $value = $this->_getConfigValue($path, 'stores', $storeId);
                    if($value != $currentValue) {
                        $overridden[] = array(
                            'scope'     => 'store',
                            'scope_id'  => $storeId
                        );
                    }
                }
                break;
            case 'default':
                foreach($tree['websites'] as $websiteId => $website) {
                    $websiteValue = $this->_getConfigValue($path, 'websites', $websiteId);
                    if($websiteValue != $currentValue) {
                        $overridden[] = array(
                            'scope'     => 'website',
                            'scope_id'  => $websiteId
                        );
                    }

                    foreach($website['stores'] as $storeId) {
                        $value = $this->_getConfigValue($path, 'stores', $storeId);
                        if($value != $currentValue && $value != $websiteValue) {
                            $overridden[] = array(
                                'scope'     => 'store',
                                'scope_id'  => $storeId
                            );
                        }
                    }
                }
                break;
        }

        return $overridden;
    }

    public function formatOverriddenScopes(\Magento\Config\Block\System\Config\Form $form, array $overridden) {
        $title = __('This setting is overridden at a more specific scope. Click for details.');

        $formatted = '<a class="overridden-hint-list-toggle" title="'. $title .'" href="#">'. $title .'</a>'.
            '<ul class="overridden-hint-list">';

        foreach($overridden as $overriddenScope) {
            $scope = $overriddenScope['scope'];
            $scopeId = $overriddenScope['scope_id'];
            $scopeLabel = $scopeId;

            $url = '#';
            $section = $form->getSectionCode();
            switch($scope) {
                case 'website':
                    $url = $this->_context->getUrlBuilder()->getUrl(
                        '*/*/*',
                        array(
                            'section'=>$section,
                            'website'=>$scopeId
                        )
                    );
                    $scopeLabel = sprintf(
                        'website <a href="%s">%s</a>',
                        $url,
                        $this->_storeManger->getWebsite($scopeId)->getName()
                    );

                    break;
                case 'store':
                    $store = $this->_storeManger->getStore($scopeId);
                    $website = $store->getWebsite();
                    $url = $this->_context->getUrlBuilder()->getUrl(
                        '*/*/*',
                        array(
                            'section'   => $section,
                            'website'   => $website->getCode(),
                            'store'     => $store->getCode()
                        )
                    );
                    $scopeLabel = sprintf(
                        'store view <a href="%s">%s</a>',
                        $url,
                        $website->getName() . ' / ' . $store->getName()
                    );
                    break;
            }

            $formatted .= "<li class='$scope'>Overridden on $scopeLabel</li>";
        }

        $formatted .= '</ul>';

        return $formatted;
    }
}