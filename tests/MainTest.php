<?php


namespace Entropi\Connectum\Test;

use GuzzleHttp\Exception\ConnectException;
use PHPUnit\Framework\TestCase;
use Entropi\Connectum\Service;

class MainTest extends TestCase
{
    const CARD_NR_SUCCESS = '4111111111111111';

    /**
     * @var Service
     */
    private $service;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $config = require dirname(__FILE__) . '/configs/config.php';
        $this->service = new Service($config['username'], $config['password'], $config['ssl_key_path'], $config['ssl_key_password']);

    }

    public function testPing()
    {

        try {
            $resp = $this->service->ping();
            $this->assertIsObject($resp);
            $this->assertTrue(property_exists($resp, 'message'));
            $this->assertEquals('PONG!', $resp->message);

        } catch (ConnectException $e) {
            $this->fail('exception received: ' . $e->getCode());
        }
    }

    public function testPaymentPageCreation()
    {
        $resp = $this->service->createOrder([
            'amount' => 1.99,
            'currency' => 'USD',
            'client' => [
                'email' => 'tester0391@example.com',
            ],
            'options' => [
                'language' => 'en',
                'return_url' => 'http://gambody.localhost',
            ]
        ]);

        $this->assertIsObject($resp);
        $this->assertTrue(property_exists($resp, 'redirect'));
        $this->assertIsString($resp->redirect);
    }

    public function testAuthorize()
    {
        $resp = $this->service->authorize([
            'amount' => rand(1, 10),
            'currency' => 'USD',
            'pan' => self::CARD_NR_SUCCESS,
            'card' => [
                'cvv' => '123',
                'holder' => 'John Smith',
                'expiration_month' => '06',
                'expiration_year' => '2022',
            ],
            'location' => [
                'ip' => '8.8.8.8',
            ],
            'client' => [
                'email' => 'johny023@example.com',
            ]
        ]);

        $this->assertIsObject($resp);
        return $resp->orders[0];
    }

    /**
     * @depends testAuthorize
     * @param \stdClass $order
     * @return \stdClass
     */
    public function testCharge($order)
    {
        $this->assertEquals('authorized', $order->status);
        $resp = $this->service->charge($order->id, (float) $order->amount);
        $this->assertIsObject($resp);
        $this->assertEquals('charged', $resp->orders[0]->status);

        return $resp->orders[0];
    }

    /**
     * @depends testCharge
     * @param \stdClass $order
     */
    public function testCancelCharged($order)
    {
        $resp = $this->service->cancel($order);
        $this->assertIsObject($resp);
        $this->assertContains($resp->orders[0]->status, ['refunded', 'reversed']);
    }

    /**
     * @param \stdClass $order
     */
    public function testCancelAuthorized()
    {
        $order = $this->testAuthorize();
        $resp = $this->service->cancel($order);
        $this->assertIsObject($resp);
        $this->assertContains($resp->orders[0]->status, ['refunded', 'reversed']);
    }

    /**
     * @depends testAuthorize
     */
    public function testFetchOrder($order)
    {
        $resp = $this->service->getOrder($order->id);
        $this->assertIsObject($resp);
    }
}