<?php
$pageTitle = 'Delivery Zones';
$currentPage = 'delivery_zones';

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/Geolocation.php';
require_once dirname(__DIR__) . '/includes/Auth.php'; // For session checks

// Vendor session/role check
if (!isset($_SESSION['user_id']) || !$auth->hasRole('vendor')) {
    header('Location: ../auth.php?action=login');
    exit();
}

$db = getDbConnection();
$vendorId = $_SESSION['user_id'];

// Initialize Geolocation service
$geolocationObj = new Geolocation($db);

// Handle zone actions (add, edit, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $zoneId = $_POST['id'] ?? null;

    if ($action === 'save_zone') {
        $name = $_POST['name'] ?? '';
        $radius_km = $_POST['radius_km'] ?? null;
        $polygon_json = $_POST['polygon_json'] ?? null; // Expecting GeoJSON string
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        $zoneData = [
            'name' => $name,
            'polygon' => $polygon_json ? json_decode($polygon_json, true) : null,
            'radius_km' => $radius_km ? floatval($radius_km) : null,
            'delivery_fee' => isset($_POST['delivery_fee']) ? floatval($_POST['delivery_fee']) : 0,
            'min_order_amount' => isset($_POST['min_order_amount']) ? floatval($_POST['min_order_amount']) : 0,
            'is_active' => $isActive
        ];
        $result = $geolocationObj->createDeliveryZone($vendorId, $zoneData);
        if ($result && $result['success']) {
            $_SESSION['success'] = "Delivery zone saved successfully.";
        } else {
            $_SESSION['error'] = "Failed to save delivery zone. Please try again.";
        }
    } elseif ($action === 'delete_zone') {
        if ($geolocationObj->deleteDeliveryZone($zoneId, $vendorId)) {
            $_SESSION['success'] = "Delivery zone deleted successfully.";
        } else {
            $_SESSION['error'] = "Failed to delete delivery zone. Please try again.";
        }
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Get delivery zones for display
$deliveryZones = $geolocationObj->getVendorDeliveryZones($vendorId);

ob_start();
?>

<!-- Delivery Zones Management -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Delivery Zones</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addZoneModal"
                onclick="resetZoneForm()">
                <i class="fas fa-plus"></i> Add Zone
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <!-- Map Container -->
                <div id="map" style="height: 500px; width: 100%;"></div>
            </div>
            <div class="col-md-4">
                <!-- Zones List -->
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($deliveryZones)): ?>
                                <?php foreach ($deliveryZones as $zone): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($zone['zone_name'] ?? $zone['name'] ?? 'Unnamed Zone'); ?></td>
                                        <td><?php echo $zone['polygon_json'] ? 'Polygon' : (isset($zone['radius_km']) ? number_format($zone['radius_km'], 1) . ' km radius' : 'N/A'); ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo ($zone['is_active'] ?? 1) ? 'success' : 'danger'; ?>">
                                                <?php echo $zone['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-info btn-sm"
                                                onclick='editZone(<?php echo json_encode($zone); ?>)'>
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm"
                                                onclick="confirmDeleteZone(<?php echo $zone['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">No delivery zones defined yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Zone Modal -->
<div class="modal fade" id="addZoneModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="zoneForm" method="POST">
                <input type="hidden" name="action" value="save_zone">
                <input type="hidden" name="id" id="zoneId">
                <input type="hidden" name="polygon_json" id="polygon_json">
                <div class="modal-header">
                    <h5 class="modal-title" id="zoneModalTitle">Add Delivery Zone</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <?php echo $_SESSION['success'];
                            unset($_SESSION['success']); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <?php echo $_SESSION['error'];
                            unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>
                    <div class="form-group">
                        <label for="name">Zone Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="radius_km">Delivery Radius (km)</label>
                        <input type="number" class="form-control" id="radius_km" name="radius_km" min="0.1" max="100"
                            step="0.1">
                        <small class="form-text text-muted">Specify a radius or draw a custom polygon.</small>
                    </div>
                    <div class="form-group">
                        <label>Custom Boundary (Draw on map)</label>
                        <div id="drawingMap" style="height: 300px;"></div>
                        <small class="form-text text-muted">Draw a polygon to define a custom delivery boundary. If a
                            polygon is drawn, the radius will be ignored.</small>
                    </div>
                    <div class="form-group">
                        <label for="delivery_fee">Delivery Fee (KSh)</label>
                        <input type="number" class="form-control" id="delivery_fee" name="delivery_fee" min="0" step="1"
                            required>
                    </div>
                    <div class="form-group">
                        <label for="min_order_amount">Minimum Order Amount (KSh)</label>
                        <input type="number" class="form-control" id="min_order_amount" name="min_order_amount" min="0"
                            step="1" required>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active"
                                value="1" checked>
                            <label class="custom-control-label" for="is_active">Active Zone</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Zone</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Zone Modal -->
<div class="modal fade" id="deleteZoneModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="delete_zone">
                <input type="hidden" name="id" id="deleteZoneId">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Delivery Zone</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this delivery zone? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Zone</button>
                </div>
            </form>
        </div>
    </div>
</div>



<script>
    if (typeof L.Control.Draw === 'undefined') {
        alert('Leaflet Draw failed to load. Please check your network and CDN links.');
    }
    // Initialize main map
    const map = L.map('map').setView([-1.2921, 36.8219], 13); // Default to Nairobi
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    // Helper to destroy and recreate the drawing map
    let drawingMap, drawnItems, drawControl;
    function initDrawingMap() {
        // Remove any previous map instance
        if (drawingMap) {
            drawingMap.remove();
            document.getElementById('drawingMap').innerHTML = '';
        }
        drawingMap = L.map('drawingMap').setView([-1.2921, 36.8219], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(drawingMap);
        drawnItems = new L.FeatureGroup();
        drawingMap.addLayer(drawnItems);
        drawControl = new L.Control.Draw({
            draw: {
                polygon: {
                    allowIntersection: false,
                    drawError: {
                        color: '#e1e4e8',
                        timeout: 2500
                    },
                    shapeOptions: { color: '#2196F3' }
                },
                marker: {
                    icon: new L.Icon.Default()
                },
                circle: {
                    shapeOptions: { color: '#2196F3' }
                },
                rectangle: false,
                polyline: false,
                circlemarker: false
            },
            edit: { featureGroup: drawnItems }
        });
        drawingMap.addControl(drawControl);

        // Drawing events
        drawingMap.on('draw:created', function (e) {
            const layer = e.layer;
            drawnItems.clearLayers();
            drawnItems.addLayer(layer);
            // Convert layer to GeoJSON and set to hidden input
            const geoJson = layer.toGeoJSON();
            document.getElementById('polygon_json').value = JSON.stringify(geoJson.geometry);
            // Clear radius input if a polygon or marker is drawn
            document.getElementById('radius_km').value = '';
        });
        drawingMap.on('draw:edited', function (e) {
            const layers = e.layers;
            layers.eachLayer(function (layer) {
                const geoJson = layer.toGeoJSON();
                document.getElementById('polygon_json').value = JSON.stringify(geoJson.geometry);
            });
        });
        drawingMap.on('draw:deleted', function (e) {
            document.getElementById('polygon_json').value = '';
        });
    }

    // Re-initialize drawing map every time modal is shown
    $('#addZoneModal').on('shown.bs.modal', function () {
        setTimeout(function() {
            initDrawingMap();
            setTimeout(function() {
                if (drawingMap) {
                    drawingMap.invalidateSize();
                }
            }, 300); // Ensure map is visible before resizing
        }, 200); // Delay to ensure modal is visible
    });

    // Populate form for editing a zone
    function editZone(zone) {
        document.getElementById('zoneModalTitle').innerText = 'Edit Delivery Zone';
        document.getElementById('zoneId').value = zone.id;
        document.getElementById('name').value = zone.name;
        document.getElementById('is_active').checked = zone.is_active == 1;
        document.getElementById('delivery_fee').value = zone.delivery_fee || '';
        document.getElementById('min_order_amount').value = zone.min_order_amount || '';
        document.getElementById('radius_km').value = zone.radius_km || '';
        document.getElementById('polygon_json').value = zone.polygon_json || '';
        // Map will be reset on modal show
        $('#addZoneModal').modal('show');
    }

    // Reset form for adding a new zone
    function resetZoneForm() {
        document.getElementById('zoneModalTitle').innerText = 'Add Delivery Zone';
        document.getElementById('zoneForm').reset();
        document.getElementById('zoneId').value = '';
        document.getElementById('polygon_json').value = '';
        document.getElementById('is_active').checked = true;
        // Map will be reset on modal show
    }

    // Validate zone form before submit
    document.getElementById('zoneForm').addEventListener('submit', function(e) {
        const polygonJson = document.getElementById('polygon_json').value.trim();
        const radiusKm = document.getElementById('radius_km').value.trim();
        if (!polygonJson && (!radiusKm || isNaN(radiusKm) || Number(radiusKm) <= 0)) {
            e.preventDefault();
            alert('Please draw a polygon on the map or enter a valid delivery radius.');
            return false;
        }
    });

    // Confirm delete zone
    function confirmDeleteZone(zoneId) {
        document.getElementById('deleteZoneId').value = zoneId;
        $('#deleteZoneModal').modal('show');
    }

    // Load existing zones on the main map (unchanged)
    const zonesData = <?php echo json_encode($deliveryZones); ?>;
    zonesData.forEach(zone => {
        if (zone.polygon_json) {
            const geoJson = JSON.parse(zone.polygon_json);
            L.geoJSON(geoJson, {
                style: {
                    color: zone.is_active ? '#28a745' : '#dc3545',
                    weight: 3,
                    opacity: 0.5,
                    fillColor: zone.is_active ? '#28a745' : '#dc3545',
                    fillOpacity: 0.2
                }
            }).addTo(map).bindPopup(zone.name + (zone.is_active ? ' (Active)' : ' (Inactive)'));
        }
    });
    if (zonesData.length > 0) {
        const firstPolygonZone = zonesData.find(zone => zone.polygon_json);
        if (firstPolygonZone) {
            const geoJson = JSON.parse(firstPolygonZone.polygon_json);
            const layer = L.geoJSON(geoJson);
            map.fitBounds(layer.getBounds());
        }
    }
</script>
<?php
$content = ob_get_clean();
include 'layout.php';
?>

<!-- Move script to end of body to ensure DOM is loaded -->
<script>
// Wait for DOM and all resources to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    if (typeof L === 'undefined' || typeof L.Control.Draw === 'undefined') {
        console.error('Leaflet or Leaflet Draw is not loaded. Check console for errors.');
        return;
    }

    // Initialize main map if container exists
    const mapElement = document.getElementById('map');
    if (mapElement) {
        const map = L.map('map').setView([-1.2921, 36.8219], 13); // Default to Nairobi
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);

        // Add existing zones to the map
        <?php if (!empty($deliveryZones)): ?>
            <?php foreach ($deliveryZones as $zone): ?>
                <?php if (!empty($zone['radius_km'])): ?>
                    // Add radius zone
                    try {
                        const center = [-1.2921, 36.8219]; // Default to Nairobi center
                        const radiusMeters = <?php echo $zone['radius_km']; ?> * 1000;
                        
                        const circle = L.circle(center, {
                            color: '<?php echo ($zone['is_active'] ?? 1) ? '#28a745' : '#dc3545'; ?>',
                            fillColor: '<?php echo ($zone['is_active'] ?? 1) ? '#28a745' : '#dc3545'; ?>',
                            fillOpacity: 0.2,
                            radius: radiusMeters
                        }).addTo(map);
                        
                        // Add popup with zone information
                        circle.bindPopup('<div><h6><?php echo htmlspecialchars($zone['zone_name'] ?? 'Unnamed Zone'); ?></h6><p>Type: <?php echo number_format($zone['radius_km'], 1); ?> km radius</p><p>Status: <?php echo ($zone['is_active'] ?? 1) ? 'Active' : 'Inactive'; ?></p><p>Fee: KSh <?php echo number_format($zone['delivery_fee'] ?? 0, 2); ?></p></div>');
                        
                    } catch (e) {
                        console.error('Error adding radius zone to map:', e);
                    }
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    } else {
        console.error('Map container not found');
    }

    // Initialize drawing map in modal
    let drawingMap, drawnItems, drawControl;
    
    function initDrawingMap() {
        const drawingMapElement = document.getElementById('drawingMap');
        if (!drawingMapElement) return;
        
        // Remove existing map if it exists
        if (drawingMap) {
            drawingMap.remove();
            drawingMapElement.innerHTML = '';
        }
        
        // Create new map instance
        drawingMap = L.map('drawingMap').setView([-1.2921, 36.8219], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(drawingMap);
        
        // Initialize feature group to store drawn items
        drawnItems = new L.FeatureGroup();
        drawingMap.addLayer(drawnItems);
        
        // Initialize the draw control and pass it the FeatureGroup of editable layers
        drawControl = new L.Control.Draw({
            edit: {
                featureGroup: drawnItems
            },
            draw: {
                polygon: true,
                polyline: false,
                rectangle: false,
                circle: false,
                marker: false,
                circlemarker: false
            }
        });
        
        drawingMap.addControl(drawControl);
        
        // Handle draw events
        drawingMap.on(L.Draw.Event.CREATED, function (e) {
            const layer = e.layer;
            drawnItems.addLayer(layer);
            
            // Update hidden field with GeoJSON
            const shape = layer.toGeoJSON();
            document.getElementById('polygon_json').value = JSON.stringify(shape);
            
            // Disable radius field when drawing a polygon
            document.getElementById('radius_km').disabled = true;
        });
        
        // Handle delete event
        drawingMap.on(L.Draw.Event.DELETED, function() {
            document.getElementById('polygon_json').value = '';
            document.getElementById('radius_km').disabled = false;
        });
        
        // Fit map to drawing area
        drawingMap.invalidateSize();
    }
    
    // Initialize drawing map when modal is shown
    $('#addZoneModal').on('shown.bs.modal', function () {
        initDrawingMap();
    });
    
    // Reset form when modal is hidden
    $('#addZoneModal').on('hidden.bs.modal', function () {
        if (drawingMap) {
            drawingMap.remove();
            drawingMap = null;
        }
        resetZoneForm();
    });
    
    // Handle radius input change
    document.getElementById('radius_km')?.addEventListener('input', function() {
        if (this.value) {
            document.getElementById('polygon_json').value = '';
            if (drawnItems) {
                drawnItems.clearLayers();
            }
        }
    });
    
    // Form validation before submission
    document.getElementById('zoneForm')?.addEventListener('submit', function(e) {
        const polygonJson = document.getElementById('polygon_json').value;
        const radiusKm = document.getElementById('radius_km').value;
        
        if (!polygonJson && !radiusKm) {
            e.preventDefault();
            alert('Please draw a delivery area or enter a radius.');
            return false;
        }
        return true;
    });
    
    // Reset zone form
    window.resetZoneForm = function() {
        document.getElementById('zoneForm').reset();
        document.getElementById('polygon_json').value = '';
        document.getElementById('radius_km').disabled = false;
        if (drawnItems) {
            drawnItems.clearLayers();
        }
    };
    
    // Confirm delete zone
    window.confirmDeleteZone = function(zoneId) {
        document.getElementById('deleteZoneId').value = zoneId;
        $('#deleteZoneModal').modal('show');
    };
});
</script>

<?php
$content = ob_get_clean();
require_once 'layout.php';
?>