<?php

namespace App\Libraries;

class LoggerHelper
{
    public static function logSupplierCall(string $supplier, string $url, array $params, ?array $response, float $duration, ?string $error = null)
    {
        $logData = [
            'supplier' => $supplier,
            'url' => $url,
            'params' => $params,
            'duration_ms' => round($duration * 1000, 2),
            'response_size' => $response ? count($response) : 0,
            'error' => $error
        ];

        log_message('info', 'Supplier Call: ' . json_encode($logData));
    }
}