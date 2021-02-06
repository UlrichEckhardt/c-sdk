<?php

namespace NewRelicFfi;

use Exception;
use FFI;

class App
{
    /** @internal */
    public FFI $ffi;

    /**
     * C-API `newrelic_app_t*`
     * @internal
     */
    public FFI\CData $app;

    public function __construct(AppConfig $config, int $timeoutMs)
    {
        $this->ffi = $config->ffi;
        $app = $this->ffi->newrelic_create_app($config->config, $timeoutMs);
        if ($app === null) {
            throw new Exception('newrelic_create_app() failed');
        }
        $this->app = $app;
    }

    public function __destruct()
    {
        if ($this->app === null) {
            return;
        }
        if (!$this->ffi->newrelic_destroy_app(FFI::addr($this->app))) {
            // TODO: signal failure without throwing an exception
            // throw new Exception('newrelic_destroy_app() failed');
        }
    }
}
