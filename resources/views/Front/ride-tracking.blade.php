<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Ride Tracking</title>
    <style>
        :root {
            --teal: #0f766e;
            --teal-soft: rgba(15, 118, 110, 0.10);
            --slate: #475569;
            --ink: #0f172a;
            --line: #dbe4ec;
            --danger: #dc2626;
            --bg: linear-gradient(180deg, #f4f8fb 0%, #ebf2f7 100%);
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Poppins", "Segoe UI", sans-serif;
            color: var(--ink);
            background: var(--bg);
        }
        .tracking-shell {
            max-width: 1240px;
            margin: 0 auto;
            padding: 24px 18px 32px;
        }
        .tracking-hero {
            display: grid;
            grid-template-columns: 1.3fr 0.9fr;
            gap: 18px;
            margin-bottom: 18px;
        }
        .tracking-card {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 24px;
            box-shadow: 0 18px 60px rgba(15, 23, 42, 0.06);
        }
        .tracking-card__body { padding: 24px; }
        .tracking-eyebrow {
            display: inline-block;
            font-size: 11px;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--teal);
            font-weight: 700;
            margin-bottom: 8px;
        }
        .tracking-title {
            margin: 0;
            font-size: 32px;
            font-weight: 700;
        }
        .tracking-subtitle {
            margin: 10px 0 0;
            color: var(--slate);
            line-height: 1.6;
        }
        .tracking-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            margin-top: 18px;
            border-radius: 999px;
            background: var(--teal-soft);
            color: var(--teal);
            font-weight: 700;
        }
        .tracking-status__dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: currentColor;
        }
        .tracking-quick-stats {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }
        .tracking-stat {
            padding: 18px;
            border-radius: 18px;
            background: #f8fbfd;
            border: 1px solid #e8eef4;
        }
        .tracking-stat span {
            display: block;
            font-size: 12px;
            color: var(--slate);
            font-weight: 600;
        }
        .tracking-stat strong {
            display: block;
            margin-top: 10px;
            font-size: 24px;
        }
        .tracking-layout {
            display: grid;
            grid-template-columns: 0.92fr 1.08fr;
            gap: 18px;
        }
        .tracking-section-title {
            margin: 0 0 18px;
            font-size: 22px;
        }
        .tracking-list {
            display: grid;
            gap: 12px;
        }
        .tracking-list__item {
            padding: 16px;
            border-radius: 16px;
            border: 1px solid #e7eef5;
            background: #fbfdff;
        }
        .tracking-list__item label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--slate);
            margin-bottom: 8px;
        }
        .tracking-list__item p {
            margin: 0;
            line-height: 1.55;
        }
        .tracking-map {
            overflow: hidden;
        }
        #rideTrackingMap {
            width: 100%;
            height: 520px;
            background: #edf4f7;
        }
        .tracking-map__footer {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            padding: 18px 24px 24px;
            color: var(--slate);
        }
        .tracking-empty {
            padding: 18px;
            border-radius: 16px;
            background: rgba(220, 38, 38, 0.08);
            color: var(--danger);
            font-weight: 600;
        }
        @media (max-width: 991px) {
            .tracking-hero,
            .tracking-layout {
                grid-template-columns: 1fr;
            }
            #rideTrackingMap {
                height: 380px;
            }
        }
    </style>
