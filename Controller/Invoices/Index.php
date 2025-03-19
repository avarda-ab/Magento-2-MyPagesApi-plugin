<?php
/**
 * @copyright   Copyright © Avarda. All rights reserved.
 * @package     Avarda_CustomerInvoices
 */

namespace Avarda\CustomerInvoices\Controller\Invoices;

use Avarda\CustomerInvoices\Helper\ConfigHelper;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Customer\Model\Session;

class Index implements ActionInterface
{
    protected Session $session;
    protected PageFactory $resultPageFactory;
    protected RedirectFactory $redirectFactory;
    protected ConfigHelper $configHelper;

    public function __construct(
        Session $customerSession,
        PageFactory $resultPageFactory,
        RedirectFactory $redirectFactory,
        ConfigHelper $configHelper
    ) {
        $this->session = $customerSession;
        $this->resultPageFactory = $resultPageFactory;
        $this->redirectFactory = $redirectFactory;
        $this->configHelper = $configHelper;
    }

    public function execute()
    {
        if (!$this->session->isLoggedIn()) {
            $redirect = $this->redirectFactory->create();
            $redirect->setPath('customer/account/login');

            return $redirect;
        } elseif (!$this->configHelper->isActive()) {
            $redirect = $this->redirectFactory->create();
            $redirect->setPath('customer/account');

            return $redirect;
        } else {
            $resultPage = $this->resultPageFactory->create();
            $resultPage->getConfig()->getTitle()->set(__('My Invoices'));

            return $resultPage;
        }
    }
}
