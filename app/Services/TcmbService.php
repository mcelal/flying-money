<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class TcmbService
{
    public function getTcmbData()
    {
        $response = Http::get('https://www.tcmb.gov.tr/kurlar/today.xml');

        if ($response->failed()) {
            return false;
        }

        $response = simplexml_load_string($response->body());
        $response = json_decode(json_encode($response));

        // Datayı düzenle
        $data = [];
        foreach ($response->Currency as $currency) {
            $data[$currency->{'@attributes'}->Kod] = $currency;
        }

        $response = [
            'date' => $response->{'@attributes'}->Date,
            'list' => $data,
        ];

        // Cache kaydet
        Cache::put('tcmb', $response, now()->addHours(10));

        return $response;
    }

    public function getCurrency($code = null)
    {
        $data = Cache::remember('tcmb', now()->addHours(10), function () {
            return $this->getTcmbData();
        });

        if (! $code) {
            return $data;
        }

        if (! isset($data['list'][$code])) {
            return null;
        }

        return (float) $data['list'][$code]->ForexBuying;
    }

    public function calcSalary(int $salary, string $currency = 'USD')
    {
        $currencyPrice = $this->getCurrency($currency);

        return [
            'currency' => $currencyPrice,
            'price'    => $salary / $currencyPrice
        ];
    }
}
