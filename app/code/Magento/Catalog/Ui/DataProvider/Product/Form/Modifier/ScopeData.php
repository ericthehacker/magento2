<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Catalog\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Api\ProductScopeManagementInterface;
use Magento\Framework\Exception\LocalizedException;
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
     * @param LocatorInterface $locator
     * @param UrlInterface $url
     * @param ProductScopeManagementInterface $productScopeManagement
     * @param StoreRepositoryInterface $storeRepository
     * @param ProductAttributeManagementInterface $productAttributeManagement
     */
    public function __construct(
        LocatorInterface $locator,
        UrlInterface $url,
        ProductScopeManagementInterface $productScopeManagement,
        StoreRepositoryInterface $storeRepository,
        ProductAttributeManagementInterface $productAttributeManagement
    ) {
        $this->locator = $locator;
        $this->url = $url;
        $this->productScopeManagement = $productScopeManagement;
        $this->storeRepository = $storeRepository;
        $this->productAttributeManagement = $productAttributeManagement;
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
        $overrideScopes = [];
        $scopeData = $this->productScopeManagement->getAllScopeDataByAttribute($this->locator->getProduct()->getId());
//         [
//              attribute_code_1 => [
//                  store ID X => value of attribute_code_1 for store ID X,
//                  store ID Y => value of attribute_code_1 for store ID Y,
//                  ...
//              ],
//              attribute_code_2 => [
//                  store ID X => value of attribute_code_2 for store ID X,
//                  store ID Y => value of attribute_code_2 for store ID Y,
//                  ...
//              ],
//          ]

        $productAttributeList = $this->productAttributeManagement->getAttributes($this->locator->getProduct()->getAttributeSetId());



        // This is technically unnecesary but because getList loads the entire list of stores at once
        // Adding this will prevent separate loads with no overhead if the list has already been cached
        $this->storeRepository->getList();



        foreach ($scopeData as $attributeId => $attributeOverrides) {
            foreach ($attributeOverrides as $storeId => $scopeValue) {
                if ($storeId == 0) {
                    continue;
                }
                $overrideValue = $productAttributeList[$attributeId]->getSource()->getOptionText($scopeValue);
                if (empty($overrideValue)) {
                    $overrideValue = $scopeValue;
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

        foreach ($meta['all-attribute-types']['children'] as $attributeGroupName => $attributeGroup) {
            foreach ($attributeGroup['children'] as $attributeCode => $attributeData) {
                if (array_key_exists($attributeCode, $overrideScopes)) {
                    $meta['all-attribute-types']['children'][$attributeGroupName]['children'][$attributeCode]['arguments']['data']['config']['scopeHint'] = [
                        'template' => 'ui/form/element/helper/scope-hint',
                        'overrideScopes' => $overrideScopes[$attributeCode]
                    ];
                }
            }
        }

////        $advancedPricingButton['arguments']['data']['config'] = [
////            'displayAsLink' => true,
////            'formElement' => Container::NAME,
////            'componentType' => Container::NAME,
////            'component' => 'Magento_Ui/js/form/components/button',
////            'template' => 'ui/form/components/button/container',
////            'actions' => [
////                [
////                    'targetName' => $this->scopeName . '.advanced_pricing_modal',
////                    'actionName' => 'toggleModal',
////                ]
////            ],
////            'title' => __('Advanced Pricing'),
////            'additionalForGroup' => true,
////            'provider' => false,
////            'source' => 'product_details',
////            'sortOrder' =>
////                $this->arrayManager->get($pricePath . '/arguments/data/config/sortOrder', $this->meta) + 1,
////        ];
//
//        $meta['scopeHint']['arguments']['data']['config'] = [
//            'componentType' => 'field',
////                        'displayAsLink' => true,
//            'formElement' => \Magento\Ui\Component\Container::NAME,
////            'component' => 'Magento_Ui/js/form/components/field',
//            'template' => 'ui/form/element/helper/scope-hint',
////            'actions' => [
////                [
////                    'targetName' => $this->scopeName . '.advanced_pricing_modal',
////                    'actionName' => 'toggleModal',
////                ]
////            ],
////            'title' => __('Advanced Pricing'),
////            'additionalForGroup' => true,
////            'provider' => false,
////            'source' => 'product_details',
////            'sortOrder' =>
////                $this->arrayManager->get($pricePath . '/arguments/data/config/sortOrder', $this->meta) + 1,
//        ];

        return $meta;
    }
}
