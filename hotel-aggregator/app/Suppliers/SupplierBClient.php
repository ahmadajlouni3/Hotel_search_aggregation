<?php

namespace App\Suppliers;
use App\Libraries\LoggerHelper;

class SupplierBClient
{
    protected $baseUrlB = 'https://hotel-search-aggregation-2.onrender.com/mock/supplierB/search';

    public function search(array $params)
    {
        $client = \Config\Services::curlrequest([
            'timeout' => 3
        ]);

        $startTimer = microtime(true);

        try {
            $response = $client->get($this->baseUrlB, [
                'query' => $params
            ]);
            $data = json_decode($response->getBody(), true);

            // finish timer and logging
            $finishTimer = microtime(true) - $startTimer;
            LoggerHelper::logSupplierCall('supplierB', $this->baseUrlB, $params, $data, $finishTimer);


            return $data;

        } catch (\Exception $e) {

            // finish timer and logging
            $finishTimer = microtime(true) - $startTimer;
            LoggerHelper::logSupplierCall('supplierB', $this->baseUrlB, $params, null, $finishTimer, $e->getMessage());

            return null;
        }
    }
}
