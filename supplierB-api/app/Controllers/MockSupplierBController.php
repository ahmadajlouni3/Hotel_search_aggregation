<?php 

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;

class MockSupplierBController extends ResourceController {
    public function supplierB () {
        $simulateSlow = $this->request->getGet("slow");
        $simulateFail = $this->request->getGet("fail");

        if ($simulateSlow) {
            sleep(5);
        }

        if ($simulateFail) {
            return $this->failServerError("Supplier B simulated failure");
        }

        $data = [
            "results" => [
                [
                    "hotelId" => "B9",
                    "name" => "Royal Atlantis",
                    "location" => "Dubai",
                    "cost" => 210,
                    "rating" => 4.7
                ],
                [
                    "hotelId" => "B10",
                    "name" => "Palm Resort",
                    "location" => "Dubai",
                    "cost" => 150,
                    "rating" => 4.2
                ]
            ]
        ];

        return $this->respond($data);
    }
}