<?php

#include <stdlib.h>
#include <stdio.h>
#include <unistd.h>
/*
* A standalone example that demonstrates to users how to
* configure logging, configure an app, connect an app,
* start a transaction and a segment, and cleanly destroy
* everything.
*/
#include "libnewrelic.h"
// int main(void) {
//     newrelic_app_t* app;
//     newrelic_txn_t* txn;
//     newrelic_app_config_t* config;
//     newrelic_segment_t* seg;

//     config = newrelic_create_app_config("<YOUR_APP_NAME>", "75a10661bb62a93c1b238faa6acd0f4f193f182f");

//     if (!newrelic_configure_log("./c_sdk.log", NEWRELIC_LOG_INFO)) {
//       printf("Error configuring logging.\n");
//       return -1;
//     }

//     if (!newrelic_init(NULL, 0)) {
//       printf("Error connecting to daemon.\n");
//       return -1;
//     }

//     /* Wait up to 10 seconds for the SDK to connect to the daemon */
//     app = newrelic_create_app(config, 10000);
//     newrelic_destroy_app_config(&config);

//     /* Start a web transaction and a segment */
//     txn = newrelic_start_web_transaction(app, "Transaction name");
//     seg = newrelic_start_segment(txn, "Segment name", "Custom");

//     /* Interesting application code happens here */
//     sleep(2);

//     /* End the segment and web transaction */
//     newrelic_end_segment(txn, &seg);
//     newrelic_end_transaction(&txn);
//     newrelic_destroy_app(&app);

//     return 0;
// }

require_once __DIR__ . '/NewRelicFfi/Api.php';
require_once __DIR__ . '/NewRelicFfi/App.php';
require_once __DIR__ . '/NewRelicFfi/AppConfig.php';
require_once __DIR__ . '/NewRelicFfi/Transaction.php';
require_once __DIR__ . '/NewRelicFfi/NonWebTransaction.php';
require_once __DIR__ . '/NewRelicFfi/WebTransaction.php';
require_once __DIR__ . '/NewRelicFfi/Segment.php';

use NewRelicFfi\Api;

$api = new Api(__DIR__ . '/libnewrelic.so');
echo $api->getVersion() . PHP_EOL;
$api->configureLog('stdout', 'DEBUG');
$api->init(null, 1000);

$config = $api->createConfig("BAPI AY Staging", "");
$config->setDistributedTracingEnabled(true);
$app = $config->createApp(2000);

$txn = $app->startWebTransaction('FFI/Transaction');
$txn->addAttribute('created by', 'FFI-driver');
$txn->addAttribute('version', 0);
$txn->addAttribute('tau', 6.28);
sleep(1);
$seg = $txn->startSegment('Segment name', 'Custom');
sleep(1);
$payload = $seg->createDistributedTracePayload();
echo base64_encode($payload) . PHP_EOL;
$seg->end();
sleep(1);
$txn->end();
die();

$ffi = FFI::cdef(
    "typedef struct _newrelic_app_config_t newrelic_app_config_t;\n" .
    "typedef struct _newrelic_app_t newrelic_app_t;\n" .
    "typedef struct _newrelic_txn_t newrelic_txn_t;\n" .
    "typedef struct _newrelic_segment_t newrelic_segment_t;\n" .
    "typedef enum _newrelic_loglevel_t {\n" .
    "    NEWRELIC_LOG_ERROR,\n" .
    "    NEWRELIC_LOG_WARNING,\n" .
    "    NEWRELIC_LOG_INFO,\n" .
    "    NEWRELIC_LOG_DEBUG,\n" .
    "} newrelic_loglevel_t;\n" .
    "newrelic_app_config_t* newrelic_create_app_config(const char* app_name, const char* license_key);\n" .
    "bool newrelic_destroy_app_config(newrelic_app_config_t** config);\n" .
    "bool newrelic_configure_log(const char* filename, newrelic_loglevel_t level);\n" .
    "bool newrelic_init(const char* daemon_socket, int time_limit_ms);\n" .
    "newrelic_app_t* newrelic_create_app(const newrelic_app_config_t* config, unsigned short timeout_ms);" .
    "bool newrelic_destroy_app(newrelic_app_t** app);\n" .
    "newrelic_txn_t* newrelic_start_web_transaction(newrelic_app_t* app, const char* name);\n" .
    "newrelic_txn_t* newrelic_start_non_web_transaction(newrelic_app_t* app, const char* name);\n" .
    "bool newrelic_end_transaction(newrelic_txn_t** transaction_ptr);\n" .
    "newrelic_segment_t* newrelic_start_segment(newrelic_txn_t* transaction, const char* name, const char* category);\n" .
    "bool newrelic_end_segment(newrelic_txn_t* transaction, newrelic_segment_t** segment_ptr);\n",
    __DIR__ . "/libnewrelic.so"
);
echo "created FFI\n";


