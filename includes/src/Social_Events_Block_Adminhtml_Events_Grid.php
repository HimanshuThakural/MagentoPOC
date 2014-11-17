<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Social
 * @package     Social_Events
 * @copyright   Copyright (c) 2014 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Social_Events_Block_Adminhtml_Events_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Init Grid default properties
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('events_list_grid');
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    /**
     * Prepare collection for Grid
     *
     * @return Social_Events_Block_Adminhtml_Grid
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('social_events/events')->getResourceCollection();
		//echo "<pre>";var_dump($collection);echo "</pre>";

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Prepare Grid columns
     *
     * @return Mage_Adminhtml_Block_Catalog_Search_Grid
     */
    protected function _prepareColumns()
    {
        $this->addColumn('events_id', array(
            'header'    => Mage::helper('social_events')->__('ID #'),
            'width'     => '50px',
            'index'     => 'events_id',
        ));

        $this->addColumn('title', array(
            'header'    => Mage::helper('social_events')->__('Title'),
            'index'     => 'title',
        ));

        $this->addColumn('category', array(
            'header'    => Mage::helper('social_events')->__('Category'),
            'index'     => 'category',
        ));

		/*$this->addColumn('details', array(
            'header'    => Mage::helper('social_events')->__('Details'),
            'index'     => 'details',
        ));*/
		$this->addColumn('entry_fee', array(
            'header'    => Mage::helper('social_events')->__('Entry Fee'),
            'index'     => 'entry_fee',
        ));

        $this->addColumn('venue', array(
            'header'    => Mage::helper('social_events')->__('Venue'),
            'index'     => 'venue',
        ));

        $this->addColumn('published_at', array(
            'header'   => Mage::helper('social_events')->__('Published On'),
            'sortable' => true,
            'width'    => '170px',
            'index'    => 'published_at',
            'type'     => 'date',
        ));

        $this->addColumn('end_date', array(
            'header'   => Mage::helper('social_events')->__('End Date'),
            'sortable' => true,
            'width'    => '170px',
            'index'    => 'end_date',
            'type'     => 'date',
        ));

        $this->addColumn('created_at', array(
            'header'   => Mage::helper('social_events')->__('Created'),
            'sortable' => true,
            'width'    => '170px',
            'index'    => 'created_at',
            'type'     => 'datetime',
        ));

        $this->addColumn('action',
            array(
                'header'    => Mage::helper('social_events')->__('Action'),
                'width'     => '100px',
                'type'      => 'action',
                'getter'    => 'getId',
                'actions'   => array(array(
                    'caption' => Mage::helper('social_events')->__('Edit'),
                    'url'     => array('base' => '*/*/edit'),
                    'field'   => 'id'
                )),
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'news',
        ));

        return parent::_prepareColumns();
    }

    /**
     * Return row URL for js event handlers
     *
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }

    /**
     * Grid url getter
     *
     * @return string current grid url
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }
}
