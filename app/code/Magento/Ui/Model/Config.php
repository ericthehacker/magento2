<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Ui\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Template;

class Config
{
    /**
     * Configuration path to session storage logging setting
     */
    const XML_PATH_LOGGING = 'dev/js/session_storage_logging';

    /**
     * Configuration path to session storage key setting
     */
    const XML_PATH_KEY = 'dev/js/session_storage_key';

    /**
     * Configuration path to scope override hints display setting
     */
    const XML_PATH_SCOPE_HINTS = 'admin/general/display_scope_hints';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Is session storage logging enabled
     *
     * @return bool
     */
    public function isLoggingEnabled()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_LOGGING);
    }

    /**
     * Get session storage key
     *
     * @return string
     */
    public function getSessionStorageKey()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_KEY);
    }

    /**
     * Get is scope override hints enabled
     *
     * @return bool
     */
    public function isEnabledScopeHints()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_SCOPE_HINTS);
    }
}
