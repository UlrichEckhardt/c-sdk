<?php

namespace NewRelicFfi;

use Exception;
use FFI;

/**
 * wrapper class to access the New Relic API using the FFI extension of PHP
 */
class Api
{
    private const C_DECLARATION = <<<'EOT'
typedef struct _newrelic_app_config_t newrelic_app_config_t;
typedef struct _newrelic_app_t newrelic_app_t;
typedef struct _newrelic_txn_t newrelic_txn_t;
typedef struct _newrelic_segment_t newrelic_segment_t;
typedef enum _newrelic_loglevel_t {
    NEWRELIC_LOG_ERROR,
    NEWRELIC_LOG_WARNING,
    NEWRELIC_LOG_INFO,
    NEWRELIC_LOG_DEBUG,
} newrelic_loglevel_t;
newrelic_app_config_t* newrelic_create_app_config(const char* app_name, const char* license_key);
bool newrelic_destroy_app_config(newrelic_app_config_t** config);
bool newrelic_configure_log(const char* filename, newrelic_loglevel_t level);
bool newrelic_init(const char* daemon_socket, int time_limit_ms);
newrelic_app_t* newrelic_create_app(const newrelic_app_config_t* config, unsigned short timeout_ms);
bool newrelic_destroy_app(newrelic_app_t** app);
newrelic_txn_t* newrelic_start_web_transaction(newrelic_app_t* app, const char* name);
newrelic_txn_t* newrelic_start_non_web_transaction(newrelic_app_t* app, const char* name);
bool newrelic_end_transaction(newrelic_txn_t** transaction_ptr);
newrelic_segment_t* newrelic_start_segment(newrelic_txn_t* transaction, const char* name, const char* category);
bool newrelic_end_segment(newrelic_txn_t* transaction, newrelic_segment_t** segment_ptr);
const char* newrelic_version(void);
EOT;

    private FFI $ffi;

    public function __construct(string $libraryPath)
    {
        if (!extension_loaded('FFI')) {
            throw new Exception('FFI extension not loaded');
        }
        $this->ffi = FFI::cdef(self::C_DECLARATION, $libraryPath);
    }

    public function getVersion(): string
    {
        $version = $this->ffi->newrelic_version();
        return $version;
    }
}
