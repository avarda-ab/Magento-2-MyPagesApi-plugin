<?php
/**
 * @copyright   Copyright © Avarda. All rights reserved.
 * @package     Avarda_CustomerInvoices
 */

namespace Avarda\CustomerInvoices\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order as ResourceOrder;

class DashboardCollection extends AbstractCollection
{
    private $filtersInitialize = false;

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            Order::class,
            ResourceOrder::class
        );
    }

    /**
     * Add special stuff here, this also affects getSize and load
     * @return void
     */
    protected function _renderFiltersBefore()
    {
        if (!$this->filtersInitialize) {
            $this->getSelect();
            $this->join(['s' => 'sales_order'], 'main_table.order_id = s.entity_id AND s.is_pharmaceutical = 1');
            $this->addFieldToFilter('main_table.started_at', ['notnull' => '']);
            $this->addFieldToFilter('main_table.status',DevocaHelper::STATUS_COMPLETED);
            $this->setOrder('main_table.started_at')
                ->setOrder('s.created_at');

            $this->filtersInitialize = true;
        }
        parent::_renderFiltersBefore();
    }

    public function initializeFilters()
    {
        $this->_renderFiltersBefore();
    }
}
