<?php
namespace Config;

use CodeIgniter\Config\BaseConfig;
use Config\Commonutils;
use Easebuzz;

include_once('easebuzz-lib/easebuzz_payment_gateway.php');

$MERCHANT_KEY=getenv('merchantKey');
$SALT=getenv('salt');
$ENV='test';

$easebuzzObj = new Easebuzz($MERCHANT_KEY, $SALT, $ENV);