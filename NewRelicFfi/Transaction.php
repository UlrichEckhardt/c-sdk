<?php

namespace NewRelicFfi;

use Exception;
use FFI;

/**
 * TODO:
 *  - newrelic_accept_distributed_trace_payload_httpsafe
 *  - newrelic_set_transaction_timing
 *  - newrelic_add_attribute_long (see below)
 *  - newrelic_start_segment
 *  - newrelic_start_datastore_segment
 *  - newrelic_start_external_segment
 *  - newrelic_record_custom_event
 *  - newrelic_record_custom_metric
 *  - newrelic_create_distributed_trace_payload
 *  - newrelic_accept_distributed_trace_payload
 *  - newrelic_create_distributed_trace_payload_httpsafe
 *  - newrelic_accept_distributed_trace_payload_httpsafe
 */
class Transaction
{
    /** @internal */
    public FFI $ffi;

    /**
     * C-API `newrelic_txn_t*`
     */
    private FFI\CData $txn;

    protected function __construct(App $app, FFI\CData $txn)
    {
        $this->ffi = $app->ffi;
        $this->txn = $txn;
    }

    public function setName(string $name): void
    {
        if (!$this->ffi->newrelic_set_transaction_name($this->txn, $name)) {
            throw new Exception('newrelic_set_transaction_name() failed');
        }
    }

    public function addAttribute(string $key, $value): void
    {
        if ($this->txn === null) {
            throw new Exception('transaction already ended');
        }

        if (is_integer($value)) {
            // TODO: When should we use newrelic_add_attribute_long() instead?
            if (!$this->ffi->newrelic_add_attribute_int($this->txn, $key, $value)) {
                throw new Exception('newrelic_add_attribute_int() failed');
            }
        } elseif (is_float($value)) {
            if (!$this->ffi->newrelic_add_attribute_double($this->txn, $key, $value)) {
                throw new Exception('newrelic_add_attribute_double() failed');
            }
        } elseif (is_string($value)) {
            if (!$this->ffi->newrelic_add_attribute_string($this->txn, $key, $value)) {
                throw new Exception('newrelic_add_attribute_string() failed');
            }
        } else {
            throw new Exception('unsupported value type for attribute');
        }
    }

    public function noticeError(int $priority, string $errMsg, string $errClass): void
    {
        // Note: newrelic_notice_error() only logs errors but returns `void`
        $this->ffi->newrelic_notice_error($this->txn, $priority, $errMsg, $errClass);
    }

    public function ignore(): void
    {
        if (!$this->ffi->newrelic_ignore_transaction($this->txn)) {
            throw new Exception('newrelic_ignore_transaction() failed');
        }
    }

    public function end(): void
    {
        if ($this->txn === null) {
            throw new Exception('transaction already ended');
        }
        if (!$this->ffi->newrelic_end_transaction(FFI::addr($this->txn))) {
            throw new Exception('newrelic_end_transaction() failed');
        }
    }

    public function __destruct()
    {
        if ($this->txn === null) {
            return;
        }
        if (!$this->ffi->newrelic_end_transaction(FFI::addr($this->txn))) {
            // TODO: signal failure without throwing an exception
            // throw new Exception('newrelic_end_transaction() failed');
        }
    }
}
