<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Catalog\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Api\ProductScopeManagementInterface;
use Magento\Framework\UrlInterface;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Catalog\Api\ProductAttributeManagementInterface;

/**
 * Tier prices modifier adds price type option to tier prices.
 */
class ScopeData extends AbstractModifier
{
    /**
     * @var LocatorInterface
     */
    private $locator;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var ProductScopeManagementInterface
     */
    private $productScopeManagement;

    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * @var ProductAttributeManagementInterface
     */
    private $productAttributeManagement;

    /**
     * @var \Magento\Ui\Model\Config
     */
    private $uiConfig;

    /**
     * @param LocatorInterface $locator
     * @param UrlInterface $url
     * @param ProductScopeManagementInterface $productScopeManagement
     * @param StoreRepositoryInterface $storeRepository
     * @param ProductAttributeManagementInterface $productAttributeManagement
     * @param \Magento\Ui\Model\Config $uiConfig
     */
    public function __construct(
        LocatorInterface $locator,
        UrlInterface $url,
        ProductScopeManagementInterface $productScopeManagement,
        StoreRepositoryInterface $storeRepository,
        ProductAttributeManagementInterface $productAttributeManagement,
        \Magento\Ui\Model\Config $uiConfig
    ) {
        $this->locator = $locator;
        $this->url = $url;
        $this->productScopeManagement = $productScopeManagement;
        $this->storeRepository = $storeRepository;
        $this->productAttributeManagement = $productAttributeManagement;
        $this->uiConfig = $uiConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        if (!$this->uiConfig->isEnabledScopeHints()) {
            return $meta;
        }

        $scopeData = $this->productScopeManagement->getAllScopeDataByAttribute($this->locator->getProduct()->getId());

        // This is technically unnecessary but because getList loads the entire list of stores at once
        // Adding this will prevent separate loads with no overhead if the list has already been cached
        $this->storeRepository->getList();

        $overrideScopes = $this->getOverrideScopes($scopeData);

        foreach ($meta['all-attribute-types']['children'] as $attributeGroupName => $attributeGroup) {
            foreach ($attributeGroup['children'] as $attributeCode => $attributeData) {
                if (array_key_exists($attributeCode, $overrideScopes)) {
                    $meta['all-attribute-types']['children'][$attributeGroupName]['children'][$attributeCode]['arguments']['data']['config']['scopeHint'] = [
//                        'component' => 'Magento_Ui/js/form/components/button',
//                        'displayAsLink' => true,
//                        'formElement' => 'container',
//                        'componentType' => 'container',
//                        'template' => 'ui/form/components/button/container',
                        'template' => 'ui/form/element/helper/scope-hint',
                        'overrideScopes' => $overrideScopes[$attributeCode]
                    ];
                }
            }
        }

        return $meta;
    }

    /**
     * Get scope values that are overridden and structure data ƒor use
     *
     * @param $scopeData
     * @return array
     */
    protected function getOverrideScopes($scopeData)
    {
        $overrideScopes = [];
        $productAttributeList = $this->productAttributeManagement->getAttributes($this->locator->getProduct()->getAttributeSetId());

        foreach ($scopeData as $attributeId => $attributeOverrides) {
            foreach ($attributeOverrides as $storeId => $scopeValue) {
                if ($storeId == 0) {
                    continue;
                }
                if (!$productAttributeList[$attributeId]->getSourceModel()) {
                    $overrideValue = $scopeValue;
                } else {
                    $overrideValue = $productAttributeList[$attributeId]->getSource()->getOptionText($scopeValue);
                }
                $overrideScopes[$productAttributeList[$attributeId]->getAttributeCode()][] = [
                    'scopeLabel' => $this->storeRepository->getById($storeId)->getName(),
                    'overrideValue' => $overrideValue,
                    'scopeUrl' => $this->url->getUrl('*/*/*',
                        [
                            '_current' => true,
                            'store' => $storeId
                        ]
                    )
                ];
            }
        }
        return $overrideScopes;
    }
}
