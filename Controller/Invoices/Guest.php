<?php

namespace Avarda\CustomerInvoices\Controller\Invoices;

use Avarda\CustomerInvoices\Helper\ConfigHelper;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\View\Result\PageFactory;

class Guest implements ActionInterface
{
    protected PageFactory $resultPageFactory;
    protected RedirectFactory $redirectFactory;
    protected ConfigHelper $configHelper;

    public function __construct(
        PageFactory $resultPageFactory,
        RedirectFactory $redirectFactory,
        ConfigHelper $configHelper
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->redirectFactory = $redirectFactory;
        $this->configHelper = $configHelper;
    }

    public function execute()
    {
        if (!$this->configHelper->isActive()) {
            $redirect = $this->redirectFactory->create();
            $redirect->setPath('/');

            return $redirect;
        } else {
            $resultPage = $this->resultPageFactory->create();
            $resultPage->getConfig()->getTitle()->set(__('My Invoices'));

            return $resultPage;
        }
    }
}
