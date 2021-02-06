<?php

namespace NewRelicFfi;

use Exception;
use FFI;

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
