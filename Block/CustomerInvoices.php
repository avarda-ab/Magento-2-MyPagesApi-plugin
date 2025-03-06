<?php
/**
 * @copyright   Copyright © Avarda. All rights reserved.
 * @package     Avarda_CustomerInvoices
 */

namespace Avarda\CustomerInvoices\Block;

use Avarda\CustomerInvoices\Helper\ConfigHelper;
use Magento\Directory\Model\Currency;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class CustomerInvoices extends Template
{
    protected ScopeConfigInterface $scopeConfig;
    protected ResolverInterface $locale;
    protected ConfigHelper $configHelper;
    protected Currency $price;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ResolverInterface $locale,
        ConfigHelper $configHelper,
        Context $context,
        Currency $price,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->scopeConfig = $scopeConfig;
        $this->locale = $locale;
        $this->configHelper = $configHelper;
        $this->price = $price;
    }

    public function getAvardaSiteKey(): string
    {
        return $this->configHelper->getAvardaSiteKey();
    }

    public function getPayFrameUrl(): string
    {
        return $this->configHelper->getTestMode() ? 'https://pay-frame.stage.avarda.com/cdn/pay-frame.js' : 'https://pay-frame.avarda.com/cdn/pay-frame.js';
    }

    public function getLocale(): string
    {
        $locale = $this->locale->getLocale();

        return explode('_', $locale)[0];
    }
}
