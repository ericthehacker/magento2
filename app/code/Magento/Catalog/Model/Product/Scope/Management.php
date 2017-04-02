<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Catalog\Model\Product\Scope;

class Management implements \Magento\Catalog\Api\ProductScopeManagementInterface
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product
     */
    protected $productResourceModel;

    /**
     * @var array
     */
    protected $productScopeData = [];

    /**
     * Management constructor.
     * @param \Magento\Catalog\Model\ResourceModel\Product $productResourceModel
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product $productResourceModel,
        \Magento\Catalog\Model\ProductFactory $productFactory
    ) {
        $this->productResourceModel = $productResourceModel;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllScopeDataByAttribute($productId)
    {
        if(!isset($this->productScopeData[$productId])) {
            /** @var array $scopeData */
            $scopeData = $this->productResourceModel->getAllScopeDataByAttribute($productId);

            $this->productScopeData[$productId] = $scopeData;
        }

        return $this->productScopeData[$productId];
    }
}