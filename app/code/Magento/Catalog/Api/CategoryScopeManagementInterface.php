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
interface CategoryScopeManagementInterface
{
    /**
     * Get category data for all scopes in array of following format
     *
     * ```php
     * [
     *     attribute_code_1 => [
     *         store ID X => value of attribute_code_1 for store ID X,
     *         store ID Y => value of attribute_code_1 for store ID Y,
     *         ...
     *     ],
     *     attribute_code_2 => [
     *         store ID X => value of attribute_code_2 for store ID X,
     *         store ID Y => value of attribute_code_2 for store ID Y,
     *         ...
     *     ],
     * ]
     * ```
     *
     * @api
     * @param int $categoryId
     * @return array
     */
    public function getScopeData(int $categoryId) : array;
}