</head>
<body>
    <div class="tracking-shell">
        <div class="tracking-hero">
            <div class="tracking-card">
                <div class="tracking-card__body">
                    <span class="tracking-eyebrow">Shared ride tracking</span>
                    <h1 class="tracking-title">Ride {{ $snapshot['booking']['token'] ?? '' }}</h1>
                    <p class="tracking-subtitle">This page shows the driver vehicle, live ride position, and remaining distance to the drop point. Refreshes happen automatically.</p>
                    <div class="tracking-status">
                        <span class="tracking-status__dot"></span>
                        <span id="bookingStatusLabel">{{ $snapshot['booking']['status'] ?? 'Unavailable' }}</span>
                    </div>
                </div>
            </div>

            <div class="tracking-card">
                <div class="tracking-card__body">
                    <div class="tracking-quick-stats">
                        <div class="tracking-stat">
                            <span>Distance to drop</span>
                            <strong id="distanceToDrop">{{ $snapshot['distance_to_drop_km'] !== null ? $snapshot['distance_to_drop_km'] . ' km' : '--' }}</strong>
                        </div>
                        <div class="tracking-stat">
                            <span>Estimated ride distance</span>
                            <strong id="estimatedDistance">{{ $snapshot['estimated_distance_km'] !== null ? $snapshot['estimated_distance_km'] . ' km' : '--' }}</strong>
                        </div>
                        <div class="tracking-stat">
                            <span>Estimated duration</span>
                            <strong id="estimatedDuration">{{ $snapshot['estimated_duration_min'] !== null ? $snapshot['estimated_duration_min'] . ' min' : '--' }}</strong>
                        </div>
                        <div class="tracking-stat">
                            <span>Share link valid until</span>
                            <strong id="shareExpiresAt">{{ $snapshot['share_expires_at'] ?? '--' }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tracking-layout">
            <div class="tracking-card">
                <div class="tracking-card__body">
                    <h2 class="tracking-section-title">Ride details</h2>
                    <div class="tracking-list">
                        <div class="tracking-list__item">
                            <label>Driver</label>
                            <p id="driverName">{{ $snapshot['driver']['name'] ?: 'Driver assigned' }}</p>
                        </div>
                        <div class="tracking-list__item">
                            <label>Vehicle</label>
                            <p id="vehicleDetails">
                                {{ trim(($snapshot['vehicle']['make'] ?? '') . ' ' . ($snapshot['vehicle']['model'] ?? '')) ?: 'Vehicle details unavailable' }}
                                @if (!empty($snapshot['vehicle']['number']))
                                    <br>{{ $snapshot['vehicle']['number'] }}
                                @endif
                            </p>
                        </div>
                        <div class="tracking-list__item">
                            <label>Pickup</label>
                            <p id="pickupAddress">{{ $snapshot['pickup']['address'] ?? 'Unavailable' }}</p>
                        </div>
                        <div class="tracking-list__item">
                            <label>Drop point</label>
                            <p id="dropoffAddress">{{ $snapshot['dropoff']['address'] ?? 'Unavailable' }}</p>
                        </div>
                        <div class="tracking-list__item">
                            <label>Ride reference</label>
                            <p id="rideReference">{{ $snapshot['booking']['ride_id'] ?: ($snapshot['booking']['token'] ?? '--') }}</p>
                        </div>
                        <div class="tracking-empty" id="liveLocationFallback" @if (!empty($snapshot['live_location'])) style="display:none;" @endif>
                            Live vehicle coordinates are not available yet. The page will keep checking for updates.
                        </div>
                    </div>
                </div>
            </div>

            <div class="tracking-card tracking-map">
                <div id="rideTrackingMap"></div>
                <div class="tracking-map__footer">
                    <span id="liveCoordinates">
                        @if (!empty($snapshot['live_location']))
                            Live: {{ $snapshot['live_location']['latitude'] }}, {{ $snapshot['live_location']['longitude'] }}
                        @else
                            Waiting for live location...
                        @endif
                    </span>
                    <span id="lastUpdatedAt">Auto refresh: every 15 seconds</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        const initialSnapshot = @json($snapshot);
        const shareToken = @json($shareToken);
        const snapshotUrl = @json(route('ride-tracking.snapshot', ['token' => $shareToken]));
        const googleMapApiKey = @json($apiGoogleMapKey);
        let trackingMap;
        let pickupMarker;
        let dropoffMarker;
        let vehicleMarker;
        let routeLine;

        function renderSnapshot(snapshot) {
            document.getElementById('bookingStatusLabel').textContent = snapshot.booking.status || 'Unavailable';
            document.getElementById('distanceToDrop').textContent = snapshot.distance_to_drop_km !== null ? `${snapshot.distance_to_drop_km} km` : '--';
            document.getElementById('estimatedDistance').textContent = snapshot.estimated_distance_km !== null ? `${snapshot.estimated_distance_km} km` : '--';
            document.getElementById('estimatedDuration').textContent = snapshot.estimated_duration_min !== null ? `${snapshot.estimated_duration_min} min` : '--';
            document.getElementById('shareExpiresAt').textContent = snapshot.share_expires_at || '--';
            document.getElementById('driverName').textContent = snapshot.driver.name || 'Driver assigned';
            document.getElementById('vehicleDetails').innerHTML =
                `${[snapshot.vehicle.make, snapshot.vehicle.model].filter(Boolean).join(' ') || 'Vehicle details unavailable'}${snapshot.vehicle.number ? `<br>${snapshot.vehicle.number}` : ''}`;
            document.getElementById('pickupAddress').textContent = snapshot.pickup.address || 'Unavailable';
            document.getElementById('dropoffAddress').textContent = snapshot.dropoff.address || 'Unavailable';
            document.getElementById('rideReference').textContent = snapshot.booking.ride_id || snapshot.booking.token || '--';
            document.getElementById('liveCoordinates').textContent = snapshot.live_location
                ? `Live: ${snapshot.live_location.latitude}, ${snapshot.live_location.longitude}`
                : 'Waiting for live location...';
            document.getElementById('lastUpdatedAt').textContent = `Last refresh: ${new Date().toLocaleTimeString()}`;
            document.getElementById('liveLocationFallback').style.display = snapshot.live_location ? 'none' : 'block';

            updateMap(snapshot);
        }

        function updateMap(snapshot) {
            if (!window.google || !trackingMap) {
                return;
            }

            const bounds = new google.maps.LatLngBounds();
            const pickup = toLatLng(snapshot.pickup);
            const dropoff = toLatLng(snapshot.dropoff);
            const live = toLatLng(snapshot.live_location);

            if (pickup) {
                if (!pickupMarker) {
                    pickupMarker = new google.maps.Marker({ map: trackingMap, label: 'P', title: 'Pickup' });
                }
                pickupMarker.setPosition(pickup);
                bounds.extend(pickup);
            }

            if (dropoff) {
                if (!dropoffMarker) {
                    dropoffMarker = new google.maps.Marker({ map: trackingMap, label: 'D', title: 'Drop point' });
                }
                dropoffMarker.setPosition(dropoff);
                bounds.extend(dropoff);
            }

            if (live) {
                if (!vehicleMarker) {
                    vehicleMarker = new google.maps.Marker({
                        map: trackingMap,
                        title: 'Vehicle',
                        icon: {
                            path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
                            scale: 5,
                            strokeColor: '#0f766e',
                            fillColor: '#0f766e',
                            fillOpacity: 1
                        }
                    });
                }
                vehicleMarker.setPosition(live);
                bounds.extend(live);
            }

            if (window.google.maps.Polyline) {
                const linePath = [pickup, live, dropoff].filter(Boolean);
                if (!routeLine) {
                    routeLine = new google.maps.Polyline({
                        map: trackingMap,
                        geodesic: true,
                        strokeColor: '#0f766e',
                        strokeOpacity: 0.9,
                        strokeWeight: 4
                    });
                }
                routeLine.setPath(linePath);
            }

            if (!bounds.isEmpty()) {
                trackingMap.fitBounds(bounds, 70);
            }
        }

        function toLatLng(point) {
            if (!point || point.latitude === null || point.longitude === null || point.latitude === undefined || point.longitude === undefined) {
                return null;
            }

            return new google.maps.LatLng(Number(point.latitude), Number(point.longitude));
        }

        function initMap() {
            const defaultCenter = {
                lat: Number(initialSnapshot.pickup.latitude || initialSnapshot.dropoff.latitude || 28.6139),
                lng: Number(initialSnapshot.pickup.longitude || initialSnapshot.dropoff.longitude || 77.2090),
            };

            trackingMap = new google.maps.Map(document.getElementById('rideTrackingMap'), {
                zoom: 13,
                center: defaultCenter,
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: true,
            });

            renderSnapshot(initialSnapshot);
        }

        function pollSnapshot() {
            fetch(snapshotUrl, { headers: { 'Accept': 'application/json' } })
                .then(response => response.json())
                .then(payload => {
                    if (payload && payload.data) {
                        renderSnapshot(payload.data);
                    }
                })
                .catch(() => {});
        }

        if (googleMapApiKey) {
            const script = document.createElement('script');
            script.src = `https://maps.googleapis.com/maps/api/js?key=${googleMapApiKey}&callback=initMap`;
            script.async = true;
            script.defer = true;
            document.head.appendChild(script);
        }

        setInterval(pollSnapshot, 15000);
    </script>
</body>
</html>
