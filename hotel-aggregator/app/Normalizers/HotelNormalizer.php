<?php

namespace App\Normalizers;

class HotelNormalizer
{
    public function fromSupplierA(array $data): array
    {
        $normalized = [];

        if (!isset($data['hotels'])) {
            return $normalized;
        }

        foreach ($data['hotels'] as $hotel) {
            $normalized[] = [
                "id" => $hotel['id'],
                "name" => $hotel['hotel_name'],
                "city" => $hotel['city'],
                "price" => $hotel['price_usd'],
                "rating" => $hotel['stars'],
                "supplier" => "supplierA"
            ];
        }

        return $normalized;
    }

    public function fromSupplierB(array $data): array
    {
        $normalized = [];

        if (!isset($data['results'])) {
            return $normalized;
        }

        foreach ($data['results'] as $hotel) {
            $normalized[] = [
                "id" => $hotel['hotelId'],
                "name" => $hotel['name'],
                "city" => $hotel['location'],
                "price" => $hotel['cost'],
                "rating" => $hotel['rating'],
                "supplier" => "supplierB"
            ];
        }

        return $normalized;
    }
}