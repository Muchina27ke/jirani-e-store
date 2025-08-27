<?php
/**
 * Geolocation and Geofencing System for Jirani Platform
 * Handles location-based features and delivery zone management
 */

class Geolocation
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /**
     * Save user location
     */
    public function saveUserLocation($userId, $latitude, $longitude, $address = '')
    {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO user_locations (user_id, latitude, longitude, address, updated_at) 
                VALUES (?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                    latitude = VALUES(latitude), 
                    longitude = VALUES(longitude), 
                    address = VALUES(address), 
                    updated_at = VALUES(updated_at)
            ");
            $stmt->bind_param("idds", $userId, $latitude, $longitude, $address);

            return $stmt->execute();

        } catch (Exception $e) {
            error_log("Failed to save user location: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user location
     */
    public function getUserLocation($userId)
    {
        $stmt = $this->conn->prepare("
            SELECT * FROM user_locations 
            WHERE user_id = ? 
            ORDER BY updated_at DESC 
            LIMIT 1
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->num_rows > 0 ? $result->fetch_assoc() : null;
    }

    /**
     * Find products within radius
     */
    public function findProductsNearLocation($latitude, $longitude, $radiusKm = 10, $limit = 50)
    {
        // Using Haversine formula to calculate distance
        $stmt = $this->conn->prepare("
            SELECT p.*, v.business_name as vendor_name, v.user_id as vendor_user_id,
                   u.phone as vendor_phone,
                   (6371 * acos(cos(radians(?)) * cos(radians(p.location_lat)) * 
                   cos(radians(p.location_lng) - radians(?)) + 
                   sin(radians(?)) * sin(radians(p.location_lat)))) AS distance
            FROM products p
            JOIN vendors v ON p.vendor_id = v.user_id
            JOIN users u ON v.user_id = u.id
            WHERE p.status = 'active' 
                AND p.location_lat IS NOT NULL 
                AND p.location_lng IS NOT NULL
                AND v.status = 'approved'
            HAVING distance <= ?
            ORDER BY distance ASC
            LIMIT ?
        ");
        $stmt->bind_param("ddddi", $latitude, $longitude, $latitude, $radiusKm, $limit);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Find vendors within radius
     */
    public function findVendorsNearLocation($latitude, $longitude, $radiusKm = 10)
    {
        $stmt = $this->conn->prepare("
            SELECT DISTINCT v.*, u.name, u.phone, u.email,
                   AVG((6371 * acos(cos(radians(?)) * cos(radians(p.location_lat)) * 
                   cos(radians(p.location_lng) - radians(?)) + 
                   sin(radians(?)) * sin(radians(p.location_lat))))) AS avg_distance,
                   COUNT(p.id) as product_count
            FROM vendors v
            JOIN users u ON v.user_id = u.id
            JOIN products p ON v.user_id = p.vendor_id
            WHERE v.status = 'approved' 
                AND p.status = 'active'
                AND p.location_lat IS NOT NULL 
                AND p.location_lng IS NOT NULL
            GROUP BY v.user_id
            HAVING avg_distance <= ?
            ORDER BY avg_distance ASC
        ");
        $stmt->bind_param("dddd", $latitude, $longitude, $latitude, $radiusKm);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Create delivery zone for vendor
     */
    public function createDeliveryZone($vendorId, $zoneData)
    {
        try {
            // Ensure all required fields are set
            $zoneName = isset($zoneData['name']) ? $zoneData['name'] : '';
            $polygonJson = isset($zoneData['polygon']) ? json_encode($zoneData['polygon']) : null;
            $radiusKm = isset($zoneData['radius_km']) ? $zoneData['radius_km'] : null;
            $deliveryFee = isset($zoneData['delivery_fee']) ? $zoneData['delivery_fee'] : 0;
            $minOrderAmount = isset($zoneData['min_order_amount']) ? $zoneData['min_order_amount'] : 0;
            $isActive = isset($zoneData['is_active']) ? $zoneData['is_active'] : 1;

            $stmt = $this->conn->prepare("
                INSERT INTO geofences (vendor_id, zone_name, polygon_json, radius_km, delivery_fee, min_order_amount, is_active, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");

            $stmt->bind_param(
                "issdddi",
                $vendorId,
                $zoneName,
                $polygonJson,
                $radiusKm,
                $deliveryFee,
                $minOrderAmount,
                $isActive
            );

            if ($stmt->execute()) {
                return ['success' => true, 'zone_id' => $this->conn->insert_id];
            }

            error_log("MySQL Error: " . $stmt->error);
            return ['success' => false, 'message' => 'Failed to create delivery zone'];

        } catch (Exception $e) {
            error_log("Failed to create delivery zone: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error'];
        }
    }

    /**
     * Get vendor delivery zones
     */
    public function getVendorDeliveryZones($vendorId)
    {
        $stmt = $this->conn->prepare("
            SELECT * FROM geofences 
            WHERE vendor_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->bind_param("i", $vendorId);
        $stmt->execute();

        $zones = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Decode polygon JSON for each zone
        foreach ($zones as &$zone) {
            $zone['polygon'] = json_decode($zone['polygon_json'], true);
        }

        return $zones;
    }

    /**
     * Check if location is within vendor delivery zone
     */
    public function isLocationInDeliveryZone($vendorId, $latitude, $longitude)
    {
        $zones = $this->getVendorDeliveryZones($vendorId);

        foreach ($zones as $zone) {
            if ($zone['radius_km']) {
                // Check circular zone
                if ($this->isPointInCircle($latitude, $longitude, $zone['center_lat'], $zone['center_lng'], $zone['radius_km'])) {
                    return [
                        'in_zone' => true,
                        'zone' => $zone,
                        'delivery_fee' => $zone['delivery_fee'],
                        'min_order_amount' => $zone['min_order_amount']
                    ];
                }
            } elseif ($zone['polygon']) {
                // Check polygon zone
                if ($this->isPointInPolygon($latitude, $longitude, $zone['polygon'])) {
                    return [
                        'in_zone' => true,
                        'zone' => $zone,
                        'delivery_fee' => $zone['delivery_fee'],
                        'min_order_amount' => $zone['min_order_amount']
                    ];
                }
            }
        }

        return ['in_zone' => false];
    }

    /**
     * Check if point is within circular zone
     */
    private function isPointInCircle($lat, $lng, $centerLat, $centerLng, $radiusKm)
    {
        $distance = $this->calculateDistance($lat, $lng, $centerLat, $centerLng);
        return $distance <= $radiusKm;
    }

    /**
     * Check if point is within polygon using ray casting algorithm
     */
    private function isPointInPolygon($lat, $lng, $polygon)
    {
        $vertices = count($polygon);
        $inside = false;

        for ($i = 0, $j = $vertices - 1; $i < $vertices; $j = $i++) {
            $xi = $polygon[$i]['lat'];
            $yi = $polygon[$i]['lng'];
            $xj = $polygon[$j]['lat'];
            $yj = $polygon[$j]['lng'];

            if (
                (($yi > $lng) != ($yj > $lng)) &&
                ($lat < ($xj - $xi) * ($lng - $yi) / ($yj - $yi) + $xi)
            ) {
                $inside = !$inside;
            }
        }

        return $inside;
    }

    /**
     * Calculate distance between two points using Haversine formula
     */
    public function calculateDistance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Geocode address to coordinates
     */
    public function geocodeAddress($address)
    {
        try {
            $encodedAddress = urlencode($address);
            $url = "https://nominatim.openstreetmap.org/search?format=json&q={$encodedAddress}&limit=1";

            $context = stream_context_create([
                'http' => [
                    'header' => "User-Agent: Jirani-Platform/1.0\r\n",
                    'timeout' => 10
                ]
            ]);

            $response = file_get_contents($url, false, $context);

            if ($response === false) {
                return ['success' => false, 'message' => 'Failed to geocode address'];
            }

            $data = json_decode($response, true);

            if (!empty($data)) {
                return [
                    'success' => true,
                    'latitude' => (float) $data[0]['lat'],
                    'longitude' => (float) $data[0]['lon'],
                    'display_name' => $data[0]['display_name']
                ];
            }

            return ['success' => false, 'message' => 'Address not found'];

        } catch (Exception $e) {
            error_log("Geocoding error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Geocoding service error'];
        }
    }

    /**
     * Reverse geocode coordinates to address
     */
    public function reverseGeocode($latitude, $longitude)
    {
        try {
            $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$latitude}&lon={$longitude}";

            $context = stream_context_create([
                'http' => [
                    'header' => "User-Agent: Jirani-Platform/1.0\r\n",
                    'timeout' => 10
                ]
            ]);

            $response = file_get_contents($url, false, $context);

            if ($response === false) {
                return ['success' => false, 'message' => 'Failed to reverse geocode'];
            }

            $data = json_decode($response, true);

            if (isset($data['display_name'])) {
                return [
                    'success' => true,
                    'address' => $data['display_name'],
                    'details' => $data['address'] ?? []
                ];
            }

            return ['success' => false, 'message' => 'Location not found'];

        } catch (Exception $e) {
            error_log("Reverse geocoding error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Reverse geocoding service error'];
        }
    }

    /**
     * Get location analytics for admin
     */
    public function getLocationAnalytics()
    {
        // Most active locations
        $stmt = $this->conn->prepare("
            SELECT 
                ROUND(location_lat, 2) as lat_rounded,
                ROUND(location_lng, 2) as lng_rounded,
                COUNT(*) as order_count,
                SUM(total_amount) as total_revenue
            FROM orders 
            WHERE location_lat IS NOT NULL 
                AND location_lng IS NOT NULL
                AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY lat_rounded, lng_rounded
            ORDER BY order_count DESC
            LIMIT 20
        ");
        $stmt->execute();
        $hotspots = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Vendor coverage
        $stmt = $this->conn->prepare("
            SELECT 
                v.business_name,
                COUNT(DISTINCT ROUND(p.location_lat, 2), ROUND(p.location_lng, 2)) as coverage_points,
                COUNT(p.id) as total_products
            FROM vendors v
            JOIN products p ON v.user_id = p.vendor_id
            WHERE p.location_lat IS NOT NULL 
                AND p.location_lng IS NOT NULL
                AND v.status = 'approved'
            GROUP BY v.user_id
            ORDER BY coverage_points DESC
            LIMIT 10
        ");
        $stmt->execute();
        $vendorCoverage = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        return [
            'hotspots' => $hotspots,
            'vendor_coverage' => $vendorCoverage
        ];
    }

    /**
     * Update product location
     */
    public function updateProductLocation($productId, $latitude, $longitude)
    {
        $stmt = $this->conn->prepare("
            UPDATE products 
            SET location_lat = ?, location_lng = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->bind_param("ddi", $latitude, $longitude, $productId);

        return $stmt->execute();
    }

    /**
     * Delete delivery zone
     */
    public function deleteDeliveryZone($zoneId, $vendorId)
    {
        $stmt = $this->conn->prepare("
            DELETE FROM geofences 
            WHERE id = ? AND vendor_id = ?
        ");
        $stmt->bind_param("ii", $zoneId, $vendorId);

        return $stmt->execute();
    }

    /**
     * Get delivery zones for a specific location
     */
    public function getDeliveryZonesForLocation($latitude, $longitude)
    {
        $zones = [];

        // Get all vendors with delivery zones
        $stmt = $this->conn->prepare("
            SELECT DISTINCT vendor_id FROM geofences
        ");
        $stmt->execute();
        $vendors = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach ($vendors as $vendor) {
            $zoneCheck = $this->isLocationInDeliveryZone($vendor['vendor_id'], $latitude, $longitude);
            if ($zoneCheck['in_zone']) {
                $zones[] = [
                    'vendor_id' => $vendor['vendor_id'],
                    'zone' => $zoneCheck['zone'],
                    'delivery_fee' => $zoneCheck['delivery_fee'],
                    'min_order_amount' => $zoneCheck['min_order_amount']
                ];
            }
        }

        return $zones;
    }
}