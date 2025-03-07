<?php
namespace Avarda\CustomerInvoices\Plugin;

use Avarda\CustomerInvoices\Helper\ConfigHelper;
use Magento\Framework\View\Layout;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Navigation
{
    protected ConfigHelper $configHelper;

    public function __construct
    (
        ConfigHelper $configHelper
    ) {
        $this->configHelper = $configHelper;
    }

    public function beforeGetOutput(Layout $layout)
    {
        if (!$this->configHelper->isActive()) {
            $layout->unsetElement('customer-account-navigation-my-invoices');
        }
    }
}
