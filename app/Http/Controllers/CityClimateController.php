<?php

namespace App\Http\Controllers;

use App\Models\CityClimates;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CityClimateController extends Controller
{
    public function cityWeather(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        $city = CityClimates::where('name', $request->name)->first();

        if (!empty($city)) {

            $request_hours = Carbon::parse($city->created_at)->format('h');
            $current_time = now()->format('h');

            if ($request_hours == $current_time) {
                return response()->json([
                    'currentClimate' => "Temperatura actual en" . $city->name . " es de " . $city->clima . "℃.". " Horario: ". now()->format('H:i:s'),
                ]);
            } else {
                $queryString = http_build_query([
                    'access_key' => 'cd908503b72642c241867c9050bbb55e',
                    'query' => $request->name,
                ]);

                $response = Http::get('http://api.weatherstack.com/current?', $queryString);
                $api_result = json_decode($response, true);

                $updateClimate = CityClimates::where('name', $request->name)->first();
                $updateClimate->clima = $api_result['current']['temperature'];
                $updateClimate->save();

                return response()->json([
                    'previousClimate' => "Temperatura a las ". Carbon::parse($city->created_at)->format('H:i:s'). " fue de ". $city->clima. "℃",
                    'currentClimate' => "Temperatura actual en " . $request->name . " es de " . $api_result['current']['temperature'] . "℃",
                ]);
            }

        } else {
            $queryString = http_build_query([
                'access_key' => 'cd908503b72642c241867c9050bbb55e',
                'query' => $request->name,
            ]);

            $response = Http::get('http://api.weatherstack.com/current?', $queryString);
            $api_result = json_decode($response, true);

            CityClimates::create([
                'name' => $request->name,
                'clima' => $api_result['current']['temperature'],
            ]);

            return response()->json([
                'currentClimate' => "Temperatura actual en" . $request->name . "es de " . $api_result['current']['temperature'] . "℃". " Horario: ". now()->format('H:i:s'),
            ]);
        }
    }
}
