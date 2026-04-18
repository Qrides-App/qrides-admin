<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\BookingExtension;
use App\Models\GeneralSetting;
use App\Services\FirestoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class RideTrackingController extends Controller
{
    public function show(string $token): View
    {
        $extension = $this->findSharedExtension($token);
        $snapshot = $this->buildSnapshot($extension);
        $apiGoogleMapKey = GeneralSetting::where('meta_key', 'api_google_map_key')->value('meta_value');

        return view('Front.ride-tracking', [
            'snapshot' => $snapshot,
            'shareToken' => $token,
            'apiGoogleMapKey' => $apiGoogleMapKey,
        ]);
    }

    public function snapshot(string $token): JsonResponse
    {
        $extension = $this->findSharedExtension($token);

        return response()->json([
            'success' => true,
            'data' => $this->buildSnapshot($extension),
        ]);
    }

    private function findSharedExtension(string $token): BookingExtension
    {
        return BookingExtension::with([
            'booking.host',
            'booking.item.item_Type',
            'booking.item.itemVehicle',
            'booking.item.vehicleMake',
        ])
            ->where('share_token', $token)
            ->where('share_tracking_enabled', true)
            ->where(function ($query) {
                $query->whereNull('share_token_expires_at')
                    ->orWhere('share_token_expires_at', '>', now());
            })
            ->firstOrFail();
    }

    private function buildSnapshot(BookingExtension $extension): array
    {
        $booking = $extension->booking;
        $pickup = is_array($extension->pickup_location) ? $extension->pickup_location : [];
        $dropoff = is_array($extension->dropoff_location) ? $extension->dropoff_location : [];
        $firebasePayload = json_decode($booking->firebase_json ?? '{}', true) ?: [];
        $liveLocation = $this->extractLiveLocation($firebasePayload);

        if (! $liveLocation && $booking->host?->firestore_id) {
            $firestorePayload = $this->resolveFirestoreLocation($booking->host->firestore_id);
            $liveLocation = $this->extractLiveLocation($firestorePayload);
        }

        $dropLat = $this->toFloat($dropoff['latitude'] ?? null);
        $dropLng = $this->toFloat($dropoff['longitude'] ?? null);
        $distanceToDropKm = null;

        if ($liveLocation && $dropLat !== null && $dropLng !== null) {
            $distanceToDropKm = round(
                $this->calculateDistanceKm($liveLocation['latitude'], $liveLocation['longitude'], $dropLat, $dropLng),
                2
            );
        }

        return [
            'booking' => [
                'id' => $booking->id,
                'token' => $booking->token,
                'status' => $booking->status,
                'ride_id' => $extension->ride_id,
                'created_at' => optional($booking->created_at)->toDateTimeString(),
            ],
            'driver' => [
                'name' => trim(($booking->host->first_name ?? '').' '.($booking->host->last_name ?? '')),
            ],
            'vehicle' => [
                'type' => $booking->item->item_Type->name ?? null,
                'make' => $booking->item->vehicleMake->name ?? null,
                'model' => $booking->item->model ?? null,
                'number' => $booking->item->registration_number ?? null,
                'color' => $booking->item->itemVehicle->color ?? null,
            ],
            'pickup' => [
                'address' => $pickup['address'] ?? null,
                'latitude' => $this->toFloat($pickup['latitude'] ?? null),
                'longitude' => $this->toFloat($pickup['longitude'] ?? null),
            ],
            'dropoff' => [
                'address' => $dropoff['address'] ?? null,
                'latitude' => $dropLat,
                'longitude' => $dropLng,
            ],
            'live_location' => $liveLocation,
            'distance_to_drop_km' => $distanceToDropKm,
            'estimated_distance_km' => $extension->estimated_distance_km !== null ? (float) $extension->estimated_distance_km : null,
            'estimated_duration_min' => $extension->estimated_duration_min !== null ? (int) $extension->estimated_duration_min : null,
            'share_expires_at' => optional($extension->share_token_expires_at)->toDateTimeString(),
        ];
    }

    private function resolveFirestoreLocation(string $firestoreId): array
    {
        try {
            $service = app(FirestoreService::class);

            return $service->getDocument("drivers/{$firestoreId}") ?? [];
        } catch (\Throwable $exception) {
            return [];
        }
    }

    private function extractLiveLocation(array $payload): ?array
    {
        if (isset($payload['geo']['geopoint'][0], $payload['geo']['geopoint'][1])) {
            return [
                'latitude' => $this->toFloat($payload['geo']['geopoint'][0]),
                'longitude' => $this->toFloat($payload['geo']['geopoint'][1]),
            ];
        }

        foreach ([
            ['latitude', 'longitude'],
            ['lat', 'lng'],
            ['lat', 'long'],
            ['driver_latitude', 'driver_longitude'],
            ['current_latitude', 'current_longitude'],
        ] as [$latKey, $lngKey]) {
            if (isset($payload[$latKey], $payload[$lngKey])) {
                return [
                    'latitude' => $this->toFloat($payload[$latKey]),
                    'longitude' => $this->toFloat($payload[$lngKey]),
                ];
            }
        }

        foreach ($payload as $value) {
            if (is_array($value)) {
                $nested = $this->extractLiveLocation($value);
                if ($nested) {
                    return $nested;
                }
            }
        }

        return null;
    }

    private function calculateDistanceKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLon / 2) * sin($dLon / 2);

        return $earthRadius * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    private function toFloat($value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
