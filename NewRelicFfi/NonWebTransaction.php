<?php

namespace NewRelicFfi;

use Exception;

class NonWebTransaction extends Transaction
{
    public function __construct(App $app, string $name)
    {
        $txn = $app->ffi->newrelic_start_non_web_transaction($app->app, $name);
        if ($txn === null) {
            throw new Exception('newrelic_start_non_web_transaction() failed');
        }
        parent::__construct($app, $txn);
    }
}
