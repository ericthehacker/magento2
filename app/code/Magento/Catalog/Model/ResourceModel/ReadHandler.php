<?php
/**
 *
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Catalog\Model\ResourceModel;

use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\EntityManager\Operation\ExtensionInterface;
use Magento\Framework\EntityManager\TypeResolver;
use Magento\Catalog\Api\Data\CategoryExtensionFactory;

/**
 * Class ReadHandler
 */
class ReadHandler implements ExtensionInterface
{
    /**
     * @var TypeResolver
     */
    private $typeResolver;

    /**
     * @var MetadataPool
     */
    protected $metadataPool;

    /**
     * @var \Magento\Eav\Model\ResourceModel\ReadHandler
     */
    protected $readHandler;

    /**
     * @var CategoryExtensionFactory
     */
    protected $extensionFactory;

    /**
     * @param TypeResolver $typeResolver
     * @param MetadataPool $metadataPool
     * @param \Magento\Eav\Model\ResourceModel\ReadHandler $readHandler
     * @param CategoryExtensionFactory $extensionFactory
     */
    public function __construct(
        TypeResolver $typeResolver,
        MetadataPool $metadataPool,
        \Magento\Eav\Model\ResourceModel\ReadHandler $readHandler,
        CategoryExtensionFactory $extensionFactory
    ) {
        $this->typeResolver = $typeResolver;
        $this->metadataPool = $metadataPool;
        $this->readHandler = $readHandler;
        $this->extensionFactory = $extensionFactory;
    }

    /**
     * @param object $entity
     * @param array $arguments
     * @return object
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute($entity, $arguments = [])
    {
        $entityType = $this->typeResolver->resolve($entity);

        $scopeData = $this->readHandler->getAllScopeData(
            $entityType,
            $entity,
            $arguments
        );

        $entityExtension = $entity->getExtensionAttributes();

        if (!$entityExtension) {
            // TODO: Figure out how to get a factory for whatever entity type is being passed in (product or category)
            $entityExtension = $this->extensionFactory->create();
        }

        $entityExtension->setScopeData($scopeData);
        $entity->setExtensionAttributes($entityExtension);

        return $entity;
    }
}
