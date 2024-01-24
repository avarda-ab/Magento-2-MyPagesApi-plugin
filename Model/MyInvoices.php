<?php
/**
 * @copyright   Copyright © Avarda. All rights reserved.
 * @package     Avarda_CustomerInvoices
 */

namespace Avarda\CustomerInvoices\Model;

use Avarda\CustomerInvoices\Helper\ConfigHelper;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\Url;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;

class MyInvoices
{
    protected AvardaClient $avardaClient;
    protected ConfigHelper $configHelper;
    protected Session $customerSession;
    protected Url $url;
    protected OrderRepositoryInterface $orderRepository;
    protected SearchCriteriaBuilder $criteriaBuilder;
    protected FilterBuilder $filterBuilder;
    protected SortOrderBuilder $sortOrderBuilder;
    protected Resolver $localeResolver;

    public function __construct(
        AvardaClient $avardaClient,
        ConfigHelper $configHelper,
        Session $customerSession,
        Url $url,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $criteriaBuilder,
        FilterBuilder $filterBuilder,
        SortOrderBuilder $sortOrderBuilder,
        Resolver $localeResolver
    ) {
        $this->avardaClient = $avardaClient;
        $this->configHelper = $configHelper;
        $this->customerSession = $customerSession;
        $this->url = $url;
        $this->orderRepository = $orderRepository;
        $this->criteriaBuilder = $criteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->localeResolver = $localeResolver;
    }

    /**
     * @param string $id
     * @return array
     * @throws GuzzleException|ClientException
     */
    public function getCustomerInvoices(string $id = ''): array
    {
        $customerId = $this->customerSession->getCustomerId();
        $lastPurchaseId = $this->getLastPurchaseId($customerId);
        if (!$lastPurchaseId) {
            return [];
        }

        $url = $this->configHelper->getApiUrl() . 'Invoices';
        if ($id) {
            $url .= '/' . $id;
        }
        $headers = $this->avardaClient->buildHeader(false);
        $headers['CustomerAuthToken'] = $this->getCustomerToken($customerId);
        $headers['CustomerTokenProvider'] = 'Self';

        try {
            $response = $this->avardaClient->get($url, $headers, ['purchaseId' => $lastPurchaseId]);
            if ($response) {
                return json_decode($response, true);
            }
        } catch (Exception $e) {
        }
        return [];
    }

    /**
     * @param string $id
     * @return string|array
     * @throws GuzzleException|ClientException
     */
    public function getCustomerAccounts(string $id = ''): array|string
    {
        $customerId = $this->customerSession->getCustomerId();
        $lastPurchaseId = $this->getLastPurchaseId($customerId, true);
        if (!$lastPurchaseId) {
            $lastPurchaseId = $this->getLastPurchaseId($customerId);
            if (!$lastPurchaseId) {
                return [];
            }
        }

        $url = $this->configHelper->getApiUrl() . 'Accounts';
        if ($id) {
            $url .= '/' . $id;
        }
        $headers = $this->avardaClient->buildHeader(false);
        $headers['CustomerAuthToken'] = $this->getCustomerToken($customerId);
        $headers['CustomerTokenProvider'] = 'Self';
        $headers['CustomerAuthenticationReturnUrl'] = $this->url->getUrl('avarda_customer/invoices');

        try {
            $response = $this->avardaClient->get($url, $headers, ['purchaseId' => $lastPurchaseId]);
            if ($response) {
                $accounts = json_decode($response, true);
                return $accounts['redirectUrl'] ?? $accounts;
            }
        } catch (Exception $e) {
        }
        return [];
    }

    /**
     * @param string $purchaseId
     * @return array|bool
     * @throws ClientException
     * @throws GuzzleException
     */
    public function getPayToken(string $purchaseId)
    {
        $url = $this->configHelper->getApiUrl() . 'Pay';
        $headers = $this->avardaClient->buildHeader();

        $localeCode = $this->localeResolver->getLocale();
        $parts = explode('_', $localeCode);
        $lang = reset($parts);
        $recognizedLocales = [
            "en" => 0,
            "sv" => 1,
            "fi" => 2,
            "no" => 3,
            "et" => 4,
            "dk" => 5,
            "cz" => 6,
            "lt" => 7,
            "sk" => 9,
            "pl" => 10,
        ];

        try {
            $response = $this->avardaClient->post($url, [
                'language' => $recognizedLocales[$lang] ?? 0, // Defaults to english
                'accountId' => $purchaseId,
            ], $headers);
            $purchaseData = json_decode($response->getBody(), true);
            return $purchaseData ?? false;
        } catch (Exception $e) {
        }
        return false;
    }

    /**
     * @param $customerId
     * @return string
     */
    public function getCustomerToken($customerId): string
    {
        return hash('sha256', $customerId);
    }

    /**
     * @param $customerId string|int
     * @param $account bool
     * @return string|bool
     */
    public function getLastPurchaseId($customerId, $account = false)
    {
        $paymentMethods = [];
        if ($this->configHelper->getMode() == ConfigHelper::MODE_CHECKOUT) {
            if ($account) {
                $paymentMethods = ['avarda_checkout3_loan', 'avarda_checkout3_part_payment'];
            } else {
                $paymentMethods = ['avarda_checkout3_invoice', 'avarda_checkout3_direct_invoice'];
            }
        } elseif ($this->configHelper->getMode() == ConfigHelper::MODE_PAYMENTS) {
            if ($account) {
                $paymentMethods = ['avarda_payments_loan', 'avarda_payments_partpayment'];
            } else {
                $paymentMethods = ['avarda_payments_invoice', 'avarda_payments_direct_invoice'];
            }
        }

        if (!$paymentMethods) {
            return '';
        }

        $customerIdFilter = $this->filterBuilder
            ->setField('customer_id')
            ->setValue($customerId)
            ->create();
        $paymentMethodFilter = $this->filterBuilder
            ->setField('extension_attribute_payment_method.method')
            ->setValue($paymentMethods)
            ->setConditionType('in')
            ->create();
        $stateFilter = $this->filterBuilder
            ->setField('state')
            ->setValue([Order::STATE_COMPLETE, Order::STATE_PROCESSING])
            ->setConditionType('in')
            ->create();
        $sortOrder = $this->sortOrderBuilder
            ->setField('created_at')
            ->setDescendingDirection()
            ->create();

        $criteria = $this->criteriaBuilder
            ->addFilters([$customerIdFilter])
            ->addFilters([$paymentMethodFilter])
            ->addFilters([$stateFilter])
            ->addSortOrder($sortOrder)
            ->setPageSize(1)
            ->setCurrentPage(1)
            ->create();

        $orderList = $this->orderRepository->getList($criteria);
        $result = $orderList->getItems();
        if ($orderList->getTotalCount() > 0) {
            /** @var Order $order */
            $order = reset($result);
            $additionalData = $order->getPayment()->getAdditionalInformation();
            return $additionalData['purchase_data']['purchaseId'] ?? false;
        } else {
            return false;
        }
    }
}
