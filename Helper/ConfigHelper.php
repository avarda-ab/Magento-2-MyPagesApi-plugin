<?php

namespace Avarda\CustomerInvoices\Helper;
use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\FlagManager;
use Magento\Framework\Module\Manager;
use Magento\Store\Model\ScopeInterface;

class ConfigHelper
{
    const TEST_URL = 'https://pay-frame.stage.avarda.com/cdn/pay-frame.js';
    const PROD_URL = 'https://pay-frame.avarda.com/cdn/pay-frame.js';
    const MODE_CHECKOUT = 'checkout';
    const MODE_PAYMENTS = 'payments';

    const CHECKOUT_ACTIVE = 'avarda/customer_invoices/checkout_active';
    const PAYMENTS_ACTIVE = 'avarda/customer_invoices/payments_active';

    protected string $parentModule = '';

    protected ScopeConfigInterface $config;

    /**
     * @throws Exception
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Manager $moduleManager
    ) {
        $this->config = $scopeConfig;

        if ($moduleManager->isEnabled('Avarda_Checkout3')) {
            $this->parentModule = self::MODE_CHECKOUT;
        } elseif ($moduleManager->isEnabled('Avarda_Payments')) {
            $this->parentModule = self::MODE_PAYMENTS;
        } else {
            throw new Exception('You must have either avarda/checkout3 or avarda/payments module installed and enabled');
        }
    }

    /**
     * @return string|false
     */
    public function getMode()
    {
        if ($this->parentModule == self::MODE_CHECKOUT) {
            return self::MODE_CHECKOUT;
        } elseif ($this->parentModule == self::MODE_PAYMENTS) {
            return self::MODE_PAYMENTS;
        } else {
            return false;
        }
    }

    public function getActiveMode(): ?string
    {
        if ($this->isActive()) {
            if ($this->config->isSetFlag(self::CHECKOUT_ACTIVE)) {
                return self::MODE_CHECKOUT;
            } elseif ($this->config->isSetFlag(self::PAYMENTS_ACTIVE)) {
                return self::MODE_PAYMENTS;
            }
        }

        return null;
    }

    /**
     * Get My Pages configuration status
     *
     * @return bool
     */
    public function isActive()
    {
        if ($this->getMode()) {
            return $this->config->isSetFlag(self::CHECKOUT_ACTIVE) || $this->config->isSetFlag(self::PAYMENTS_ACTIVE);
        } else {
            return false;
        }
    }

    /**
     * @return bool|null
     */
    public function getTestMode()
    {
        if ($this->getActiveMode() == self::MODE_CHECKOUT) {
            return (bool) $this->config->getValue('payment/avarda_checkout3_checkout/test_mode');
        } elseif ($this->getActiveMode() == self::MODE_PAYMENTS) {
            return (bool) $this->config->getValue('avarda_payments/api/test_mode');
        } else {
            return false;
        }
    }

    public function getAvardaSiteKey()
    {
        if ($this->getActiveMode() == self::MODE_CHECKOUT) {
            return $this->config->getValue('avarda/customer_invoices/checkout_site_key') ?? '';
        } elseif ($this->getActiveMode() == self::MODE_PAYMENTS) {
            return $this->config->getValue('avarda/customer_invoices/payments_site_key') ?? '';
        } else {
            return '';
        }
    }
}
