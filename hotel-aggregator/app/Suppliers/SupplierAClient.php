<?php

namespace App\Suppliers;
use App\Libraries\LoggerHelper;

class SupplierAClient
{
    protected $baseUrlA = "http://localhost:8081/mock/supplierA/search";

    public function search(array $params)
    {
        $client = \Config\Services::curlrequest([
            'timeout' => 3
        ]);

        $startTimer = microtime(true);

        try {
            $response = $client->get($this->baseUrlA, [
                'query' => $params
            ]);
            $data = json_decode($response->getBody(), true);

            // finish timer and logging
            $finishTimer = microtime(true) - $startTimer;
            LoggerHelper::logSupplierCall('supplierA', $this->baseUrlA, $params, $data, $finishTimer);

            return $data;

        } catch (\Exception $e) {

            // finish timer and logging
            $finishTimer = microtime(true) - $startTimer;
            LoggerHelper::logSupplierCall('supplierA', $this->baseUrlA, $params, null, $finishTimer, $e->getMessage());

            return null;
        }
    }
}