//     newrelic_app_config_t* config;
//     config = newrelic_create_app_config("<YOUR_APP_NAME>", "<LICENCE_KEY>");
$config = $ffi->newrelic_create_app_config("BAPI AY Staging", "");
if ($config === null) {
    throw new Exception('newrelic_create_app_config() failed');
}
echo "created app config\n";
var_dump($config);


if (!$ffi->newrelic_configure_log("stdout", $ffi->NEWRELIC_LOG_DEBUG)) {
    throw new Exception('newrelic_configure_log() failed');
}

// If this fails, run the daemon using:
//    newrelic-daemon --loglevel debug --foreground --address /tmp/.newrelic.sock
//
//     if (!newrelic_init(NULL, 0)) {
//       printf("Error connecting to daemon.\n");
//       return -1;
//     }
if (!$ffi->newrelic_init(null, 1000)) {
    throw new Exception('newrelic_init() failed');
}
echo "initialized NR\n";


//     newrelic_app_t* app;
//     app = newrelic_create_app(config, 10000);
$app = $ffi->newrelic_create_app($config, 10000);
if ($app === null) {
    throw new Exception('newrelic_create_app() failed');
}
echo "created app\n";
var_dump($app);


//     newrelic_destroy_app_config(&config);
if (!$ffi->newrelic_destroy_app_config(FFI::addr($config))) {
    throw new Exception('newrelic_destroy_app_config() failed');
}
echo "released app config\n";

if (false) {
    //     newrelic_txn_t* txn;
    //     /* Start a web transaction and a segment */
    //     txn = newrelic_start_web_transaction(app, "Transaction name");
    //     seg = newrelic_start_segment(txn, "Segment name", "Custom");
    $txn = $ffi->newrelic_start_web_transaction($app, "FFI/Transaction");
    if ($txn === null) {
        throw new Exception('newrelic_start_web_transaction() failed');
    }
    echo "started web transaction\n";
} else {
    $txn = $ffi->newrelic_start_non_web_transaction($app, "FFI/Transaction");
    if ($txn === null) {
        throw new Exception('newrelic_start_non_web_transaction() failed');
    }
    echo "started non-web transaction\n";
}

//     seg = newrelic_start_segment(txn, "Segment name", "Custom");
$seg = $ffi->newrelic_start_segment($txn, "Segment name", "Custom");
if ($seg === null) {
    throw new Exception('newrelic_start_segment() failed');
}

// Interesting application code happens here
sleep(3);

//     /* End the segment and web transaction */
if (!$ffi->newrelic_end_segment($txn, FFI::addr($seg))) {
    throw new Exception('newrelic_end_segment() failed');
}


//     newrelic_end_transaction(&txn);
if (!$ffi->newrelic_end_transaction(FFI::addr($txn))) {
    throw new Exception('newrelic_end_transaction() failed');
}
echo "finished transaction\n";

sleep(10);

//     newrelic_destroy_app(&app);
if (!$ffi->newrelic_destroy_app(FFI::addr($app))) {
    throw new Exception('newrelic_end_transaction() failed');
}
