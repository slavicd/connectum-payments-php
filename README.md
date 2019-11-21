# Connectum Payments from Slavic D.

PHP wrapper for Connectum payment gateway

## Usage

Obtain username, password and certificate file from Connectum.

````php
<?php
$service = new \Entropi\Connectum\Service($user, $pass, $certPath, $cert_pass);
echo $service->ping()->message;
$service->authorize([
    'amount' => rand(1, 10),
    'pan' => '4111111111111111',
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
        'email' => 'john.smith@example.com',
    ],
]);
````