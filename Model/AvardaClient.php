<?php
/**
 * @copyright Copyright © Avarda. All rights reserved.
 * @package   Avarda_Payments
 */

namespace Avarda\CustomerInvoices\Model;

use Avarda\CustomerInvoices\Helper\ConfigHelper;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Laminas\Http\Request;
use Magento\Customer\Model\Session;
use Magento\Framework\FlagManager;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Model\Method\Logger;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class AvardaClient
{
    protected ConfigHelper $config;
    protected UrlInterface $url;
    protected FlagManager $flagManager;
    protected Session $customerSession;
    protected ResponseInterface $lastResponse;
    protected Logger $logger;

    public function __construct(
        ConfigHelper $config,
        FlagManager $flagManager,
        UrlInterface $url,
        Logger $logger,
        Session $customerSession,
    ) {
        $this->config = $config;
        $this->flagManager = $flagManager;
        $this->url = $url;
        $this->logger = $logger;
        $this->customerSession = $customerSession;
    }

    /**
     * @param $url string
     * @param $payload array
     * @param $headers array
     * @param $payloadType string
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function post($url, $payload, $headers, $payloadType = 'json'): ResponseInterface
    {
        $client = new Client();
        $response = $client->request(
            Request::METHOD_POST,
            $url,
            [
                $payloadType => $payload,
                'headers' => $headers,
                'http_errors' => false
            ]
        );

        $this->handleErrors($response);

        return $response;
    }

    /**
     * @param string $url
     * @param array $headers
     * @param array $additionalParameters
     * @return string
     * @throws GuzzleException
     */
    public function get($url, $headers, $additionalParameters = []): string
    {
        $client = new Client();
        $response = $client->request(
            Request::METHOD_GET,
            $url,
            [
                'headers' => $headers,
                'query' => $additionalParameters,
                'http_errors' => false
            ]
        );

        $this->handleErrors($response);

        return $response->getBody()->getContents();
    }

    /**
     * @param ResponseInterface $response
     * @return bool|null
     * @throws RuntimeException
     */
    public function handleErrors(ResponseInterface $response): ?bool
    {
        $this->lastResponse = $response;
        switch ($response->getStatusCode()) {
            case 200:
            case 201:
            case 202:
            case 203:
                return true;
            case 400:
            case 401:
            case 422:
            case 403:
            case 404:
            case 405:
                $this->logger->debug([$response->getStatusCode(), $response->getBody()]);
                throw new RuntimeException('Error in request');
            case 500:
                $this->logger->debug([$response->getStatusCode(), $response->getBody()]);
                throw new RuntimeException('Avarda server error');
            default:
                throw new RuntimeException('Unhandled response status code (' . $response->getStatusCode() . ')');
        }
    }

    /**
     * @param bool $json
     * @param bool $token
     * @return array
     * @throws ClientException
     * @throws GuzzleException
     */
    public function buildHeader($json = true, $token = true): array
    {
        $header = [
            'Date' => date('r')
        ];

        if ($json) {
            $header['Accept'] = 'application/json';
            $header['Content-Type'] = 'application/json';
        } else {
            $header['Accept'] = 'text/plain';
        }

        if ($token) {
            $header['Authorization'] = sprintf('Bearer %s', $this->getToken());
        }

        return $header;
    }

    /**
     * @return ResponseInterface
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    /**
     * @return string
     * @throws ClientException
     * @throws GuzzleException
     */
    private function getToken()
    {
        $tokenValid = $this->flagManager->getFlagData(ConfigHelper::KEY_TOKEN_FLAG . '_valid');
        if (!$tokenValid || $tokenValid < time()) {

            $authUrl = $this->config->getTokenUrl();
            $authParam = [
                'clientId'     => $this->config->getClientId(),
                'clientSecret' => $this->config->getClientSecret()
            ];
            $headers = [
                'Accept' => 'text/plain',
                'Content-Type' => 'application/json-patch+json'
            ];
            $response = $this->post($authUrl, $authParam, $headers, 'json');
            $responseArray = json_decode((string)$response->getBody(), true);

            if (!is_array($responseArray)) {
                throw new ClientException(__('Authentication with avarda responded with invalid response'));
            } elseif (isset($responseArray['error_description'])) {
                throw new ClientException(__('Authentication error, check avarda credentials'));
            } else {
                $this->flagManager->saveFlag(ConfigHelper::KEY_TOKEN_FLAG . '_valid', strtotime($responseArray['tokenExpirationUtc']));
                $this->config->saveNewToken($responseArray['token']);
            }
        }

        return $this->config->getToken();
    }
}
