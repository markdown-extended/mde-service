#!/usr/bin/env php
<?php
@ini_set('display_errors','1'); @error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
$args = array_slice($argv, 1);

# first argument can be the domain name
$domain = isset($args[0]) ? $args[0] : 'http://piwi.local/projects/markdown-extended/mde-service/';

$interface = 'www/mde-api.php';
#$debug          = false;
$debug          = true;
$mde_options    = array();
$mde_source     = 'My *test* MDE **content** ... azerty `azerty()` azerty <http://google.com/> azerty.';

$data = array(
    'options'   => json_encode($mde_options),
    'source'    => $mde_source,
    'debug'     => $debug
);
$url = $domain . '/' . $interface;

$curl_handler = curl_init();
curl_setopt($curl_handler, CURLOPT_URL, $url);
curl_setopt($curl_handler, CURLOPT_POST, true);
curl_setopt($curl_handler, CURLOPT_POSTFIELDS, $data);
curl_setopt($curl_handler, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl_handler, CURLINFO_HEADER_OUT, true);
//curl_setopt($curl_handler, CURLOPT_HTTPHEADER, array( 'Accept: application/json' ));
//curl_setopt($curl_handler, CURLOPT_HEADER, true);
//curl_setopt($curl_handler, CURLOPT_VERBOSE, true);

echo ">>>> executing cUrl request to '$url' posting data:".PHP_EOL;
var_export($data);
echo PHP_EOL;

$curl_response = curl_exec($curl_handler);
if (curl_errno($curl_handler)) {
    echo '>>>> !! ERROR - ' . curl_error($curl_handler);
} else {
    $curl_response = json_decode($curl_response, true);
    if (isset($curl_response['dump'])) {
        $dump = unserialize($curl_response['dump']);
        unset($curl_response['dump']);
    }
}
$curl_info = curl_getinfo($curl_handler);
curl_close($curl_handler);

echo ">>>> response array:".PHP_EOL;
var_export($curl_response);
echo PHP_EOL;

if ($debug) {
    echo ">>>> webservice dump:".PHP_EOL;
    var_export($dump);
    echo PHP_EOL;
}

echo ">>>> request/response info:".PHP_EOL;
var_export($curl_info);
echo PHP_EOL;
