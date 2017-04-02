<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eav\Model\ResourceModel;

use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\EntityManager\Operation\AttributeInterface;
use Magento\Framework\Model\Entity\ScopeInterface;
use Magento\Framework\Model\Entity\ScopeResolver;
use Psr\Log\LoggerInterface;

class ReadHandler implements AttributeInterface
{
    /**
     * @var ReadHandler\Attribute
     */
    protected $attribute;

    /**
     * ReadHandler constructor
     *
     * @param ReadHandler\Attribute $attribute
     */
    public function __construct(
        \Magento\Eav\Model\ResourceModel\ReadHandler\Attribute $attribute
    ) {
        $this->attribute = $attribute;
    }

    /**
     * Add attribute data for entity to entity and return it
     *
     * @param string $entityType
     * @param array $entityData
     * @param array $arguments
     * @return array
     * @throws \Exception
     * @throws \Magento\Framework\Exception\ConfigurationMismatchException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute($entityType, $entityData, $arguments = [])
    {
        return $this->attribute->getData($entityType, $entityData, $arguments = []);
    }
}
