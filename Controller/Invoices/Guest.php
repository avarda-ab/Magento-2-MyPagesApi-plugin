<?php

namespace Avarda\CustomerInvoices\Controller\Invoices;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\View\Result\PageFactory;

class Guest implements ActionInterface
{
    protected PageFactory $resultPageFactory;
    protected RedirectFactory $redirectFactory;

    public function __construct(
        PageFactory $resultPageFactory,
        RedirectFactory $redirectFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->redirectFactory = $redirectFactory;
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('My Invoices'));

        return $resultPage;
    }
}
