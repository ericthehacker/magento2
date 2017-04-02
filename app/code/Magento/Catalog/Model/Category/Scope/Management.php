<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Catalog\Model\Category\Scope;

class Management implements \Magento\Catalog\Api\CategoryScopeManagementInterface
{
    /** 
     * @var \Magento\Catalog\Model\ResourceModel\Category 
     */
    protected $categoryResourceModel;
    
    /**
     * @var array  
     */
    protected $categoryScopeData = [];

    /**
     * Management constructor.
     * @param \Magento\Catalog\Model\ResourceModel\Category $categoryResourceModel
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Category $categoryResourceModel
    ) {
        $this->categoryResourceModel = $categoryResourceModel;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllScopeDataByAttribute($categoryId)
    {
        if (!isset($this->categoryScopeData[$categoryId])) {
            /** @var array $scopeData */
            $scopeData = $this->categoryResourceModel->getAllScopeDataByAttribute($categoryId);

            $this->categoryScopeData[$categoryId] = $scopeData;
        }

        return $this->categoryScopeData[$categoryId];
    }
}