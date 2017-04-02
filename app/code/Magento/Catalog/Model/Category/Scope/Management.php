<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Catalog\Model\Category\Scope;

class Management implements \Magento\Catalog\Api\CategoryScopeManagementInterface
{
    /** @var \Magento\Catalog\Model\ResourceModel\Category */
    protected $categoryResourceModel;
    /** @var \Magento\Catalog\Model\CategoryFactory */
    protected $categoryFactory;
    /** @var array  */
    protected $categoryScopeData = [];

    /**
     * Management constructor.
     * @param \Magento\Catalog\Model\ResourceModel\Category $categoryResourceModel
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Category $categoryResourceModel,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory
    )
    {
        $this->categoryResourceModel = $categoryResourceModel;
        $this->categoryFactory = $categoryFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getScopeData(int $categoryId): array
    {
        if(!isset($this->categoryScopeData[$categoryId])) {
            /** @var \Magento\Catalog\Model\Category $category */
            $category = $this->categoryFactory->create();
            $this->categoryResourceModel->load($category, $categoryId);

            /** @var array $scopeData */
            $scopeData = $this->categoryResourceModel->loadScopeData($category);

            $this->categoryScopeData[$categoryId] = $scopeData;
        }

        return $this->categoryScopeData[$categoryId];
    }
}