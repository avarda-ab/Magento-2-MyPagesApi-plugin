<?php
namespace Avarda\CustomerInvoices\Plugin;

use Magento\Framework\View\Layout;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Navigation
{
    protected ScopeConfigInterface $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function beforeGetOutput(Layout $layout)
    {
        $checkoutActive = $this->scopeConfig->getValue('avarda/customer_invoices/checkout_active', ScopeInterface::SCOPE_STORE);
        $paymentsActive = $this->scopeConfig->getValue('avarda/customer_invoices/payments_active', ScopeInterface::SCOPE_STORE);

        if (!$checkoutActive && !$paymentsActive) {
            $layout->unsetElement('customer-account-navigation-my-invoices');
        }
    }
}
