<?php

namespace NewRelicFfi;

use Exception;
use FFI;

class AppConfig
{
    /** @internal */
    public FFI $ffi;

    /**
     * C-API `newrelic_app_config_t*`
     * @internal
     */
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

    public function setDistributedTracingEnabled(bool $enabled): void
    {
        $this->config->distributed_tracing->enabled = $enabled;
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

    /**
     * factory function to create an app
     */
    public function createApp(int $timeoutMs): App
    {
        return new App($this, $timeoutMs);
    }
}
