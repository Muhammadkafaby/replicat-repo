<!DOCTYPE html>
<html lang="id" class="h-full bg-gradient-to-br from-gray-50 to-gray-100">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Peta Interaktif - Hotspot Vigilance</title>
    <meta name="description" content="Peta interaktif monitoring kebakaran hutan real-time untuk Sumatera Selatan.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        .glass-effect { backdrop-filter: blur(10px); background-color: rgba(255, 255, 255, 0.95); }
        .gradient-border { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); padding: 1px; border-radius: 12px; }
        .gradient-border-content { background: white; border-radius: 11px; }
        .hover-lift { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .hover-lift:hover { transform: translateY(-4px); box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); }
        .pulse-ring { animation: pulse-ring 2s cubic-bezier(0.455, 0.03, 0.515, 0.955) infinite; }
        @keyframes pulse-ring { 0% { transform: scale(0.33); } 40%, 50% { opacity: 0; } 100% { opacity: 0; transform: scale(1.2); } }
        #map { min-height: 500px; height: 60vh; border-radius: 16px; }
    </style>
</head>
<body class="h-full bg-gradient-to-br from-gray-50 to-gray-100"
      x-data="{ sidebarOpen: false }">

<!-- Responsive Sidebar -->
<aside class="fixed inset-y-0 left-0 z-50 flex flex-col bg-white border-r border-gray-200 shadow-lg transition-all duration-300 ease-in-out
    w-64 lg:w-72 xl:w-80
    transform -translate-x-full lg:translate-x-0"
    :class="{ '-translate-x-full': !sidebarOpen, 'translate-x-0': sidebarOpen || window.innerWidth >= 1024 }"
    x-cloak
    @keydown.window.escape="sidebarOpen = false"
    x-data
>
    <!-- Sidebar Header -->
    <div class="flex items-center h-20 px-6 border-b border-gray-100">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg">
                <i class="fas fa-fire text-white text-xl"></i>
            </div>
            <div>
                <h1 class="text-lg font-bold text-gray-900">Hotspot Vigilance</h1>
                <p class="text-xs text-blue-600 font-medium">Monitoring</p>
            </div>
        </div>
        <button class="ml-auto lg:hidden p-2 rounded hover:bg-gray-100 text-gray-500" @click="sidebarOpen = false">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <!-- Sidebar Navigation -->
    <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-2">
        <a href="/dashboard" class="flex items-center px-4 py-3 rounded-lg font-semibold text-sm transition
            text-gray-700 hover:bg-blue-50 hover:text-blue-700">
            <i class="fas fa-home mr-3"></i>
            Dashboard
        </a>
        <a href="/peta" class="flex items-center px-4 py-3 rounded-lg font-semibold text-sm transition
            bg-gradient-to-r from-blue-500 to-blue-600 text-white shadow
            hover:from-blue-600 hover:to-blue-700">
            <i class="fas fa-map-marked-alt mr-3"></i>
            Interactive Maps
        </a>
        <a href="/analitik" class="flex items-center px-4 py-3 rounded-lg font-semibold text-sm transition
            text-gray-700 hover:bg-cyan-50 hover:text-cyan-700">
            <i class="fas fa-chart-line h-4 w-4 me-3"></i>
            Analytics
        </a>
        <a href="/laporan" class="flex items-center px-4 py-3 rounded-lg font-semibold text-sm transition
            text-gray-700 hover:bg-sky-50 hover:text-sky-700">
            <i class="fas fa-file-alt mr-4"></i>
            Reports
        </a>
    </nav>
    <!-- Sidebar User Profile -->
    <div class="border-t border-gray-100 px-6 py-4 flex items-center space-x-3">
        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg">
            <i class="fas fa-user text-white text-sm"></i>
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-gray-900 truncate">{{ Auth::user()->name ?? 'Admin User' }}</p>
            <p class="text-xs text-gray-500 truncate">System Administrator</p>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-gray-400 hover:text-gray-600 p-1 rounded transition-colors duration-200">
                <i class="fas fa-sign-out-alt text-sm"></i>
            </button>
        </form>
    </div>
