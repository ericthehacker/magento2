<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eav\Model\ResourceModel\Attribute;

use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\DB\Select;
use Psr\Log\LoggerInterface;

class ScopeDataLoader
{
    /**
     * @var MetadataPool
     */
    protected $metadataPool;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Eav\Model\Config
     */
    protected $config;

    /**
     * @var \Magento\Framework\EntityManager\TypeResolver
     */
    protected $typeResolver;

    /**
     * ReadHandler constructor.
     *
     * @param MetadataPool $metadataPool
     * @param LoggerInterface $logger
     * @param \Magento\Eav\Model\Config $config
     * @param \Magento\Framework\EntityManager\TypeResolver $typeResolver
     */
    public function __construct(
        MetadataPool $metadataPool,
        LoggerInterface $logger,
        \Magento\Eav\Model\Config $config,
        \Magento\Framework\EntityManager\TypeResolver $typeResolver
    ) {
        $this->metadataPool = $metadataPool;
        $this->logger = $logger;
        $this->config = $config;
        $this->typeResolver = $typeResolver;
    }

    /**
     * Get attribute of given entity type
     *
     * @param string $entityType
     * @return \Magento\Eav\Api\Data\AttributeInterface[]
     * @throws \Exception if for unknown entity type
     */
    protected function getAttributes($entityType)
    {
        $metadata = $this->metadataPool->getMetadata($entityType);
        $eavEntityType = $metadata->getEavEntityType();
        $attributes = (null === $eavEntityType) ? [] : $this->config->getAttributes($eavEntityType);
        return $attributes;
    }

    /**
     * Get array of data from EAV attributes for all scopes in array of following format:
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
     * This method is patterned off of @see \Magento\Eav\Model\ResourceModel\ReadHandler::execute but
     * is heavily modified to return attribute data for all scopes
     *
     * @param string $entityType
     * @param int $entityId
     * @return array
     * @throws \Exception
     * @throws \Magento\Framework\Exception\ConfigurationMismatchException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getAllScopeDataByAttribute($entityType, $entityId)
    {
        $entityData = [];
        $metadata = $this->metadataPool->getMetadata($entityType);

        if (!$metadata->getEavEntityType()) {//todo hasCustomAttributes
            return [];
        }
        $connection = $metadata->getEntityConnection();

        $attributeTables = [];
        $attributesMap = [];
        $selects = [];

        /** @var \Magento\Eav\Model\Entity\Attribute\AbstractAttribute $attribute */
        foreach ($this->getAttributes($entityType) as $attribute) {
            if (!$attribute->isStatic()) {
                $attributeTables[$attribute->getBackend()->getTable()][] = $attribute->getAttributeId();
                $attributesMap[$attribute->getAttributeId()] = $attribute->getAttributeCode();
            }
        }
        if (count($attributeTables)) {
            $attributeTables = array_keys($attributeTables);
            foreach ($attributeTables as $attributeTable) {
                $select = $connection->select()
                    ->from(
                        ['t' => $attributeTable],
                        [
                            'value' => 't.value',
                            'attribute_id' => 't.attribute_id',
                            'store_id' => 't.store_id',
                        ]
                    )
                    ->where($metadata->getLinkField() . ' = ?', $entityId)
                    ->group('value')
                    ->group('attribute_id');

                $selects[] = $select;
            }
            $unionSelect = new \Magento\Framework\DB\Sql\UnionExpression(
                $selects,
                \Magento\Framework\DB\Select::SQL_UNION_ALL
            );

            foreach ($connection->fetchAll($unionSelect) as $attributeValue) {
                if (isset($attributesMap[$attributeValue['attribute_id']])) {
                    $entityData[$attributeValue['attribute_id']][$attributeValue['store_id']] = $attributeValue['value'];
                } else {
                    $this->logger->warning(
                        "Attempt to load value of nonexistent EAV attribute '{$attributeValue['attribute_id']}'
                        for entity type '$entityType'."
                    );
                }
            }
        }

        return $entityData;
    }
}
