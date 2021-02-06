<?php

namespace NewRelicFfi;

use Exception;
use FFI;

class Segment
{
    /** @internal */
    public Transaction $txn;

    /**
     * C-API `newrelic_segment_t*`
     * @internal
     */
    public ?FFI\CData $seg;

    public function __construct(Transaction $txn, string $name, string $category)
    {
        $this->txn = $txn;
        if ($this->txn->txn === null) {
            throw new Exception('transaction already ended');
        }
        $this->seg = $this->txn->ffi->newrelic_start_segment($this->txn->txn, $name, $category);
        if ($this->seg === null) {
            throw new Exception('newrelic_start_segment() failed');
        }
    }

    public function createDistributedTracePayload(): string
    {
        if ($this->seg === null) {
            throw new Exception('segment already ended');
        }
        $payload = $this->txn->ffi->newrelic_create_distributed_trace_payload(
            $this->txn->txn,
            $this->seg
        );
        if ($payload === null) {
            throw new Exception('newrelic_create_distributed_trace_payload() failed');
        }
        $res = FFI::string($payload);
        FFI::free($payload);
        return $res;
    }

    public function acceptDistributedTracePayload(string $payload, string $transportType): void
    {
        if ($this->seg === null) {
            throw new Exception('segment already ended');
        }
        if (!$this->txn->ffi->newrelic_accept_distributed_trace_payload(
            $this->txn->txn,
            $payload,
            $transportType
        )) {
            throw new Exception('newrelic_accept_distributed_trace_payload() failed');
        }
    }

    public function end(): void
    {
        if ($this->seg === null) {
            throw new Exception('segment already ended');
        }
        if (!$this->txn->ffi->newrelic_end_segment($this->txn->txn, FFI::addr($this->seg))) {
            throw new Exception('newrelic_end_segment() failed');
        }
        $this->seg = null;
    }

    public function __destruct()
    {
        if ($this->seg === null) {
            return;
        }
        if (!$this->txn->ffi->newrelic_end_segment($this->txn->txn, FFI::addr($this->seg))) {
            // TODO: signal failure without throwing an exception
            // throw new Exception('newrelic_end_segment() failed');
        }
    }
}
