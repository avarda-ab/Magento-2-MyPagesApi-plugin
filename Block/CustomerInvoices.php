<?php
/**
 * @copyright   Copyright © Avarda. All rights reserved.
 * @package     Avarda_CustomerInvoices
 */

namespace Avarda\CustomerInvoices\Block;

use Avarda\CustomerInvoices\Helper\ConfigHelper;
use Avarda\CustomerInvoices\Model\MyInvoices;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Directory\Model\Currency;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Gateway\Http\ClientException;

class CustomerInvoices extends Template
{
    protected MyInvoices $myInvoices;
    protected ConfigHelper $configHelper;
    protected Currency $price;

    public function __construct(
        Context $context,
        MyInvoices $myInvoices,
        Currency $price,
        ConfigHelper $configHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->myInvoices = $myInvoices;
        $this->configHelper = $configHelper;
        $this->price = $price;
    }

    /**
     * @return array
     * @throws ClientException
     * @throws GuzzleException
     */
    public function getCustomerInvoices()
    {
        return $this->myInvoices->getCustomerInvoices();
    }

    /**
     * @return array|string
     * @throws GuzzleException
     * @throws ClientException
     */
    public function getCustomerAccounts()
    {
        return $this->myInvoices->getCustomerAccounts();
    }

    /**
     * @return array|string
     * @throws ClientException
     * @throws GuzzleException
     */
    public function getCustomerAccount()
    {
        $id = $this->getRequest()->getParam('id');
        return $this->myInvoices->getCustomerAccounts($id);
    }

    /**
     * @return array
     * @throws ClientException
     * @throws GuzzleException
     */
    public function getCustomerInvoice()
    {
        $id = $this->getRequest()->getParam('id');
        return $this->myInvoices->getCustomerInvoices($id);
    }

    /**
     * @return array|string
     * @throws ClientException
     * @throws GuzzleException
     */
    public function getPayToken()
    {
        $id = $this->getRequest()->getParam('id');
        return $this->myInvoices->getPayToken($id);
    }

    /**
     * @param $invoice
     * @return bool
     */
    public function needsToPay($invoice)
    {
        return in_array($invoice['state'], ['Unpaid', 'Overdue']);
    }

    /**
     * @param float $price
     * @return string
     */
    public function getFormattedPrice($price)
    {
        return $this->price->format($price);
    }

    /**
     * @return string
     */
    public function getCheckOutClientScriptPath()
    {
        return $this->configHelper->getCheckoutJsUrl();
    }

    public function getStateClass($state)
    {
        switch ($state) {
            case 'Transferred':
                return 'transferred';
            case 'Paid':
                return 'paid';
            case 'Overdue':
                return 'overdue';
            case 'Overpaid':
                return 'overpaid';
            case 'Processing':
                return 'processing';
            case 'Scheduled':
                return 'scheduled';
            case 'Unpaid':
                return 'unpaid';
            case 'Notproduced':
                return 'notproduced';
            default:
                return 'unknown';
        }
    }

    /**
     * @return string
     */
    public function getStyles(): string
    {
        return $this->configHelper->getStyles();
    }
}
