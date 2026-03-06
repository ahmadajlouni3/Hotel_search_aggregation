<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;

class MockSupplierAController extends ResourceController {
    public function supplierA (){
        $data = [
            "hotels" => [
                [
                    "id" => "A1",
                    "hotel_name" => "Grand Palace",
                    "city" => "Dubai",
                    "price_usd" => 180,
                    "stars" => 4.5
                ],
                [
                    "id" => "A2",
                    "hotel_name" => "Sea View Resort",
                    "city" => "Dubai",
                    "price_usd" => 220,
                    "stars" => 5
                ]
            ]
        ];

        return $this->respond($data);
    }
}