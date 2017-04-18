<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Catalog\Api;

/**
 * @api
 */
interface ProductScopeManagementInterface
{
    /**
     * Get product data for all scopes
     *
     * @see \Magento\Eav\Model\ResourceModel\Attribute\DataLoader::getAllScopeDataByAttribute for array pattern
     *
     * @api
     * @param int $productId
     * @return array
     */
    public function getAllScopeDataByAttribute($productId);
}