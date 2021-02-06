<?php

namespace NewRelicFfi;

use Exception;
use FFI;

class AppConfig
{
    /** @internal */
    public FFI $ffi;

    /** @internal */
    public FFI\CData $config;

    public function __construct(Api $api, string $appName, string $licenceKey)
    {
        $this->ffi = $api->ffi;
        $config = $this->ffi->newrelic_create_app_config($appName, $licenceKey);
        if ($config === null) {
            throw new Exception('newrelic_create_app_config() failed');
        }
        $this->config = $config;
    }

    public function __destruct()
    {
        if ($this->config === null) {
            return;
        }
        if (!$this->ffi->newrelic_destroy_app_config(FFI::addr($this->config))) {
            // TODO: signal failure without throwing an exception
            // throw new Exception('newrelic_destroy_app_config() failed');
        }
    }
}