</aside>

<!-- Sidebar Overlay for Mobile -->
<div x-show="sidebarOpen" x-cloak
    class="fixed inset-0 z-40 bg-black bg-opacity-40 lg:hidden"
    @click="sidebarOpen = false"
    x-transition:enter="transition-opacity ease-linear duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity ease-linear duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0">
</div>

<!-- Main content -->
<div class="transition-all duration-300 ease-in-out lg:ml-72 xl:ml-80">
    <!-- Top navigation bar -->
    <div class="sticky top-0 z-40 flex h-20 shrink-0 items-center gap-x-4 glass-effect border-b border-gray-200/50 px-4 shadow-lg sm:gap-x-6 sm:px-6 lg:px-8">
        <button type="button" @click="sidebarOpen = true" class="-m-2.5 p-2.5 text-gray-700 lg:hidden hover:bg-gray-100 rounded-lg transition-colors duration-200">
            <span class="sr-only">Open sidebar</span>
            <i class="fas fa-bars h-5 w-5"></i>
        </button>
        <div class="h-8 w-px bg-gray-200 lg:hidden"></div>
        <div class="flex flex-1 flex-col md:flex-row md:items-center md:justify-between gap-2 md:gap-0 self-stretch lg:gap-x-6">
            <div class="flex items-center gap-x-4 flex-wrap">
                <nav class="flex" aria-label="Breadcrumb">
                    <ol role="list" class="flex items-center space-x-2 sm:space-x-4">
                        <li>
                            <div class="flex items-center">
                                <i class="fas fa-map-marked-alt h-4 w-4 text-gray-400"></i>
                                <span class="ml-2 text-sm font-medium text-gray-900">Peta Interaktif</span>
                            </div>
                        </li>
                    </ol>
                </nav>
                <div class="flex items-center space-x-2">
                    <div class="flex items-center space-x-1">
                        <div class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></div>
                        <span class="text-xs font-medium text-gray-600">Live Data</span>
                    </div>
                    <span class="text-xs text-gray-500" x-text="'Last update: ' + (window.lastUpdate || '-')"></span>
                </div>
            </div>
            <div class="flex items-center gap-x-2 sm:gap-x-4 mt-2 md:mt-0">
                <!-- Quick Actions -->
                <div class="hidden sm:flex items-center space-x-2">
                    <button type="button" onclick="refreshHotspotData()" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-all duration-200 group">
                        <i class="fas fa-sync-alt h-4 w-4 group-hover:scale-110 transition-transform duration-200"></i>
                    </button>
                </div>
                <!-- Notifications -->
                <div class="relative" x-data="{ open: false }">
                    <button type="button" @click="open = !open"
                            class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-all duration-200 group">
                        <span class="sr-only">View notifications</span>
                        <div class="relative">
                            <i class="fas fa-bell h-5 w-5 group-hover:scale-110 transition-transform duration-200"></i>
                        </div>
                    </button>
                    <!-- Notification dropdown (optional, can be filled if needed) -->
                </div>
                <!-- Profile dropdown -->
                <div class="relative" x-data="{ open: false }">
                    <button type="button" @click="open = !open" class="flex items-center p-1.5 hover:bg-gray-100 rounded-lg transition-all duration-200">
                        <span class="sr-only">Open user menu</span>
                        <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center shadow-lg">
                            <i class="fas fa-user text-white text-sm"></i>
                        </div>
                        <span class="hidden lg:flex lg:items-center ml-3">
                            <span class="text-sm font-semibold leading-6 text-gray-900">{{ Auth::user()->name ?? 'Admin User' }}</span>
                            <i class="fas fa-chevron-down ml-2 h-3 w-3 text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': open }"></i>
                        </span>
                    </button>
                    <div x-show="open" @click.away="open = false"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-xl bg-white py-2 shadow-xl ring-1 ring-gray-900/5">
                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors duration-200">Profile Settings</a>
                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors duration-200">Team Management</a>
                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors duration-200">API Keys</a>
                        <hr class="my-1 border-gray-100">
                        <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors duration-200">
                                    <i class="fas fa-sign-out-alt mr-2"></i>Sign out
                                </button>
                            </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Main Map Section -->
    <main class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-10">
                <div class="gradient-border hover-lift">
                    <div class="gradient-border-content p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Peta Interaktif Hotspot</h3>
                            <button onclick="refreshHotspotData()" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-all duration-200">
                                <i class="fas fa-sync-alt h-4 w-4"></i>
                            </button>
                        </div>
                        <div id="map" class="w-full"></div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
