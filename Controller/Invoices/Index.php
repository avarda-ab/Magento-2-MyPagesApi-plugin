<?php
/**
 * @copyright   Copyright © Avarda. All rights reserved.
 * @package     Avarda_CustomerInvoices
 */

namespace Avarda\CustomerInvoices\Controller\Invoices;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Customer\Model\Session;

class Index implements ActionInterface
{
    protected Session $session;
    protected PageFactory $resultPageFactory;
    protected RedirectFactory $redirectFactory;

    public function __construct(
        Session $customerSession,
        PageFactory $resultPageFactory,
        RedirectFactory $redirectFactory
    ) {
        $this->session = $customerSession;
        $this->resultPageFactory = $resultPageFactory;
        $this->redirectFactory = $redirectFactory;
    }

    public function execute()
    {
        if (!$this->session->isLoggedIn()) {
            $redirect = $this->redirectFactory->create();
            $redirect->setPath('customer/account/login');

            return $redirect;
        } else {
            $resultPage = $this->resultPageFactory->create();
            $resultPage->getConfig()->getTitle()->set(__('My Invoices'));

            return $resultPage;
        }
    }
}
