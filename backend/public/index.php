<?php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    if (!is_string($context['APP_ENV'])) {
        throw new RuntimeException('APP_ENV must be a string');
    }

    if (!is_bool($context['APP_DEBUG'])) {
        throw new RuntimeException('APP_DEBUG must be a boolean');
    }

    return new Kernel($context['APP_ENV'], $context['APP_DEBUG']);
};
