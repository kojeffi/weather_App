<?php

namespace App\Http\Controllers;

use App\Services\WeatherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WeatherController extends Controller
{
    protected WeatherService $weatherService;

    public function __construct(WeatherService $weatherService)
    {
        $this->weatherService = $weatherService;
    }

    public function current(Request $request): JsonResponse
    {
        $request->validate([
            'city' => 'required|string'
        ]);

        try {
            $data = $this->weatherService->getCurrentWeather($request->city);
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function forecast(Request $request): JsonResponse
    {
        $request->validate([
            'city' => 'required|string'
        ]);

        try {
            $data = $this->weatherService->getForecast($request->city);
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }
}