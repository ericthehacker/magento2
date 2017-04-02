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
     * Get product data for all scopes in array of following format
     *
     * ```php
     * [
     *     attribute_id_1 => [
     *         store ID X => value of attribute_id_1 for store ID X,
     *         store ID Y => value of attribute_id_1 for store ID Y,
     *         ...
     *     ],
     *     attribute_id_2 => [
     *         store ID X => value of attribute_id_2 for store ID X,
     *         store ID Y => value of attribute_id_2 for store ID Y,
     *         ...
     *     ],
     * ]
     * ```
     *
     * @api
     * @param int $productId
     * @return array
     */
    public function getAllScopeDataByAttribute($productId);
}