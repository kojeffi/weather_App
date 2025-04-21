<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class WeatherService
{
    protected Client $client;
    protected string $apiKey;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = env('OPENWEATHER_API_KEY');
    }

    public function getCoordinates(string $city): array
    {
        try {
            $response = $this->client->get(env('OPENWEATHER_GEOCODE_URL'), [
                'query' => [
                    'q' => $city,
                    'limit' => 1,
                    'appid' => $this->apiKey
                ]
            ]);

            $data = json_decode($response->getBody(), true);

            if (empty($data)) {
                throw new \Exception('City not found');
            }

            return [
                'lat' => $data[0]['lat'],
                'lon' => $data[0]['lon'],
                'name' => $data[0]['name'],
                'country' => $data[0]['country']
            ];
        } catch (GuzzleException $e) {
            throw new \Exception('Failed to fetch coordinates: ' . $e->getMessage());
        }
    }

    public function getCurrentWeather(string $city): array
    {
        $coordinates = $this->getCoordinates($city);

        try {
            $response = $this->client->get(env('OPENWEATHER_CURRENT_URL'), [
                'query' => [
                    'lat' => $coordinates['lat'],
                    'lon' => $coordinates['lon'],
                    'appid' => $this->apiKey,
                    'units' => 'metric'
                ]
            ]);

            $data = json_decode($response->getBody(), true);

            return [
                'name' => $data['name'],
                'main' => $data['main'],
                'weather' => $data['weather'],
                'wind' => $data['wind'],
                'dt' => $data['dt']
            ];
        } catch (GuzzleException $e) {
            throw new \Exception('Failed to fetch weather: ' . $e->getMessage());
        }
    }

    public function getForecast(string $city): array
    {
        $coordinates = $this->getCoordinates($city);

        try {
            $response = $this->client->get(env('OPENWEATHER_FORECAST_URL'), [
                'query' => [
                    'lat' => $coordinates['lat'],
                    'lon' => $coordinates['lon'],
                    'appid' => $this->apiKey,
                    'units' => 'metric',
                    'cnt' => 24 // 24 intervals (3 days)
                ]
            ]);

            $data = json_decode($response->getBody(), true);

            // Group by day
            $forecast = [];
            foreach ($data['list'] as $item) {
                $date = date('Y-m-d', $item['dt']);
                if (!isset($forecast[$date])) {
                    $forecast[$date] = [
                        'temp_min' => $item['main']['temp_min'],
                        'temp_max' => $item['main']['temp_max'],
                        'weather' => $item['weather'][0],
                        'dt' => $item['dt']
                    ];
                } else {
                    if ($item['main']['temp_min'] < $forecast[$date]['temp_min']) {
                        $forecast[$date]['temp_min'] = $item['main']['temp_min'];
                    }
                    if ($item['main']['temp_max'] > $forecast[$date]['temp_max']) {
                        $forecast[$date]['temp_max'] = $item['main']['temp_max'];
                    }
                }
            }

            return array_values($forecast);
        } catch (GuzzleException $e) {
            throw new \Exception('Failed to fetch forecast: ' . $e->getMessage());
        }
    }
}