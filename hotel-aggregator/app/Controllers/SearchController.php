<?php 

namespace App\Controllers;

// CodeIgniter modules
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\Throttle\Throttler;

// custom modules
use App\Suppliers\SupplierAClient;
use App\Suppliers\SupplierBClient;
use App\Normalizers\HotelNormalizer;

class SearchController extends ResourceController{
    public function hotels(){

        // call rate limiter checker
        if ($this->rateLimiter()) {
            return $this->failTooManyRequests('Rate limit exceeded.');
        }

        // rules
        // $rules = [
        //     'city' => 'required',
        //     'checkin' => 'required|valid_date',
        //     'checkout' => 'required|valid_date',
        //     'guests' => 'required|integer'
        // ];

        // // check if parameters apply rules
        // if (!$this->validate($rules)) {
        //     return $this->failValidationErrors($this->validator->getErrors());
        // }


        // collect parameters (NEW)
        $params = $this->request->getGet();


        // Generate a cache key based on search parameters
        $cacheKey = 'hotel_search_' . md5(json_encode($params));

        // Try to get cached results
        $cached = cache()->get($cacheKey);

        if ($cached !== null) {
            return $this->respond([
                "results" => $cached,
                "cached" => true
            ]);
        }

        // call to suppliers externally
        $firstSupplier = new SupplierAClient();
        $secondSupplier = new SupplierBClient();
        $normalizer = new HotelNormalizer();


        try {
            $resultA = $firstSupplier->search($params);
        } catch (\Throwable $e) {
            log_message('error', 'SupplierA failed: ' . $e->getMessage());
            $resultA = [];
        }

        try {
            $resultB = $secondSupplier->search($params);
        } catch (\Throwable $e) {
            log_message('error', 'SupplierB failed: ' . $e->getMessage());
            $resultB = [];
        }

        
        // merge all data togather
        $hotels = $this->mergeData($resultA, $resultB, $normalizer);

        // filter Data
        $hotels = $this->filterData($hotels, $params);

        // Sort Data
        $hotels = $this->sortData($hotels, $params);

        // Save results to cache for 60 seconds
        cache()->save($cacheKey, $hotels, 60);

        // send response
        return $this->respond([
            "results" => $hotels,
            "cached" => false
        ]);
    }


    private function rateLimiter(){
        $throttler = service('throttler');
        $key = md5($this->request->getIPAddress());

        return !$throttler->check($key, 10, 60);
    }
    

    private function mergeData(
        array $firstSupplier, 
        array $secondSupplier, 
        HotelNormalizer $normalizer) : array
        {
        $temp = array_unique([], SORT_REGULAR);

        if ($firstSupplier) {
            $temp = array_merge($temp, $normalizer->fromSupplierA($firstSupplier));
        }

        if ($secondSupplier) {
            $temp = array_merge($temp, $normalizer->fromSupplierB($secondSupplier));
        }

        return $temp;
    }

    private function filterData(
        array $mainData, 
        array $params) : array
        {
            
        $temp = array_filter($mainData, function($h) use ($params) {
            // Apply min_price only if it's numeric and not empty
            if (!empty($params['min_price']) && is_numeric($params['min_price']) && $h['price'] < $params['min_price']) return false;
            // Apply max_price only if it's numeric and not empty
            if (!empty($params['max_price']) && is_numeric($params['max_price']) && $h['price'] > $params['max_price']) return false;
            // Apply min_rating only if it's numeric and not empty
            if (!empty($params['min_rating']) && is_numeric($params['min_rating']) && $h['rating'] < $params['min_rating']) return false;
            // Apply city filter if provided
            if (!empty($params['city']) && strtolower($h['city']) !== strtolower($params['city'])) return false;
            return true;
        });

        return array_values($temp);
    }

    private function sortData(
        array $mainData, 
        array $params) : array
        {
        $temp = $mainData;

        if (isset($params['sort']) && !empty($temp)) {
            switch ($params['sort']) {
                case 'price_asc':
                    usort($temp, fn($a,$b) => $a['price'] <=> $b['price']);
                    break;
                case 'price_desc':
                    usort($temp, fn($a,$b) => $b['price'] <=> $a['price']);
                    break;
                case 'rating_desc':
                    usort($temp, fn($a,$b) => $b['rating'] <=> $a['rating']);
                    break;
                case 'rating_asc':
                    usort($temp, fn($a,$b) => $a['rating'] <=> $b['rating']);
                    break;
            }
        }

        return $temp;
    }
}
