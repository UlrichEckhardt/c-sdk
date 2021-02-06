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
typedef enum _newrelic_loglevel_t {
    NEWRELIC_LOG_ERROR,
    NEWRELIC_LOG_WARNING,
    NEWRELIC_LOG_INFO,
    NEWRELIC_LOG_DEBUG,
} newrelic_loglevel_t;
typedef uint64_t newrelic_time_us_t;
typedef enum _newrelic_transaction_tracer_threshold_t {
    NEWRELIC_THRESHOLD_IS_APDEX_FAILING,
    NEWRELIC_THRESHOLD_IS_OVER_DURATION,
} newrelic_transaction_tracer_threshold_t;
typedef enum _newrelic_tt_recordsql_t {
    NEWRELIC_SQL_OFF,
    NEWRELIC_SQL_RAW,
    NEWRELIC_SQL_OBFUSCATED
} newrelic_tt_recordsql_t;

typedef struct _newrelic_transaction_tracer_config_t {
  bool enabled;
  newrelic_transaction_tracer_threshold_t threshold;
  newrelic_time_us_t duration_us;
  newrelic_time_us_t stack_trace_threshold_us;
  struct {
    bool enabled;
    newrelic_tt_recordsql_t record_sql;
    newrelic_time_us_t threshold_us;
  } datastore_reporting;
} newrelic_transaction_tracer_config_t;
typedef struct _newrelic_datastore_segment_config_t {
  bool instance_reporting;
  bool database_name_reporting;
} newrelic_datastore_segment_config_t;
typedef struct _newrelic_distributed_tracing_config_t {
  bool enabled;
} newrelic_distributed_tracing_config_t;
typedef struct _newrelic_span_event_config_t {
  bool enabled;
} newrelic_span_event_config_t;


typedef struct _newrelic_app_config_t {
  char app_name[255];
  char license_key[255];
  char redirect_collector[100];
  char log_filename[512];
  newrelic_loglevel_t log_level;
  newrelic_transaction_tracer_config_t transaction_tracer;
  newrelic_datastore_segment_config_t datastore_tracer;
  newrelic_distributed_tracing_config_t distributed_tracing;
  newrelic_span_event_config_t span_events;
} newrelic_app_config_t;

typedef struct _newrelic_app_t newrelic_app_t;
typedef struct _newrelic_txn_t newrelic_txn_t;
typedef struct _newrelic_segment_t newrelic_segment_t;
bool newrelic_accept_distributed_trace_payload(newrelic_txn_t* transaction, const char* payload, const char* transport_type);
bool newrelic_accept_distributed_trace_payload_httpsafe(newrelic_txn_t* transaction, const char* payload, const char* transport_type);
bool newrelic_add_attribute_double(newrelic_txn_t* transaction, const char* key, const double value);
bool newrelic_add_attribute_int(newrelic_txn_t* transaction, const char* key, const int value);
bool newrelic_add_attribute_string(newrelic_txn_t* transaction, const char* key, const char* value);
newrelic_app_config_t* newrelic_create_app_config(const char* app_name, const char* license_key);
bool newrelic_configure_log(const char* filename, newrelic_loglevel_t level);
newrelic_app_t* newrelic_create_app(const newrelic_app_config_t* config, unsigned short timeout_ms);
char* newrelic_create_distributed_trace_payload(newrelic_txn_t* transaction, newrelic_segment_t* segment);
char* newrelic_create_distributed_trace_payload_httpsafe(newrelic_txn_t* transaction, newrelic_segment_t* segment);
bool newrelic_destroy_app(newrelic_app_t** app);
bool newrelic_destroy_app_config(newrelic_app_config_t** config);
bool newrelic_end_segment(newrelic_txn_t* transaction, newrelic_segment_t** segment_ptr);
bool newrelic_end_transaction(newrelic_txn_t** transaction_ptr);
bool newrelic_init(const char* daemon_socket, int time_limit_ms);
void newrelic_notice_error(newrelic_txn_t* transaction, int priority, const char* errmsg, const char* errclass);
newrelic_txn_t* newrelic_start_non_web_transaction(newrelic_app_t* app, const char* name);
newrelic_segment_t* newrelic_start_segment(newrelic_txn_t* transaction, const char* name, const char* category);
newrelic_txn_t* newrelic_start_web_transaction(newrelic_app_t* app, const char* name);
const char* newrelic_version(void);
EOT;

    /** @internal */
    public FFI $ffi;

    public function __construct(string $libraryPath)
    {
        if (!extension_loaded('FFI')) {
            throw new Exception('FFI extension not loaded');
        }
        if (!file_exists($libraryPath)) {
            throw new Exception('the given library does not exist');
        }
        $this->ffi = FFI::cdef(self::C_DECLARATION, $libraryPath);
    }

    public function getVersion(): string
    {
        $version = $this->ffi->newrelic_version();
        return $version;
    }

    public function configureLog(string $filename, string $level): void
    {
        $nrLevel = $this->ffi->{'NEWRELIC_LOG_' . $level};
        if (!$this->ffi->newrelic_configure_log($filename, $nrLevel)) {
            throw new Exception('newrelic_configure_log() failed');
        }
    }

    // If this fails, there are two reasons:
    // 1. You need to run the daemon using:
    //    newrelic-daemon --loglevel debug --foreground --address /tmp/.newrelic.sock
    // 2. You tried to call this a second time after it was called either explicitly
    //    or implicitly.
    public function init(?string $daemonSocket, int $timeLimitMs): void
    {
        //     if (!newrelic_init(NULL, 0)) {
        //       printf("Error connecting to daemon.\n");
        //       return -1;
        //     }
        if (!$this->ffi->newrelic_init($daemonSocket, $timeLimitMs)) {
            throw new Exception('newrelic_init() failed');
        }
    }

    /**
     * factory function to create a configuration
     */
    public function createConfig(string $appName, string $licenceKey): AppConfig
    {
        return new AppConfig($this, $appName, $licenceKey);
    }
}
