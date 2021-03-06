<?php

class Clean_SqlReports_Block_Adminhtml_Customreport_View_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    protected $_sqlQueryResults;

    public function __construct()
    {
        parent::__construct();
        $this->setId('reportsGrid');
        $this->setDefaultSort('report_id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->addExportType('*/*/exportCsv', $this->__('CSV'));
    }

    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        return $this;
    }

    /**
     * @return Clean_SqlReports_Model_Report
     */
    protected function _getReport()
    {
        return Mage::registry('current_report');
    }

    /**
     * @author Lee Saferite <lee.saferite@aoe.com>
     * @return Varien_Data_Collection_Db
     */
    protected function _createCollection()
    {
        return $this->_getReport()->getReportCollection();
    }

    /**
     * make an attempt to catch errors loading/preparing grid
     * for instance: if the query contains an `id` column which is non-unique
     * the varien data collection will throw a:
     *  "Item (Varien_Object) with the same id "1" already exist"
     *  exception
     * @see Mage_Adminhtml_Block_Widget_Grid::_prepareCollection
     */
    protected function _prepareCollection()
    {
        if (isset($this->_collection)) {
            return $this->_collection;
        }

        $collection = $this->_createCollection();
        $this->setCollection($collection);

        try {
            parent::_prepareCollection();
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('An error occured rendering the grid: ' . $e->getMessage()));
            Mage::logException($e);
            //abort rendering grid and replace collection with an empty one
            $this->setCollection(new Varien_Data_Collection());
        }
        return $this;
    }

    public function getRowUrl($row)
    {
        return null;
    }

    protected function _prepareColumns()
    {
        try {
            $collection = $this->_createCollection();
            $collection->setPageSize(1);
            $collection->load();
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('An error occured rendering the grid: ' . $e->getMessage()));
            Mage::logException($e);
            $collection = new Varien_Data_Collection();
        }

        /** @var Clean_SqlReports_Model_Report_GridConfig $config */
        $config     = $this->_getReport()->getGridConfig();

        $labels     = $config->getLabels();
        $filterable = $config->getFilterable();
        $clickable  = $config->getClickable();
        $hidden     = $config->getHidden();
        $items      = $collection->getItems();
        if (count($items)) {
            $item = reset($items);
            foreach ($item->getData() as $key => $val) {
                $isFilterable = false;
                if (isset($filterable[$key])) {
                    $isFilterable = $filterable[$key];
                } elseif (in_array($key, $filterable)) {
                    $isFilterable = 'adminhtml/widget_grid_column_filter_text';
                }
                $label = $key;
                if (isset($labels[$key])) {
                    $label = $labels[$key];
                }
                $isClickable = false;
                if (isset($clickable[$key])) {
                    $isClickable = 'cleansql/adminhtml_widget_grid_column_renderer_clickable';
                }
                $isHidden = false;
                if (isset($hidden[$key])) {
                    $isHidden = true;
                }
                $this->addColumn(
                    Mage::getModel('catalog/product')->formatUrlKey($key),
                    array(
                        'header'   => Mage::helper('core')->__($label),
                        'index'    => $key,
                        'filter'   => $isFilterable,
                        'sortable' => true,
                        'renderer' => $isClickable,
                        'column_css_class' => ($isHidden ? 'no-display' : ''),
                        'header_css_class' => ($isHidden ? 'no-display' : ''),
                    )
                );
            }
        }

        return parent::_prepareColumns();
    }
}
