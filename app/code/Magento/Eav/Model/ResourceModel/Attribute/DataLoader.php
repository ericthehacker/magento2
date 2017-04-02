<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eav\Model\ResourceModel\Attribute;

use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Model\Entity\ScopeInterface;
use Magento\Framework\Model\Entity\ScopeResolver;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\DB\Select;
use Psr\Log\LoggerInterface;

class DataLoader
{
    /**
     * @var MetadataPool
     */
    protected $metadataPool;

    /**
     * @var ScopeResolver
     */
    protected $scopeResolver;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /** @var \Magento\Eav\Model\Config */
    private $config;

    /**
     * @var \Magento\Framework\EntityManager\TypeResolver
     */
    protected $typeResolver;

    /**
     * ReadHandler constructor.
     *
     * @param MetadataPool $metadataPool
     * @param ScopeResolver $scopeResolver
     * @param LoggerInterface $logger
     * @param \Magento\Eav\Model\Config $config
     * @param \Magento\Framework\EntityManager\TypeResolver $typeResolver
     */
    public function __construct(
        MetadataPool $metadataPool,
        ScopeResolver $scopeResolver,
        LoggerInterface $logger,
        \Magento\Eav\Model\Config $config,
        \Magento\Framework\EntityManager\TypeResolver $typeResolver
    ) {
        $this->metadataPool = $metadataPool;
        $this->scopeResolver = $scopeResolver;
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
     * @param ScopeInterface $scope
     * @return array
     */
    protected function getContextVariables(ScopeInterface $scope)
    {
        $data[] = $scope->getValue();
        if ($scope->getFallback()) {
            $data = array_merge($data, $this->getContextVariables($scope->getFallback()));
        }
        return $data;
    }

    /**
     * @param $entityType
     * @param $entityId
     * @return array
     * @throws \Exception
     */
    public function getAllScopeDataByAttribute($entityType, $entityId)
    {
        $metadata = $this->metadataPool->getMetadata($entityType);
        $entityData[$metadata->getLinkField()] = $entityId;
        return $this->getData($entityType, $entityData, [], true);
    }

    /**
     * @param string $entityType
     * @param array $entityData
     * @param array $arguments
     * @param bool $loadAllScopes
     * @return array
     * @throws \Exception
     * @throws \Magento\Framework\Exception\ConfigurationMismatchException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getData($entityType, $entityData, $arguments = [], $loadAllScopes = false)
    {
        $metadata = $this->metadataPool->getMetadata($entityType);

        if (!$metadata->getEavEntityType()) {//todo hasCustomAttributes
            return $entityData;
        }
        $context = $this->scopeResolver->getEntityContext($entityType, $entityData);
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
                $from = ['value' => 't.value', 'attribute_id' => 't.attribute_id'];
                if ($loadAllScopes) {
                    $from['store_id'] = 't.store_id';
                }
                $select = $connection->select()
                    ->from(
                        ['t' => $attributeTable],
                        $from
                    )
                    ->where($metadata->getLinkField() . ' = ?', $entityData[$metadata->getLinkField()]);

                if ($loadAllScopes) {
                    $this->addGroupForAllScopeData($select);
                } else {
                    $this->addScopeToSelect($select, $context, $metadata);
                }
                $selects[] = $select;
            }
            $unionSelect = new \Magento\Framework\DB\Sql\UnionExpression(
                $selects,
                \Magento\Framework\DB\Select::SQL_UNION_ALL
            );

            $entityData = $this->populateData(
                $connection,
                $unionSelect,
                $attributesMap,
                $loadAllScopes,
                $entityType,
                $entityData
            );
        }

        return $entityData;
    }

    /**
     * Add store ID scope to attribute select so that only data for that scope is loaded
     *
     * @param Select $select
     * @param \Magento\Framework\Model\Entity\ScopeInterface[] $context
     * @param EntityMetadataInterface $metadata
     * @return $this
     */
    protected function addScopeToSelect(Select $select, array $context, EntityMetadataInterface $metadata)
    {
        foreach ($context as $scope) {
            //TODO: if (in table exists context field)
            $select->where(
                $metadata->getEntityConnection()->quoteIdentifier($scope->getIdentifier()) . ' IN (?)',
                $this->getContextVariables($scope)
            )->order('t.' . $scope->getIdentifier() . ' DESC');
        }
        return $this;
    }

    /**
     * Group by values so that data returned by select is unique, avoiding common data being returned by query
     *
     * @param Select $select
     * @return $this
     */
    protected function addGroupForAllScopeData(Select $select)
    {
        $select->group('value')
            ->group('attribute_id');
        return $this;
    }

    /**
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     * @param \Magento\Framework\DB\Sql\UnionExpression $unionSelect
     * @param bool $loadAllScopes
     * @param string $entityType
     * @param array $entityData
     * @return array
     */
    protected function populateData(
        \Magento\Framework\DB\Adapter\AdapterInterface $connection,
        \Magento\Framework\DB\Sql\UnionExpression $unionSelect,
        $attributesMap,
        bool $loadAllScopes,
        string $entityType,
        array $entityData
    ) {
        foreach ($connection->fetchAll($unionSelect) as $attributeValue) {
            if (isset($attributesMap[$attributeValue['attribute_id']])) {
                if ($loadAllScopes) {
                    $entityData[$attributeValue['attribute_id']][$attributeValue['store_id']] = $attributeValue['value'];
                } else {
                    $entityData[$attributesMap[$attributeValue['attribute_id']]] = $attributeValue['value'];
                }
            } else {
                $this->logger->warning(
                    "Attempt to load value of nonexistent EAV attribute '{$attributeValue['attribute_id']}'
                        for entity type '$entityType'."
                );
            }
        }
        return $entityData;
    }
}
