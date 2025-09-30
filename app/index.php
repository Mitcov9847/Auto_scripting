<?php

include_once __DIR__ . "/lib/currency.php";
include_once __DIR__ . "/lib/convertor.php";

$api_key = getenv("API_KEY");

$response = [
    "error" => "",
    "data" => []
];

$key = filter_input(INPUT_POST, "key");

if(empty($key)) {
    $response['error'] = "API key is missing";
    echo json_encode($response);
    exit();
}

if ($key !== $api_key) {
    $response['error'] = "Invalid API key";
    echo json_encode($response);
    exit();
}

if(filter_input(INPUT_GET, "currencies") !== null) {
    $response['data'] = array_map(
        fn($c) => $c->name, 
        Currency::cases()
    );
    echo json_encode($response);
    exit();
}

$from = filter_input(
    INPUT_GET, 
    "from", 
    FILTER_VALIDATE_REGEXP, 
    ["options" => ["regexp" => "/^[A-Z]{3}$/"]]
);
$to = filter_input(
    INPUT_GET, 
    "to", 
    FILTER_VALIDATE_REGEXP, 
    ["options" => ["regexp" => "/^[A-Z]{3}$/"]]
);
$date = filter_input(
    INPUT_GET, 
    "date", 
    FILTER_VALIDATE_REGEXP, 
    ["options" => ["regexp" => "/^\d{4}-\d{2}-\d{2}$/"]]
) ?? date("Y-m-d");

$from = strtoupper("{$from}");
$to = strtoupper("{$to}");


if(! IsKnownCurrency($from)) {
    $response['error'] = "The currency {$from} is unknown";
    echo json_encode($response);
    exit(); 
}

if(! IsKnownCurrency($to)) {
    $response['error'] = "The currency {$to} is unknown";
    echo json_encode($response);
    exit(); 
}

$data = file_get_contents(__DIR__ . "/data.json");
$exchangeRates = json_decode($data);
$convertor = new Convertor($exchangeRates);

try {
    $rate = $convertor->exchange(
        Currency::from($from), 
        Currency::from($to), 
        DateTime::createFromFormat("Y-m-d", $date)
    );
} catch(Exception $e) {
    $response['error'] = $e->getMessage();
    echo json_encode($response);
    exit(); 
}

$response['data'] = [
    "from" => $from,
    "to" => $to,
    "rate" => $rate,
    "date" => $date
];

echo json_encode($response);