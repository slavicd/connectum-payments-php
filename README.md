# Connectum Payments from Slavic D.

PHP wrapper for Connectum payment gateway

## Usage

````php
<?php
$service = new \Entropi\Connectum\Service($user, $pass, $cert, $cert_pass);
echo $service->ping()->message;

````