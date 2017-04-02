<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Catalog\Model\Product\Scope;

class Management implements \Magento\Catalog\Api\ProductScopeManagementInterface
{
    /** @var \Magento\Catalog\Model\ResourceModel\Product */
    protected $productResourceModel;
    /** @var \Magento\Catalog\Model\ProductFactory */
    protected $productFactory;
    /** @var array  */
    protected $productScopeData = [];

    /**
     * Management constructor.
     * @param \Magento\Catalog\Model\ResourceModel\Product $productResourceModel
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product $productResourceModel,
        \Magento\Catalog\Model\ProductFactory $productFactory
    )
    {
        $this->productResourceModel = $productResourceModel;
        $this->productFactory = $productFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getScopeData(int $productId): array
    {
        if(!isset($this->productScopeData[$productId])) {
            /** @var \Magento\Catalog\Model\Product $product */
            $product = $this->productFactory->create();
            $this->productResourceModel->load($product, $productId);

            /** @var array $scopeData */
            $scopeData = $this->productResourceModel->loadScopeData($product);

            $this->productScopeData[$productId] = $scopeData;
        }

        return $this->productScopeData[$productId];
    }
}