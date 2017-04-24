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
use Magento\Ui\Component\Container;
use Magento\Ui\Component\Modal;

/**
 * Tier prices modifier adds price type option to tier prices.
 */
class ScopeData extends AbstractModifier
{
    /**
     * Suffix of scope hint modal name
     */
    const SCOPE_HINT_MODAL_SUFFIX = '_scope_hint_modal';

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
     * @var array
     */
    private $meta;

    /**
     * @var \Magento\Framework\Stdlib\ArrayManager
     */
    private $arrayManager;

    /**
     * @var string
     */
    private $scopeName;

    /**
     * ScopeData constructor.
     * @param LocatorInterface $locator
     * @param UrlInterface $url
     * @param ProductScopeManagementInterface $productScopeManagement
     * @param StoreRepositoryInterface $storeRepository
     * @param ProductAttributeManagementInterface $productAttributeManagement
     * @param \Magento\Ui\Model\Config $uiConfig
     * @param \Magento\Framework\Stdlib\ArrayManager $arrayManager
     * @param string $scopeName
     */
    public function __construct(
        LocatorInterface $locator,
        UrlInterface $url,
        ProductScopeManagementInterface $productScopeManagement,
        StoreRepositoryInterface $storeRepository,
        ProductAttributeManagementInterface $productAttributeManagement,
        \Magento\Ui\Model\Config $uiConfig,
        \Magento\Framework\Stdlib\ArrayManager $arrayManager,
        $scopeName = 'product_form.product_form' //@todo: should be empty in constructor and passed in via DI
    ) {
        $this->locator = $locator;
        $this->url = $url;
        $this->productScopeManagement = $productScopeManagement;
        $this->storeRepository = $storeRepository;
        $this->productAttributeManagement = $productAttributeManagement;
        $this->uiConfig = $uiConfig;
        $this->arrayManager = $arrayManager;
        $this->scopeName = $scopeName;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        return $data;
    }

    /**
     * Gets formatted override value string to be displayed to user
     *
     * @param mixed $overrideValue
     * @return string
     */
    private function getOverrideValueString($overrideValue) {
        $string = $overrideValue;

        if(is_array($overrideValue)) {
            $string = implode("\n", $overrideValue); //@todo: quick and dirty solution
        }
        return nl2br($string); //@todo: html escape
    }

    /**
     * Adds scope hint modal for given attribute
     *
     * @param string $attributeCode
     * @param array $overridenScopes
     * @return $this
     */
    private function addScopeHintModal($attributeCode, array $overridenScopes) {
        $overrideScopeDetails = [];
        foreach($overridenScopes as $storeCode => $overrideInfo) {
            $overrideScopeDetails[$storeCode] = [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'component' => 'Magento_Ui/js/form/components/html',
                            'componentType' => Container::NAME,
                            'additionalClasses' => 'override-scope-hint',
                            'content' => sprintf(
                                '<span class="override-scope">%s</span>' .
                                '<span class="override-value">%s</span>', //@todo: HTML in strings makes me sad
                                $overrideInfo['scopeLabel'],
                                $this->getOverrideValueString($overrideInfo['overrideValue'])
                            ),
                        ]
                    ]
                ]
            ];
        }

        $this->meta[$attributeCode . self::SCOPE_HINT_MODAL_SUFFIX] = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'isTemplate' => false,
                        'componentType' => Modal::NAME,
                        'dataScope' => '',
                        'options' => [
                            'title' => __('Storeviews with different values'),
                            'buttons' => [],
                        ],
                    ]
                ]
            ],
            'children' => [
                'override_scopes_container' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'componentType' => 'fieldset',
                                'label' => '',
                                'opened' => 1,
                            ]
                        ]
                    ],
                    'children' => $overrideScopeDetails
                ]
            ]
        ];

        return $this;
    }

    /**
     * Adds a scope hint link below product attribute
     *
     * @param string $attributeGroupKey
     * @param string $fieldContainerKey
     * @param string $attributeCode
     * @param array $overridenScopes
     * @return $this
     */
    private function addScopeHintLink($attributeGroupKey, $fieldContainerKey, $attributeCode, array $overridenScopes) {
        //@todo: get path without string concatenation?
        $path = "$attributeGroupKey/children/$fieldContainerKey/children/$attributeCode";

        $scopeHintButton['arguments']['data']['config'] = [
            'displayAsLink' => true,
            'formElement' => Container::NAME,
            'componentType' => Container::NAME,
            'component' => 'Magento_Ui/js/form/components/button',
            'template' => 'ui/form/components/button/container',
            'actions' => [
                [
                    'targetName' => $this->scopeName . '.' . $attributeCode . self::SCOPE_HINT_MODAL_SUFFIX,
                    'actionName' => 'toggleModal',
                ]
            ],
            'title' => __('%1 storeview(s) have a different value', count($overridenScopes)),
            'additionalForGroup' => true,
            'provider' => false,
            'sortOrder' =>
                $this->arrayManager->get($path . '/arguments/data/config/sortOrder', $this->meta) + 1,
        ];

        $this->meta = $this->arrayManager->set(
            $this->arrayManager->slicePath($path, 0, -1) . '/scope_hint_button',
            $this->meta,
            $scopeHintButton
        );

        return $this;
    }

    /**
     * Loops over product attributes and adds scope hint
     * links and modals as necessary.
     *
     * @return $this
     */
    private function addScopeHintLinks() {
        $scopeData = $this->productScopeManagement->getAllScopeDataByAttribute($this->locator->getProduct()->getId());

        // This is technically unnecessary but because getList loads the entire list of stores at once
        // Adding this will prevent separate loads with no overhead if the list has already been cached
        $this->storeRepository->getList();

        $overrideScopes = $this->getOverrideScopes($scopeData);

        //@todo: these nested loops are kind of ugly ...
        foreach ($this->meta as $attributeGroupKey => $attributeGroup) {
            foreach ($attributeGroup['children'] as $fieldContainerKey => $fieldContainer) {
                if(!isset($fieldContainer['children'])) {
                    continue;
                }

                foreach ($fieldContainer['children'] as $attributeCode => $attributeData) {
                    if (!array_key_exists($attributeCode, $overrideScopes)) {
                        continue;
                    }

                    $this->addScopeHintLink(
                        $attributeGroupKey,
                            $fieldContainerKey,
                            $attributeCode,
                            $overrideScopes[$attributeCode]
                    );
                    $this->addScopeHintModal($attributeCode, $overrideScopes[$attributeCode]);
                }
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        if (!$this->uiConfig->isEnabledScopeHints()) {
            return $meta;
        }

        $this->meta = $meta;

        $this->addScopeHintLinks();

        return $this->meta;
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
                $overrideScopes[$productAttributeList[$attributeId]->getAttributeCode()][$this->storeRepository->getById($storeId)->getCode()] = [
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
