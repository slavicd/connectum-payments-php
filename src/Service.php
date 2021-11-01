<?php


namespace Entropi\Connectum;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ServerException;

class Service
{
    const SANDBOX_URL = 'https://api.sandbox.connectum.eu';
    const PRODUCTION_URL = 'https://api.connectum.eu';

    private $username;
    private $password;
    private $sslKeyPath;
    private $sslKeyPassword;

    /**
     * @var Client
     */
    private $httpClient;

    /**
     * Service constructor.
     * @param string $username
     * @param string $password
     * @param string $sslKeyPath
     * @param string $sslKeyPassword
     * @param string $apiUrl can be a URL or "sandbox"/"production" for Connectum default URLs
     */
    public function __construct(
        $username,
        $password,
        $sslKeyPath,
        $sslKeyPassword,
        $apiUrl=null
    ) {
        $this->username = $username;
        $this->password = $password;
        $this->sslKeyPath = $sslKeyPath;
        $this->sslKeyPassword = $sslKeyPassword;

        if (!is_null($apiUrl)) {
            $baseUri = $apiUrl;
        } else {
            $baseUri = self::SANDBOX_URL;
        }

        if ($baseUri==='production') {
            $baseUri = self::PRODUCTION_URL;
        }

        if ($baseUri=='sandbox') {
            $baseUri=self::SANDBOX_URL;
        }

        $this->httpClient = new Client([
            'base_uri' => $baseUri,
            'allow_redirects' => false,
            'cert' => [$this->sslKeyPath, $this->sslKeyPassword],
            //'debug' => true,
            'auth' => [$this->username, $this->password],
        ]);
    }

    public function ping()
    {
        $resp = $this->httpClient->get('ping');

        if ($resp->getStatusCode()!=200) {
            throw new ServerException('Bad response for ping');
        }
        $resp  = json_decode($resp->getBody());

        return $resp;
    }

    /**
     * Creates an order and returns the payment page URL
     * along with order data
     */
    public function createOrder($data)
    {
        $httpResp = $this->httpClient->post('orders/create', [
            'json' => $data,
        ]);

        if ($httpResp->getStatusCode()!=201) {
            throw new ServerException('Order not created.');
        }

        $resp = json_decode($httpResp->getBody());
        $resp->redirect = $httpResp->getHeader('location')[0];

        return $resp;
    }

    public function authorize($data)
    {
        $httpResp = $this->httpClient->post('orders/authorize', [
            'json' => $data,
        ]);

        $resp = json_decode($httpResp->getBody());
        return $resp;
    }

    public function charge($orderId, $amount)
    {
        $httpResp = $this->httpClient->put('orders/' . $orderId . '/charge', [
            'json' => [
                'amount' => $amount,
            ]
        ]);

        return json_decode($httpResp->getBody());
    }

    /**
     * @param \stdClass $order
     * @return \stdClass
     */
    public function cancel($order)
    {
        $httpResp = $this->httpClient->put('orders/' . $order->id . '/cancel', [
            'json' => [
                'amount' => $order->amount,
            ],
        ]);

        return json_decode($httpResp->getBody());
    }

    /**
     * @param string $id
     * @return \stdClass
     */
    public function getOrder($id)
    {
        $httpResp = $this->httpClient->get('orders/' . $id);
        return json_decode($httpResp->getBody());
    }
}