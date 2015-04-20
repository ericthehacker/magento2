<?php

namespace Magento\Config\Model\Search;
use Magento\Search\Model\QueryFactory;

class Config extends \Magento\Framework\Object
{
    /** @var \Magento\Backend\Helper\Data */
    protected $_adminhtmlData;
    /** @var \Magento\Framework\Stdlib\String  */
    protected $_string;
    /** @var array */
    protected $_configStructureData;

    /**
     * @param \Magento\Backend\Helper\Data $adminhtmlData
     * @param \Magento\Config\Model\Config\Structure\Data $configStructureData
     */
    public function __construct(
        \Magento\Backend\Helper\Data $adminhtmlData,
        \Magento\Config\Model\Config\Structure\Data $configStructureData
    ) {
        $this->_adminhtmlData = $adminhtmlData;
        $this->_configStructureData = $configStructureData->get();
    }

    /**
     * Load search results
     *
     * @return $this
     */
    public function load()
    {
        $result = [];
        if (!$this->hasStart() || !$this->hasLimit() || !$this->hasQuery()) {
            $this->setResults($result);
            return $this;
        }

        $tabs = $this->_configStructureData['tabs'];

        $fields = [];
        foreach($this->_configStructureData['sections'] as $section) {
            foreach($section['children'] as $group) {
                if(!isset($group['children'])) {
                    continue;
                }
                foreach($group['children'] as $field) {
                    //@todo: check ACL for individual field
                    $field['tab'] = $tabs[$section['tab']]['label'];
                    $field['section'] = $section['label'];
                    $field['group'] = $group['label'];
                    $fields[] = $field;
                }
            }
        }

        foreach($fields as $field) {
            if(count($result) >= $this->getLimit()) {
                break;
            }

            if(!isset($field['label'])) {
                continue;
            }

            $label = strtolower($field['label']);
            $query = strtolower($this->getQuery());

            if(strpos($label, $query) !== false) {
                $result[] = [
                    'id' => 'store-config-search',
                    'type' => __('Store Config Field'),
                    'name' => __($field['label']),
                    'description' => sprintf(
                        '%s -> %s -> %s',
                        __($field['tab']),
                        __($field['section']),
                        __($field['group'])
                    ),
                    'url' => $this->_adminhtmlData->getUrl(
                        'admin/system_config/edit',
                        array(
                            'section' => $field['section']
                        )
                    ),
                ];
            }
        }

        $this->setResults($result);

        return $this;
    }
}