<script>
// Initialize the map when the page loads
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Leaflet map
    var map = L.map('map', {
        zoomControl: false, // We'll add custom controls
        attributionControl: true
    }).setView([-2.5489, 118.0149], 5); // Center on Indonesia

    // Helper: get/set map mode from localStorage
    function getSavedMapMode() {
        return localStorage.getItem('hotspot_map_mode') || 'Dark Theme';
    }
    function setSavedMapMode(mode) {
        localStorage.setItem('hotspot_map_mode', mode);
    }

    // Multiple tile layer options for modern look
    var baseLayers = {
        "Satellite": L.tileLayer('https://{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', {
            maxZoom: 20,
            subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
            attribution: '© Google'
        }),
        "Dark Theme": L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '© CARTO',
            maxZoom: 19
        }),
        "Light Theme": L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }),
        "Terrain": L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenTopoMap contributors',
            maxZoom: 17
        })
    };

    // --- PATCH: Remember last map mode ---
    // Remove all layers first
    Object.values(baseLayers).forEach(layer => { try { map.removeLayer(layer); } catch {} });

    // Get last mode or default
    var lastMode = getSavedMapMode();
    var currentBaseLayer = baseLayers[lastMode] || baseLayers["Dark Theme"];
    currentBaseLayer.addTo(map);

    // Add layer control and listen for changes
    var layerControl = L.control.layers(baseLayers).addTo(map);
    map.on('baselayerchange', function(e) {
        setSavedMapMode(e.name);
    });

    // Add custom zoom control at bottom left
    L.control.zoom({
        position: 'bottomleft'
    }).addTo(map);

    // Add scale control
    L.control.scale({
        position: 'bottomright',
        metric: true,
        imperial: false
    }).addTo(map);

    // Fetch real hotspot data from API
    fetchHotspotData();

    async function fetchHotspotData() {
        try {
            const response = await fetch('https://opsroom.sipongidata.my.id/api/opsroom/indoHotspot?wilayah=IN&filterperiode=false&from=&to=&late=24&satelit[]=NASA-MODIS&satelit[]=NASA-SNPP&satelit[]=NASA-NOAA20&confidence[]=low&confidence[]=medium&confidence[]=high&provinsi=&kabkota=');
            const data = await response.json();
            
            if (data && data.features) {
                addHotspotsToMap(data.features);
                updateActiveAlerts(data.features);
                updateStatistics(data.features);
            }
        } catch (error) {
            console.error('Error fetching hotspot data:', error);
            // Fallback to sample data if API fails
            loadSampleData();
        }
    }

    function addHotspotsToMap(features) {
        // Clear existing hotspots
        hotspotLayerGroup.clearLayers();
        currentHotspots = features;

        features.forEach(function(feature, index) {
            const props = feature.properties;
            const coords = feature.geometry.coordinates;
            
            // Convert coordinates [lng, lat] to [lat, lng] for Leaflet
            const lat = coords[1];
            const lng = coords[0];
            
            const severity = getSeverityFromConfidence(props.confidence_level);
            const color = getColorBySeverity(severity);
            const confidence = parseFloat(props.confidence) || 30;
            
            // Create modern circle marker with dynamic sizing
            const baseRadius = Math.max(confidence * 20, 200);
            
            // Create main hotspot marker
            var circle = L.circle([lat, lng], {
                color: color,
                fillColor: color,
                fillOpacity: 0.7,
                radius: baseRadius,
                weight: 3
            });

            circle.addTo(hotspotLayerGroup);

            // Store original data for search
            circle.hotspotData = {
                index: index,
                properties: props,
                severity: severity,
                color: color,
                confidence: confidence
            };

            // Enhanced popup with modern styling
            circle.bindPopup(`
                <div class="p-5 min-w-80 bg-white rounded-xl shadow-2xl border border-gray-100">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h4 class="font-bold text-xl text-gray-900 mb-1">${props.desa || 'Unknown Location'}</h4>
                            <p class="text-sm text-gray-600">${props.kecamatan}, ${props.kabkota}</p>
                        </div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold shadow-sm" style="background: linear-gradient(135deg, ${color}20, ${color}40); color: ${color}; border: 1px solid ${color}30;">
                            ${severity.toUpperCase()}
                        </span>
                    </div>
                    
                    <div class="space-y-3 text-sm">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <span class="font-semibold text-gray-700 block">Province</span>
                                <p class="text-gray-900 mt-1">${props.nama_provinsi}</p>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <span class="font-semibold text-gray-700 block">Source</span>
                                <p class="text-gray-900 mt-1">${props.sumber}</p>
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <span class="font-semibold text-gray-700 block">Detection Time</span>
                            <p class="text-gray-900 mt-1">${new Date(props.date_hotspot).toLocaleString()}</p>
                        </div>
                        
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <span class="font-semibold text-gray-700 block">Coordinates</span>
                            <p class="text-gray-900 font-mono text-xs mt-1">${lat.toFixed(6)}, ${lng.toFixed(6)}</p>
                        </div>
                        
                        <div class="bg-gradient-to-r from-blue-50 to-blue-100 p-4 rounded-lg border border-blue-200">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-semibold text-blue-900">Confidence Level</span>
                                <span class="text-blue-900 font-bold text-lg">${confidence}%</span>
                            </div>
                            <div class="w-full bg-blue-200 rounded-full h-3 overflow-hidden">
                                <div class="h-3 rounded-full bg-gradient-to-r from-blue-500 to-blue-600" style="width: ${confidence}%;"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-5 pt-4 border-t border-gray-200 flex gap-3">
                        <button onclick="zoomToHotspot(${lat}, ${lng})" class="flex-1 inline-flex justify-center items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-search-plus mr-2"></i>
                            Zoom Here
                        </button>
                        <a href="${props.route_create}" target="_blank" class="flex-1 inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 shadow-md">
                            <i class="fas fa-file-plus mr-2"></i>
                            Create Report
                        </a>
                    </div>
                </div>
            `, {
                maxWidth: 400,
                className: 'modern-popup'
            });

            // Simple hover effects without animations
            circle.on('mouseover', function() {
                this.setStyle({
                    weight: 5,
                    fillOpacity: 0.9
                });
                
                // Show mini tooltip
                var tooltip = L.tooltip({
                    permanent: false,
                    direction: 'top',
                    className: 'modern-tooltip'
                }).setContent(`
                    <div class="bg-black/80 text-white px-3 py-2 rounded-lg text-xs font-medium">
                        <div class="font-semibold">${props.desa || 'Unknown Location'}</div>
                        <div class="text-gray-300">${severity.toUpperCase()} - ${confidence}%</div>
                    </div>
                `);
                this.bindTooltip(tooltip).openTooltip();
            });

            circle.on('mouseout', function() {
                this.setStyle({
                    weight: 3,
                    fillOpacity: 0.7
                });
                this.closeTooltip();
            });

            // Click to center and zoom
            circle.on('click', function() {
                map.setView([lat, lng], Math.max(map.getZoom(), 12));
            });
        });

        updateLoadingState(false);
        updateSearchResultsCount(features.length);
    }

    function getSeverityFromConfidence(confidenceLevel) {
        switch(confidenceLevel) {
            case 'high': return 'critical';
            case 'medium': return 'high';
            case 'low': return 'medium';
            default: return 'low';
        }
    }

    function loadSampleData() {
        // Fallback sample data
        var hotspots = [
            { lat: 0.7893, lng: 113.9213, temp: 85, severity: 'critical', location: 'Riau Forest Sector 7' },
            { lat: -2.2180, lng: 113.9209, temp: 72, severity: 'high', location: 'Central Kalimantan A3' },
            { lat: -6.8885, lng: 107.6440, temp: 58, severity: 'medium', location: 'West Java Highlands' }
        ];

        hotspots.forEach(function(hotspot) {
            var color = getColorBySeverity(hotspot.severity);
            var circle = L.circle([hotspot.lat, hotspot.lng], {
                color: color,
                fillColor: color,
                fillOpacity: 0.7,
                radius: hotspot.temp * 100
            }).addTo(map);

            circle.bindPopup(`
                <div class="p-2">
                    <h4 class="font-bold text-sm">${hotspot.location}</h4>
                    <p class="text-sm">Temperature: ${hotspot.temp}°C</p>
                    <p class="text-sm">Severity: <span class="capitalize font-medium">${hotspot.severity}</span></p>
                </div>
            `);
        });
    }

    function getColorBySeverity(severity) {
        switch(severity) {
            case 'critical': return '#EF4444';
            case 'high': return '#F59E0B';
            case 'medium': return '#EAB308';
            case 'low': return '#22C55E';
            default: return '#6B7280';
        }
    }

    function updateActiveAlerts(features) {
        // Filter recent high-risk hotspots for alerts
        const alerts = features
            .filter(f => f.properties.confidence >= 30)
            .slice(0, 10) // Show only top 10 alerts
            .map(f => {
                const props = f.properties;
                const severity = getSeverityFromConfidence(props.confidence_level);
                return {
                    severity: severity,
                    location: `${props.desa || props.kecamatan}, ${props.kabkota}`,
                    province: props.nama_provinsi,
                    confidence: props.confidence,
                    date: props.date_hotspot,
                    source: props.sumber
                };
            });

        // Log the alerts since panel is removed
        console.log('Active Alerts:', alerts);
    }

    function updateAlertsDisplay(alerts) {
        const alertsContainer = document.querySelector('.lg\\:col-span-1:first-child .space-y-3.max-h-64.overflow-y-auto');
        if (alertsContainer && alerts.length > 0) {
            // Clear existing sample alerts
            alertsContainer.innerHTML = '';
            
            // Add real alerts from API
            alerts.slice(0, 5).forEach(alert => {
                const alertElement = document.createElement('div');
                alertElement.className = `flex items-start space-x-3 p-3 bg-gradient-to-r ${getAlertColorClass(alert.severity)} rounded-xl border ${getAlertBorderClass(alert.severity)}`;
                
                alertElement.innerHTML = `
                    <div class="flex-shrink-0">
                        <div class="w-3 h-3 ${getAlertDotClass(alert.severity)} rounded-full mt-1"></div>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium ${getAlertTextClass(alert.severity)}">${alert.severity.charAt(0).toUpperCase() + alert.severity.slice(1)} Alert</p>
                        <p class="text-sm ${getAlertLocationClass(alert.severity)}">${alert.location}</p>
                        <p class="text-xs ${getAlertDetailClass(alert.severity)} mt-1">Confidence: ${alert.confidence}% | ${new Date(alert.date).toLocaleDateString()}</p>
                    </div>
                `;
                
                alertsContainer.appendChild(alertElement);
            });
        }
    }
    
    function getAlertColorClass(severity) {
        switch(severity) {
            case 'critical': return 'from-red-50 to-red-100/50';
            case 'high': return 'from-orange-50 to-orange-100/50';
            case 'medium': return 'from-yellow-50 to-yellow-100/50';
            default: return 'from-green-50 to-green-100/50';
        }
    }
    
    function getAlertBorderClass(severity) {
        switch(severity) {
            case 'critical': return 'border-red-200/50';
            case 'high': return 'border-orange-200/50';
            case 'medium': return 'border-yellow-200/50';
            default: return 'border-green-200/50';
        }
    }
    
    function getAlertDotClass(severity) {
        switch(severity) {
            case 'critical': return 'bg-red-500';
            case 'high': return 'bg-orange-500';
            case 'medium': return 'bg-yellow-500';
            default: return 'bg-green-500';
        }
    }
    
    function getAlertTextClass(severity) {
        switch(severity) {
            case 'critical': return 'text-red-900';
            case 'high': return 'text-orange-900';
            case 'medium': return 'text-yellow-900';
            default: return 'text-green-900';
        }
    }
    
    function getAlertLocationClass(severity) {
        switch(severity) {
            case 'critical': return 'text-red-700';
            case 'high': return 'text-orange-700';
            case 'medium': return 'text-yellow-700';
            default: return 'text-green-700';
        }
    }
    
    function getAlertDetailClass(severity) {
        switch(severity) {
            case 'critical': return 'text-red-600';
            case 'high': return 'text-orange-600';
            case 'medium': return 'text-yellow-600';
            default: return 'text-green-600';
        }
    }

    function updateStatistics(features) {
        const stats = {
            totalHotspots: features.length,
            criticalHotspots: features.filter(f => f.properties.confidence >= 80).length,
            highRiskHotspots: features.filter(f => f.properties.confidence >= 50 && f.properties.confidence < 80).length,
            mediumRiskHotspots: features.filter(f => f.properties.confidence >= 30 && f.properties.confidence < 50).length,
            lowRiskHotspots: features.filter(f => f.properties.confidence < 30).length
        };

        // Log the statistics since panel is removed
        console.log('Hotspot Statistics:', stats);
    }

    function updateStatsDisplay(stats) {
        // Update the statistics in the panel using specific selectors
        const statsContainer = document.querySelector('.lg\\:col-span-1:last-child .grid.grid-cols-2.gap-3');
        if (statsContainer) {
            const statCards = statsContainer.querySelectorAll('.text-center');
            if (statCards.length >= 4) {
                // Update active hotspots
                statCards[0].querySelector('.text-xl').textContent = stats.totalHotspots;
                
                // Update high risk areas (high + critical)
                const highRiskCount = stats.criticalHotspots + stats.highRiskHotspots;
                statCards[1].querySelector('.text-xl').textContent = highRiskCount;
                
                // Update critical hotspots count in first card if needed
                if (stats.criticalHotspots > 0) {
                    statCards[0].querySelector('.text-xl').textContent = stats.criticalHotspots;
                    statCards[0].querySelector('.text-xs').textContent = 'Critical Hotspots';
                }
            }
        }
    }

    // Global variables for layer management
    let hotspotLayerGroup = L.layerGroup().addTo(map);
    let currentHotspots = [];

    // Modern refresh function with loading animation
    function refreshHotspotData() {
        updateLoadingState(true);
        
        fetchHotspotData();
    }

    function updateLoadingState(isLoading) {
        const statusIndicator = document.querySelector('.absolute.top-6.left-6 .w-3.h-3');
        const statusText = document.querySelector('.absolute.top-6.left-6 .text-sm.font-semibold');
        const lastUpdated = document.querySelector('.absolute.top-6.left-6 .text-xs.text-gray-500');
        
        if (isLoading) {
            if (statusIndicator) {
                statusIndicator.className = 'w-3 h-3 bg-yellow-500 rounded-full shadow-sm';
            }
            if (statusText) {
                statusText.textContent = 'Loading Data...';
            }
            if (lastUpdated) {
                lastUpdated.textContent = 'Refreshing...';
            }
        } else {
            if (statusIndicator) {
                statusIndicator.className = 'w-3 h-3 bg-green-500 rounded-full shadow-sm';
            }
            if (statusText) {
                statusText.textContent = 'Live Monitoring';
            }
            if (lastUpdated) {
                lastUpdated.textContent = `Last updated: ${new Date().toLocaleTimeString()}`;
            }
        }
    }

    // Enhanced search function with real-time feedback
    function searchHotspots(searchTerm) {
        const term = searchTerm.toLowerCase().trim();
        
        // Clear previous search highlights
        hotspotLayerGroup.eachLayer(function(layer) {
            if (layer.hotspotData) {
                const originalColor = layer.hotspotData.color;
                layer.setStyle({ 
                    opacity: 1, 
                    fillOpacity: 0.7,
                    color: originalColor,
                    fillColor: originalColor,
                    weight: 3
                });
            }
        });
        
        if (term === '') {
            updateSearchResultsCount(currentHotspots.length);
            hideSearchResultsPanel();
            return;
        }

        let matchCount = 0;
        let matchedHotspots = [];
        
        hotspotLayerGroup.eachLayer(function(layer) {
            if (layer.hotspotData) {
                const props = layer.hotspotData.properties;
                
                // Enhanced search fields
                const searchableText = [
                    props.desa || '',
                    props.kecamatan || '',
                    props.kabkota || '',
                    props.nama_provinsi || '',
                    props.sumber || '',
                    layer.hotspotData.severity || ''
                ].join(' ').toLowerCase();
                
                if (searchableText.includes(term)) {
                    // Highlight matched hotspots
                    layer.setStyle({ 
                        opacity: 1, 
                        fillOpacity: 0.9,
                        color: '#FFD700',
                        fillColor: layer.hotspotData.color,
                        weight: 5
                    });
                    matchCount++;
                    matchedHotspots.push({
                        layer: layer,
                        data: layer.hotspotData
                    });
                } else {
                    // Dim non-matched hotspots
                    layer.setStyle({ 
                        opacity: 0.3, 
                        fillOpacity: 0.2,
                        weight: 1
                    });
                }
            }
        });

        updateSearchResultsCount(matchCount);
        showSearchResults(matchedHotspots, term);
        
        // Auto-fit bounds to matched results if any
        if (matchCount > 0) {
            const group = new L.featureGroup(matchedHotspots.map(m => m.layer));
            map.fitBounds(group.getBounds(), { 
                padding: [20, 20],
                maxZoom: 10 
            });
        }
    }
    
    function updateSearchResultsCount(count) {
        const searchInput = document.getElementById('search-field');
        if (searchInput) {
            const searchContainer = searchInput.parentElement;
            let countBadge = searchContainer.querySelector('.search-count-badge');
            
            if (!countBadge) {
                countBadge = document.createElement('div');
                countBadge.className = 'search-count-badge absolute right-3 top-1/2 transform -translate-y-1/2 bg-blue-500 text-white text-xs px-2 py-1 rounded-full';
                searchContainer.appendChild(countBadge);
            }
            
            countBadge.textContent = count;
            countBadge.style.display = count > 0 ? 'block' : 'none';
        }
    }
    
    function showSearchResults(matches, term) {
        hideSearchResultsPanel(); // Remove any existing panel
        
        if (matches.length === 0) {
            showNoResultsMessage(term);
            return;
        }
        
        const resultsPanel = document.createElement('div');
        resultsPanel.id = 'search-results-panel';
        resultsPanel.className = 'absolute top-20 left-6 right-6 max-w-md bg-white/95 backdrop-blur-lg rounded-xl shadow-2xl border border-gray-200/50 z-50 max-h-80 overflow-y-auto';
        
        resultsPanel.innerHTML = `
            <div class="p-4 border-b border-gray-200/50">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">Search Results</h3>
                    <button onclick="hideSearchResultsPanel()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <p class="text-sm text-gray-600 mt-1">${matches.length} hotspot(s) found for "${term}"</p>
            </div>
            <div class="p-2">
                ${matches.slice(0, 5).map((match, index) => {
                    const props = match.data.properties;
                    const severity = match.data.severity;
                    const color = match.data.color;
                    
                    return `
                        <div class="p-3 hover:bg-gray-50 rounded-lg cursor-pointer" onclick="focusOnHotspot(${match.layer.getLatLng().lat}, ${match.layer.getLatLng().lng})">
                            <div class="flex items-start space-x-3">
                                <div class="w-3 h-3 rounded-full mt-1.5" style="background-color: ${color};"></div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-900 text-sm">${props.desa || 'Unknown Location'}</p>
                                    <p class="text-xs text-gray-600">${props.kecamatan}, ${props.kabkota}</p>
                                    <div class="flex items-center mt-1 space-x-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium" style="background-color: ${color}20; color: ${color};">
                                            ${severity.toUpperCase()}
                                        </span>
                                        <span class="text-xs text-gray-500">${match.data.confidence}% confidence</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }).join('')}
                ${matches.length > 5 ? `<div class="p-2 text-center text-sm text-gray-500">... and ${matches.length - 5} more results</div>` : ''}
            </div>
        `;
        
        document.querySelector('.flex-1.relative').appendChild(resultsPanel);
    }
    
    function showNoResultsMessage(term) {
        const noResultsPanel = document.createElement('div');
        noResultsPanel.id = 'search-results-panel';
        noResultsPanel.className = 'absolute top-20 left-6 right-6 max-w-md bg-white/95 backdrop-blur-lg rounded-xl shadow-2xl border border-gray-200/50 z-50';
        
        noResultsPanel.innerHTML = `
            <div class="p-6 text-center">
                <div class="mx-auto w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-search text-gray-400 text-xl"></i>
                </div>
                <h3 class="font-semibold text-gray-900 mb-2">No results found</h3>
                <p class="text-sm text-gray-600 mb-4">No hotspots match "${term}". Try a different search term.</p>
                <button onclick="hideSearchResultsPanel()" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                    Clear Search
                </button>
            </div>
        `;
        
        document.querySelector('.flex-1.relative').appendChild(noResultsPanel);
    }
    
    function hideSearchResultsPanel() {
        const existingPanel = document.getElementById('search-results-panel');
        if (existingPanel) {
            existingPanel.remove();
        }
    }
    
    function focusOnHotspot(lat, lng) {
        hideSearchResultsPanel();
        map.setView([lat, lng], 14);
        
        // Find and open popup for this hotspot
        hotspotLayerGroup.eachLayer(function(layer) {
            if (layer.getLatLng && Math.abs(layer.getLatLng().lat - lat) < 0.0001 && Math.abs(layer.getLatLng().lng - lng) < 0.0001) {
                layer.openPopup();
            }
        });
    }
    
    function zoomToHotspot(lat, lng) {
        map.setView([lat, lng], 16);
    }

    // Fullscreen toggle function
    function toggleFullscreen() {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen();
        } else {
            document.exitFullscreen();
        }
    }

    // Auto-refresh every 5 minutes
    setInterval(function() {
        console.log('Auto-refreshing hotspot data...');
        refreshHotspotData();
    }, 5 * 60 * 1000);

    // Close search results when clicking outside
    document.addEventListener('click', function(event) {
        const searchPanel = document.getElementById('search-results-panel');
        const searchField = document.getElementById('search-field');
        
        if (searchPanel && !searchPanel.contains(event.target) && event.target !== searchField) {
            hideSearchResultsPanel();
        }
    });

    // Clear search when escape is pressed
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const searchField = document.getElementById('search-field');
            if (searchField) {
                searchField.value = '';
                searchHotspots('');
            }
            hideSearchResultsPanel();
        }
    });

    // Make functions globally available
    window.refreshHotspotData = refreshHotspotData;
    window.searchHotspots = searchHotspots;
    window.toggleFullscreen = toggleFullscreen;
    window.hideSearchResultsPanel = hideSearchResultsPanel;
    window.focusOnHotspot = focusOnHotspot;
    window.zoomToHotspot = zoomToHotspot;
});
</script>

</body>
</html>