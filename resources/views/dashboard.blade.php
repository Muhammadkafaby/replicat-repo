<!DOCTYPE html>
<html lang="id" class="h-full bg-gradient-to-br from-gray-50 to-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard - Hotspot Vigilance</title>
    <meta name="description" content="Dashboard monitoring kebakaran hutan real-time untuk Sumatera Selatan.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
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
    </style>
</head>
<body class="h-full bg-gradient-to-br from-gray-50 to-gray-100" 
      x-data="{
          sidebarOpen: false,
          sidebarCollapsed: false,
          refreshing: false,
          fetchingNASA: false,
          stats: {
              activeHotspots: 0,
              alertsToday: 0,
              coverage: 85,
              responseTime: 15
          },
          hotspots: [],
          notifications: [],
          realtimeStatus: 'connecting',
          lastUpdate: null,
          
          // Map functionality
          map: null,
          mapInitialized: false,
          hotspotMarkers: [],
          
          // Indonesian Hotspot API Configuration
          indoHotspotConfig: {
              API_URL: 'https://opsroom.sipongidata.my.id/api/opsroom/indoHotspot',
              wilayah: 'IN',
              filterperiode: false,
              from: '',
              to: '',
              late: 24,
              satelit: ['NASA-MODIS', 'NASA-SNPP', 'NASA-NOAA20'],
              confidence: ['low', 'medium', 'high'],
              provinsi: '',
              kabkota: ''
          },
          
          // Initialize dashboard
          init() {
              console.log('Initializing dashboard with Indonesian Hotspot API...');
              this.loadIndoHotspotData();
              this.loadNotifications();
              setInterval(() => this.loadIndoHotspotData(), 300000); // Refresh every 5 minutes
              // Remove notification auto-refresh since we generate them from hotspot data
          },
          
          // Load Indonesian Hotspot data
          async loadIndoHotspotData() {
              this.refreshing = true;
              console.log('Starting to load Indonesian Hotspot data...');
              
              try {
                  // Build query parameters for Indonesian Hotspot API
                  const params = new URLSearchParams({
                      wilayah: this.indoHotspotConfig.wilayah,
                      filterperiode: this.indoHotspotConfig.filterperiode,
                      from: this.indoHotspotConfig.from,
                      to: this.indoHotspotConfig.to,
                      late: this.indoHotspotConfig.late,
                      provinsi: this.indoHotspotConfig.provinsi,
                      kabkota: this.indoHotspotConfig.kabkota
                  });
                  
                  // Add multiple satelit and confidence parameters
                  this.indoHotspotConfig.satelit.forEach(sat => {
                      params.append('satelit[]', sat);
                  });
                  
                  this.indoHotspotConfig.confidence.forEach(conf => {
                      params.append('confidence[]', conf);
                  });
                  
                  const apiUrl = `${this.indoHotspotConfig.API_URL}?${params.toString()}`;
                  console.log('Fetching from API URL:', apiUrl);
                  
                  // Try to fetch with CORS enabled, fallback to no-cors if needed
                  let response;
                  try {
                      response = await fetch(apiUrl, {
                          method: 'GET',
                          headers: {
                              'Accept': 'application/json',
                          },
                          mode: 'cors'
                      });
                  } catch (corsError) {
                      console.warn('CORS error, trying with no-cors mode:', corsError);
                      response = await fetch(apiUrl, {
                          method: 'GET',
                          mode: 'no-cors'
                      });
                  }
                  
                  console.log('API Response status:', response.status);
                  console.log('API Response type:', response.type);
                  
                  if (response.type === 'opaque') {
                      // No-cors mode - we can't read the response
                      console.warn('API returned opaque response (no-cors mode). Using fallback data.');
                      throw new Error('CORS blocked - cannot read API response');
                  }
                  
                  if (!response.ok) {
                      throw new Error(`HTTP error! status: ${response.status}`);
                  }
                  
                  const result = await response.json();
                  console.log('API Response data:', result);
                  
                  if (result && (result.features || result.data)) {
                      // Handle GeoJSON format or direct data array
                      const dataToProcess = result.features || result.data || result;
                      this.hotspots = this.processIndoHotspotData(dataToProcess);
                      this.updateStatsFromIndoData(dataToProcess);
                      this.realtimeStatus = 'connected';
                      this.lastUpdate = new Date().toLocaleTimeString('id-ID');
                      console.log(`Successfully loaded ${this.hotspots.length} hotspots from Indonesian Hotspot API`);
                      
                      // Generate notifications from new data
                      this.generateNotificationsFromData(this.hotspots);
                  } else {
                      console.warn('No data found in API response:', result);
                      throw new Error('No data found in API response');
                  }
              } catch (error) {
                  console.error('Error loading Indonesian Hotspot data:', error);
                  this.realtimeStatus = 'error';
                  // Only load fallback data if API completely fails
                  console.log('Loading fallback data due to API error...');
                  await this.loadFallbackData();
              } finally {
                  this.refreshing = false;
              }
          },
          
          // Process Indonesian Hotspot API data into standard format
          processIndoHotspotData(data) {
              console.log('Processing Indonesian hotspot data:', data);
              
              // Handle GeoJSON format
              let hotspotArray = data;
              
              // If data is GeoJSON features array
              if (Array.isArray(data) && data.length > 0 && data[0].type === 'Feature') {
                  console.log('Detected GeoJSON format with features');
                  hotspotArray = data;
              }
              // If data is wrapped in another object
              else if (data && typeof data === 'object' && !Array.isArray(data)) {
                  if (data.features) hotspotArray = data.features;
                  else if (data.data) hotspotArray = data.data;
                  else if (data.result) hotspotArray = data.result;
              }
              
              if (!Array.isArray(hotspotArray)) {
                  console.warn('Expected array data, received:', typeof hotspotArray, hotspotArray);
                  return [];
              }
              
              console.log(`Processing ${hotspotArray.length} hotspot records...`);
              
              return hotspotArray.map((item, index) => {
                  // Handle GeoJSON feature format
                  let properties = item;
                  let coordinates = [0, 0];
                  
                  if (item.type === 'Feature') {
                      properties = item.properties || {};
                      coordinates = item.geometry?.coordinates || [0, 0];
                  }
                  
                  // Extract data from properties (GeoJSON format)
                  const confidence = this.parseConfidenceValue(properties.confidence || properties.confidence_level || 0);
                  const brightness = parseFloat(properties.brightness || properties.bright_ti4 || properties.bright_ti5 || 0);
                  const frp = parseFloat(properties.frp || properties.fire_radiative_power || 0);
                  
                  // Coordinates from GeoJSON geometry or fallback to properties
                  const latitude = coordinates[1] || parseFloat(properties.lat || properties.latitude || 0);
                  const longitude = coordinates[0] || parseFloat(properties.long || properties.longitude || 0);
                  
                  // Handle date/time from Indonesian API format
                  let acqDate = properties.date_hotspot_ori || properties.date_hotspot || properties.acq_date;
                  let acqTime = '';
                  
                  if (acqDate) {
                      try {
                          const dateObj = new Date(acqDate);
                          acqDate = dateObj.toISOString().slice(0, 10);
                          acqTime = dateObj.toTimeString().slice(0, 8);
                      } catch (e) {
                          acqDate = new Date().toISOString().slice(0, 10);
                          acqTime = new Date().toTimeString().slice(0, 8);
                      }
                  } else {
                      acqDate = new Date().toISOString().slice(0, 10);
                      acqTime = new Date().toTimeString().slice(0, 8);
                  }
                  
                  const processedItem = {
                      id: properties.hs_id || properties.id || index + 1,
                      latitude: latitude,
                      longitude: longitude,
                      brightness: brightness || 300, // Default if not available
                      confidence: confidence,
                      frp: frp || 0,
                      acq_date: acqDate,
                      acq_time: acqTime,
                      satellite: properties.sumber || properties.satelit || properties.satellite || 'Unknown',
                      instrument: 'MODIS', // Default for Indonesian API
                      severity: this.calculateSeverity(confidence, brightness || 300, frp || 0),
                      is_active: this.isHotspotActive({ acq_date: acqDate }),
                      provinsi: properties.nama_provinsi || properties.provinsi || '',
                      kabupaten: properties.kabkota || properties.kabupaten || '',
                      kecamatan: properties.kecamatan || '',
                      desa: properties.desa || '',
                      kawasan: properties.kawasan || '',
                      confidence_level: properties.confidence_level || ''
                  };
                  
                  console.log(`Processed hotspot ${index + 1}:`, processedItem);
                  return processedItem;
              });
          },
          
          // Parse confidence value from various formats
          parseConfidenceValue(confidence) {
              if (typeof confidence === 'string') {
                  const lowerConf = confidence.toLowerCase();
                  if (lowerConf === 'high' || lowerConf === 'tinggi') return 85;
                  if (lowerConf === 'medium' || lowerConf === 'sedang') return 65;
                  if (lowerConf === 'low' || lowerConf === 'rendah') return 45;
                  return parseFloat(confidence) || 50;
              }
              return parseFloat(confidence) || 50;
          },
          
          // Calculate severity based on confidence, brightness, and FRP
          calculateSeverity(confidence, brightness, frp) {
              const confScore = confidence >= 80 ? 3 : confidence >= 60 ? 2 : 1;
              const brightScore = brightness >= 350 ? 3 : brightness >= 320 ? 2 : 1;
              const frpScore = frp >= 50 ? 3 : frp >= 20 ? 2 : 1;
              
              const totalScore = confScore + brightScore + frpScore;
              
              if (totalScore >= 8) return 'high';
              if (totalScore >= 5) return 'medium';
              return 'low';
          },
          
          // Check if hotspot is currently active
          isHotspotActive(item) {
              try {
                  const acqDate = new Date(item.acq_date || item.tanggal || item.date);
                  const now = new Date();
                  const diffHours = (now - acqDate) / (1000 * 60 * 60);
                  return diffHours <= 24; // Consider active if detected within last 24 hours
              } catch (error) {
                  console.warn('Error parsing date for hotspot activity:', error);
                  return true; // Default to active if date parsing fails
              }
          },
          
          // Update statistics from Indonesian API data
          updateStatsFromIndoData(data) {
              if (!Array.isArray(data)) {
                  this.stats.activeHotspots = 0;
                  this.stats.alertsToday = 0;
                  return;
              }
              
              const processedData = this.processIndoHotspotData(data);
              this.stats.activeHotspots = processedData.filter(h => h.is_active).length;
              this.stats.alertsToday = processedData.filter(h => h.severity === 'high').length;
              // Coverage and response time remain static
              this.stats.coverage = 85;
              this.stats.responseTime = 15;
          },
          
          // Generate notifications from hotspot data
          generateNotificationsFromData(hotspotsData) {
              const notifications = [];
              const highSeverityHotspots = hotspotsData.filter(h => h.severity === 'high' && h.is_active);
              const mediumSeverityHotspots = hotspotsData.filter(h => h.severity === 'medium' && h.is_active);
              
              // High severity notifications
              if (highSeverityHotspots.length > 0) {
                  const latestHotspot = highSeverityHotspots[0];
                  notifications.push({
                      id: 1,
                      message: `High severity hotspot detected in ${latestHotspot.provinsi || 'Indonesia'}${latestHotspot.kabupaten ? ', ' + latestHotspot.kabupaten : ''} (Confidence: ${latestHotspot.confidence}%)`,
                      time: new Date().toLocaleTimeString('id-ID'),
                      severity: 'high',
                      type: 'hotspot'
                  });
              }
              
              // System update notification
              notifications.push({
                  id: 2,
                  message: `Indonesian Hotspot API updated - ${hotspotsData.length} hotspots loaded from ${this.getSatelliteTypes().join(', ')}`,
                  time: new Date().toLocaleTimeString('id-ID'),
                  severity: 'low',
                  type: 'system'
              });
              
              // Medium severity cluster notification
              if (mediumSeverityHotspots.length > 3) {
                  notifications.push({
                      id: 3,
                      message: `${mediumSeverityHotspots.length} medium confidence hotspots detected across ${this.getProvinceCount()} provinces - monitoring required`,
                      time: new Date().toLocaleTimeString('id-ID'),
                      severity: 'medium',
                      type: 'hotspot'
                  });
              }
              
              // Coverage notification
              if (this.getProvinceCount() > 0) {
                  notifications.push({
                      id: 4,
                      message: `Monitoring coverage active in ${this.getProvinceCount()} Indonesian provinces with average confidence ${this.calculateAverageConfidence()}%`,
                      time: new Date().toLocaleTimeString('id-ID'),
                      severity: 'low',
                      type: 'system'
                  });
              }
              
              this.notifications = notifications;
          },

          // Load fallback data when API fails
          async loadFallbackData() {
              console.log('Loading fallback data...');
              this.hotspots = this.generateMockHotspots(5); // Minimal fallback data
              this.updateStats();
              this.realtimeStatus = 'error';
          },

          // Load notifications from Indonesian hotspot data
          async loadNotifications() {
              // Don't fetch from backend, use notifications generated from hotspot data
              // This will be populated by generateNotificationsFromData() when hotspot data loads
              if (this.notifications.length === 0) {
                  this.generateMockNotifications(); // Only as initial fallback
              }
          },
          
          // Generate mock hotspot data with realistic coordinates
          generateMockHotspots(count) {
              const mockHotspots = [];
              const sumatraCoords = {
                  lat: { min: -5.5, max: 3.5 },
                  lng: { min: 95.0, max: 109.0 }
              };
              
              for (let i = 0; i < count; i++) {
                  const lat = (Math.random() * (sumatraCoords.lat.max - sumatraCoords.lat.min) + sumatraCoords.lat.min).toFixed(6);
                  const lng = (Math.random() * (sumatraCoords.lng.max - sumatraCoords.lng.min) + sumatraCoords.lng.min).toFixed(6);
                  const brightness = Math.floor(Math.random() * 100) + 300;
                  const confidence = Math.floor(Math.random() * 40) + 60;
                  const frp = (Math.random() * 50 + 5).toFixed(1);
                  
                  mockHotspots.push({
                      id: i + 1,
                      latitude: parseFloat(lat),
                      longitude: parseFloat(lng),
                      brightness: brightness,
                      confidence: confidence,
                      frp: parseFloat(frp),
                      acq_date: new Date().toISOString().slice(0, 10),
                      acq_time: new Date().toTimeString().slice(0, 8),
                      satellite: ['Terra', 'Aqua', 'NPP'][Math.floor(Math.random() * 3)],
                      instrument: 'MODIS',
                      severity: confidence >= 80 ? 'high' : confidence >= 60 ? 'medium' : 'low',
                      is_active: Math.random() > 0.2
                  });
              }
              return mockHotspots;
          },
          
          // Update statistics based on hotspot data
          updateStats() {
              this.stats.activeHotspots = this.hotspots.filter(h => h.is_active).length;
              this.stats.alertsToday = this.hotspots.filter(h => h.severity === 'high').length;
              // Coverage and response time remain static for demo
          },
          
          // Generate mock notifications
          generateMockNotifications() {
              this.notifications = [
                  {
                      id: 1,
                      message: 'New high confidence hotspot detected in Riau',
                      time: new Date().toLocaleTimeString('id-ID'),
                      severity: 'high',
                      type: 'hotspot'
                  },
                  {
                      id: 2,
                      message: 'Indonesian Hotspot data successfully updated',
                      time: new Date(Date.now() - 300000).toLocaleTimeString('id-ID'),
                      severity: 'low',
                      type: 'system'
                  },
                  {
                      id: 3,
                      message: 'Medium confidence hotspot cluster detected',
                      time: new Date(Date.now() - 600000).toLocaleTimeString('id-ID'),
                      severity: 'medium',
                      type: 'hotspot'
                  }
              ];
          },
          
          // Load demo data as fallback (deprecated - use loadFallbackData instead)
          loadDemoData() {
              this.loadFallbackData();
          },
          
          // Force refresh Indonesian Hotspot data
          async refreshNASAData() {
              this.fetchingNASA = true;
              await this.loadIndoHotspotData();
              await this.loadNotifications();
              this.fetchingNASA = false;
          },
          
          // Manual refresh
          async refreshData() {
              await this.loadIndoHotspotData();
              await this.loadNotifications();
          },
          
          // Get severity badge color
          getSeverityColor(severity) {
              switch(severity) {
                  case 'high': return 'bg-red-100 text-red-800';
                  case 'medium': return 'bg-yellow-100 text-yellow-800';
                  case 'low': return 'bg-green-100 text-green-800';
                  default: return 'bg-gray-100 text-gray-800';
              }
          },
          
          // Get hotspot details for display
          getHotspotDetails(hotspot) {
              return {
                  coordinates: `${hotspot.latitude.toFixed(4)}, ${hotspot.longitude.toFixed(4)}`,
                  timeAgo: this.getTimeAgo(hotspot.acq_date, hotspot.acq_time),
                  confidenceLevel: this.getConfidenceLevel(hotspot.confidence),
                  satelliteInfo: `${hotspot.satellite} (${hotspot.instrument})`
              };
          },
          
          // Calculate time ago from acquisition date/time
          getTimeAgo(date, time) {
              try {
                  const acqDateTime = new Date(`${date}T${time}`);
                  const now = new Date();
                  const diffMs = now - acqDateTime;
                  const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
                  const diffMins = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
                  
                  if (diffHours > 24) {
                      const diffDays = Math.floor(diffHours / 24);
                      return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
                  } else if (diffHours > 0) {
                      return `${diffHours}h ${diffMins}m ago`;
                  } else {
                      return `${diffMins}m ago`;
                  }
              } catch (e) {
                  return 'Recently';
              }
          },
          
          // Get confidence level description
          getConfidenceLevel(confidence) {
              if (confidence >= 80) return 'Very High';
              if (confidence >= 60) return 'High';
              if (confidence >= 40) return 'Medium';
              return 'Low';
          },

          // Export hotspot data
          exportData() {
              if (this.hotspots.length === 0) {
                  alert('No data to export');
                  return;
              }
              
              const csvContent = this.convertToCSV(this.hotspots);
              const blob = new Blob([csvContent], { type: 'text/csv' });
              const url = window.URL.createObjectURL(blob);
              const a = document.createElement('a');
              a.href = url;
              a.download = `indo_hotspots_${new Date().toISOString().slice(0, 10)}.csv`;
              a.click();
              window.URL.revokeObjectURL(url);
          },
          
          // Convert hotspots to CSV format
          convertToCSV(data) {
              const headers = ['ID', 'Latitude', 'Longitude', 'Brightness', 'Confidence', 'FRP', 'Date', 'Time', 'Satellite', 'Severity', 'Provinsi', 'Kabupaten', 'Kecamatan', 'Desa'];
              const csvRows = [headers.join(',')];
              
              data.forEach(hotspot => {
                  const row = [
                      hotspot.id,
                      hotspot.latitude,
                      hotspot.longitude,
                      hotspot.brightness || 'N/A',
                      hotspot.confidence,
                      hotspot.frp || 'N/A',
                      hotspot.acq_date,
                      hotspot.acq_time,
                      hotspot.satellite || 'Unknown',
                      hotspot.severity,
                      hotspot.provinsi || 'N/A',
                      hotspot.kabupaten || 'N/A',
                      hotspot.kecamatan || 'N/A',
                      hotspot.desa || 'N/A'
                  ];
                  csvRows.push(row.join(','));
              });
              
              return csvRows.join('\n');
          },

          // Format coordinates for display
          formatCoordinates(lat, lng) {
              return `${parseFloat(lat).toFixed(4)}, ${parseFloat(lng).toFixed(4)}`;
          },

          // Calculate risk analysis
          calculateRisk() {
              const totalHotspots = this.hotspots.length;
              const highSeverity = this.hotspots.filter(h => h.severity === 'high').length;
              const mediumSeverity = this.hotspots.filter(h => h.severity === 'medium').length;
              
              return {
                  high: highSeverity,
                  medium: mediumSeverity,
                  low: totalHotspots - highSeverity - mediumSeverity
              };
          },

          // Calculate average confidence
          calculateAverageConfidence() {
              if (this.hotspots.length === 0) return 0;
              const totalConfidence = this.hotspots.reduce((sum, h) => sum + parseFloat(h.confidence || 0), 0);
              return Math.round(totalConfidence / this.hotspots.length);
          },

          // Calculate average brightness
          calculateAverageBrightness() {
              if (this.hotspots.length === 0) return 0;
              const totalBrightness = this.hotspots.reduce((sum, h) => sum + parseFloat(h.brightness || 0), 0);
              return Math.round(totalBrightness / this.hotspots.length);
          },

          // Get unique satellite types
          getSatelliteTypes() {
              const satellites = [...new Set(this.hotspots.map(h => h.satellite).filter(s => s && s !== 'Unknown'))];
              return satellites.length > 0 ? satellites : ['No Data'];
          },

          // Get province count
          getProvinceCount() {
              const provinces = [...new Set(this.hotspots.map(h => h.provinsi).filter(p => p && p.trim() !== ''))];
              return provinces.length || 0;
          },
      }">

    <!-- Dashboard Sidebar -->
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
                bg-gradient-to-r from-blue-500 to-blue-600 text-white shadow
                hover:from-blue-600 hover:to-blue-700">
                <i class="fas fa-home mr-3"></i>
                Dashboard
            </a>
            <a href="/peta" class="flex items-center px-4 py-3 rounded-lg font-semibold text-sm transition
                text-gray-700 hover:bg-blue-50 hover:text-blue-700">
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
        <!-- Topbar -->
        <div class="sticky top-0 z-40 flex h-20 shrink-0 items-center gap-x-4 glass-effect border-b border-gray-200/50 px-4 shadow-lg sm:gap-x-6 sm:px-6 lg:px-8">
            <button type="button" @click="sidebarOpen = true" class="-m-2.5 p-2.5 text-gray-700 lg:hidden hover:bg-gray-100 rounded-lg transition-colors duration-200">
                <span class="sr-only">Open sidebar</span>
                <i class="fas fa-bars h-5 w-5"></i>
            </button>
            
            <!-- Separator -->
            <div class="h-8 w-px bg-gray-200 lg:hidden"></div>
            
            <!-- Enhanced Breadcrumb & Status -->
            <div class="flex flex-1 flex-col md:flex-row md:items-center md:justify-between gap-2 md:gap-0 self-stretch lg:gap-x-6">
                <div class="flex items-center gap-x-4 flex-wrap">
                    <nav class="flex" aria-label="Breadcrumb">
                        <ol role="list" class="flex items-center space-x-2 sm:space-x-4">
                            <li>
                                <div class="flex items-center">
                                    <i class="fas fa-home h-4 w-4 text-gray-400"></i>
                                    <span class="ml-2 text-sm font-medium text-gray-900">Dashboard</span>
                                </div>
                            </li>
                        </ol>
                    </nav>
                    <!-- Real-time Status Indicator -->
                    <div class="flex items-center space-x-2">
                        <div class="flex items-center space-x-1">
                            <div class="w-2 h-2 rounded-full"
                                 :class="{
                                     'bg-green-400 animate-pulse': realtimeStatus === 'connected',
                                     'bg-yellow-400 animate-pulse': realtimeStatus === 'connecting',
                                     'bg-red-400': realtimeStatus === 'error',
                                     'bg-gray-400': realtimeStatus === 'disconnected'
                                 }"></div>
                            <span class="text-xs font-medium text-gray-600"
                                  x-text="realtimeStatus === 'connected' ? 'Live Data' :
                                          realtimeStatus === 'connecting' ? 'Connecting...' :
                                          realtimeStatus === 'error' ? 'Connection Error' : 'Offline'"></span>
                        </div>
                        <span class="text-xs text-gray-500" x-show="lastUpdate" x-text="'Last update: ' + lastUpdate"></span>
                    </div>
                </div>
                <div class="flex items-center gap-x-2 sm:gap-x-4 mt-2 md:mt-0">
                    <!-- Quick Actions -->
                    <div class="hidden sm:flex items-center space-x-2">
                        <button type="button" @click="refreshData()"
                                class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-all duration-200 group"
                                :class="{ 'animate-spin': refreshing }">
                            <i class="fas fa-sync-alt h-4 w-4 group-hover:scale-110 transition-transform duration-200"></i>
                        </button>
                        <button type="button" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-all duration-200 group">
                            <i class="fas fa-download h-4 w-4 group-hover:scale-110 transition-transform duration-200"></i>
                        </button>
                    </div>
                    <!-- Enhanced Notifications -->
                    <div class="relative" x-data="{ open: false }">
                        <button type="button" @click="open = !open"
                                class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-all duration-200 group">
                            <span class="sr-only">View notifications</span>
                            <div class="relative">
                                <i class="fas fa-bell h-5 w-5 group-hover:scale-110 transition-transform duration-200"></i>
                                <span x-show="notifications.length > 0"
                                      class="absolute -top-1 -right-1 h-4 w-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center animate-pulse"
                                      x-text="notifications.length"></span>
                            </div>
                        </button>
                        <!-- Notification dropdown -->
                        <div x-show="open" @click.away="open = false" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="absolute right-0 z-50 mt-2 w-80 origin-top-right rounded-xl bg-white py-3 shadow-xl ring-1 ring-gray-900/5">
                            <div class="px-4 py-2 border-b border-gray-100">
                                <h3 class="text-sm font-semibold text-gray-900">Notifications</h3>
                            </div>
                            <div class="max-h-96 overflow-y-auto">
                                <template x-for="notification in notifications.slice(0, 5)" :key="notification.id">
                                    <div class="px-4 py-3 hover:bg-gray-50 transition-colors duration-200">
                                        <div class="flex items-start space-x-3">
                                            <div class="flex-shrink-0 mt-0.5">
                                                <div class="w-2 h-2 rounded-full" 
                                                     :class="{
                                                         'bg-red-500': notification.severity === 'high',
                                                         'bg-yellow-500': notification.severity === 'medium',
                                                         'bg-blue-500': notification.severity === 'low'
                                                     }"></div>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm text-gray-900" x-text="notification.message"></p>
                                                <p class="text-xs text-gray-500" x-text="notification.time"></p>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                <div x-show="notifications.length === 0" class="px-4 py-8 text-center">
                                    <i class="fas fa-bell-slash text-2xl text-gray-300 mb-2"></i>
                                    <p class="text-sm text-gray-500">No new notifications</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Enhanced Profile dropdown -->
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
                            <a href="/profile" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors duration-200">Profile Settings</a>
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
        </div>        <!-- Enterprise Main dashboard content -->
        <main class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Enhanced Header -->
                <div class="mb-10">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-600 bg-clip-text text-transparent sm:text-4xl sm:tracking-tight">
                                Dashboard Monitoring
                            </h1>
                            <p class="mt-2 text-lg text-gray-600">
                                Real-time forest fire monitoring at Indonesia
                            </p>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="flex items-center space-x-2 px-4 py-2 bg-green-50 rounded-xl border border-green-200">
                                <div class="flex items-center space-x-1">
                                    <div class="w-2 h-2 rounded-full animate-pulse" :class="{
                                        'bg-green-400': realtimeStatus === 'connected',
                                        'bg-yellow-400': realtimeStatus === 'connecting',
                                        'bg-red-400': realtimeStatus === 'error'
                                    }"></div>
                                    <span class="text-sm font-medium" :class="{
                                        'text-green-700': realtimeStatus === 'connected',
                                        'text-yellow-700': realtimeStatus === 'connecting',
                                        'text-red-700': realtimeStatus === 'error'
                                    }" x-text="realtimeStatus === 'connected' ? 'Indo Hotspot Live' : 
                                               realtimeStatus === 'connecting' ? 'Connecting...' : 'Connection Error'"></span>
                                </div>
                            </div>
                            <button type="button" @click="refreshNASAData()" 
                                    :disabled="fetchingNASA"
                                    class="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white px-6 py-3 rounded-xl text-sm font-semibold transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                                <i class="fas fa-satellite mr-2" :class="{ 'animate-spin': fetchingNASA }"></i>
                                <span x-text="fetchingNASA ? 'Fetching Hotspots...' : 'Fetch Indonesian API'"></span>
                            </button>
                            <button type="button" @click="refreshData()" 
                                    {{-- class="bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white px-6 py-3 rounded-xl text-sm font-semibold transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-1" --}}
                                    class="bg-gradient-to-r from-orange-500 to-amber-400 hover:from-orange-600 hover:to-amber-500 text-white px-6 py-3 rounded-xl text-sm font-semibold transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-1"
                                    :class="{ 'animate-pulse': refreshing }">
                                <i class="fas fa-sync-alt mr-2" :class="{ 'animate-spin': refreshing }"></i>
                                <span x-text="refreshing ? 'Refreshing...' : 'Refresh All'"></span>
                            </button>
                            <button type="button" @click="loadIndoHotspotData()" 
                                    class="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-6 py-3 rounded-xl text-sm font-semibold transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                                <i class="fas fa-globe-asia mr-2"></i>
                                <span>Direct API Call</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Stats Grid -->
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-10">
                    <!-- Active Hotspots -->
                    <div class="gradient-border hover-lift">
                        <div class="gradient-border-content p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-red-600 rounded-xl flex items-center justify-center shadow-lg">
                                        <i class="fas fa-fire text-white text-xl"></i>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Active Hotspots</dt>
                                        <dd class="text-3xl font-bold text-gray-900" x-text="hotspots.length"></dd>
                                        <dd class="text-xs text-gray-500">Real-time monitoring</dd>
                                    </dl>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="text-right">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            Live
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Alerts Today -->
                    <div class="gradient-border hover-lift">
                        <div class="gradient-border-content p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 bg-gradient-to-br from-amber-500 to-red-400 rounded-xl flex items-center justify-center shadow-lg">
                                        <i class="fas fa-exclamation-triangle text-white text-xl"></i>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Alerts Today</dt>
                                        <dd class="text-3xl font-bold text-gray-900" x-text="analyticsData.highRiskAreas || '0'">0</dd>
                                        <dd class="text-xs text-gray-500">Automated detection</dd>
                                    </dl>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="text-right">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            24h
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Coverage Area -->
                    <div class="gradient-border hover-lift">
                        <div class="gradient-border-content p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center shadow-lg">
                                        <i class="fas fa-shield-alt text-white text-xl"></i>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Coverage</dt>
                                        <dd class="text-3xl font-bold text-gray-900" x-text="(stats.coverage || 0) + '%'"></dd>
                                        <dd class="text-xs text-gray-500">Monitoring area</dd>
                                    </dl>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="text-right">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Optimal
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Response Time -->
                    <div class="gradient-border hover-lift">
                        <div class="gradient-border-content p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 bg-gradient-to-br from-sky-500 to-blue-700 rounded-xl flex items-center justify-center shadow-lg">
                                        <i class="fas fa-clock text-white text-xl"></i>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Response Time</dt>
                                        <dd class="text-3xl font-bold text-gray-900" x-text="(stats.responseTime || 0) + ' min'"></dd>
                                        <dd class="text-xs text-gray-500">Average response</dd>
                                    </dl>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="text-right">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            Fast
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Features Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-10">
                    <!-- Real-time Map -->
                    <div class="lg:col-span-2">
                        <div class="gradient-border hover-lift h-full">
                            <div class="gradient-border-content h-full">
                                <div class="px-6 py-4 border-b border-gray-200">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h3 class="text-lg font-semibold text-gray-900">Real-time Hotspot Map</h3>
                                            <p class="text-sm text-gray-500">Monitoring distribusi hotspot</p>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <button type="button" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                                                <i class="fas fa-expand-alt h-4 w-4"></i>
                                            </button>
                                            <button type="button" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                                                <i class="fas fa-cog h-4 w-4"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-6">
                                    <!-- Map Preview with Stats -->
                                    <div class="bg-gradient-to-br from-green-50 via-blue-50 to-red-50 rounded-xl border border-gray-100 overflow-hidden">
                                        <!-- Map Header -->
                                        <div class="bg-white/80 backdrop-blur-sm p-4 border-b border-gray-200">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-4">
                                                    <div class="text-center">
                                                        <div class="text-lg font-bold text-gray-900" x-text="hotspots.length"></div>
                                                        <div class="text-xs text-gray-500">Active Hotspots</div>
                                                    </div>
                                                    <div class="text-center">
                                                        <div class="text-lg font-bold text-blue-600" x-text="getSatelliteTypes().length"></div>
                                                        <div class="text-xs text-gray-500">Satellites</div>
                                                    </div>
                                                    <div class="text-center">
                                                        <div class="text-lg font-bold text-green-600" x-text="getProvinceCount()"></div>
                                                        <div class="text-xs text-gray-500">Provinces</div>
                                                    </div>
                                                </div>
                                                <div class="flex items-center space-x-2">
                                                    <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                                                    <span class="text-xs text-gray-500">Live Data</span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Map Placeholder -->
                                        <div class="h-64 flex items-center justify-center">
                                            <div class="text-center">
                                                <i class="fas fa-map-marked-alt text-5xl text-gray-300 mb-4"></i>
                                                <p class="text-lg font-medium text-gray-600">Interactive Map</p>
                                                <p class="text-sm text-gray-500 mt-2">
                                                    <span x-show="hotspots.length > 0" x-text="'Displaying ' + hotspots.length + ' hotspots'"></span>
                                                    <span x-show="hotspots.length === 0">No hotspots to display</span>
                                                </p>
                                                <button @click="window.location.href='/peta'" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200">
                                                    <i class="fas fa-external-link-alt mr-2"></i>
                                                    Open Full Map
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Recent Hotspot Locations -->
                                        <div x-show="hotspots.length > 0" class="bg-white/80 backdrop-blur-sm p-4 border-t border-gray-200">
                                            <h4 class="text-sm font-medium text-gray-900 mb-3">Recent Locations</h4>
                                            <div class="grid grid-cols-2 gap-3">
                                                <template x-for="hotspot in hotspots.slice(0, 4)" :key="hotspot.id">
                                                    <div class="text-xs p-2 bg-gray-50 rounded">
                                                        <div class="font-medium text-gray-900" x-text="hotspot.provinsi || 'Unknown Province'"></div>
                                                        <div class="text-gray-500" x-text="hotspot.kabupaten || 'Unknown District'"></div>
                                                        <div class="text-gray-400" x-text="formatCoordinates(hotspot.latitude, hotspot.longitude)"></div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- AI Insights Panel -->
                    <div class="space-y-6">
                        <!-- Quick Insights -->
                        <div class="gradient-border hover-lift">
                            <div class="gradient-border-content p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-semibold text-gray-900">System Insights</h3>
                                    <i class="fas fa-lightbulb text-blue-600"></i>
                                </div>
                                <div class="space-y-4">
                                    <div class="p-3 bg-blue-50 rounded-lg">
                                        <div class="flex items-start space-x-3">
                                            <i class="fas fa-chart-line text-blue-600 mt-1"></i>
                                            <div>
                                                <p class="text-sm font-medium text-blue-900">Data Analysis</p>
                                                <p class="text-xs text-blue-700" x-text="'Processing ' + hotspots.length + ' hotspot detections'"></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="p-3 bg-cyan-50 rounded-lg">
                                        <div class="flex items-start space-x-3">
                                            <i class="fas fa-satellite text-cyan-600 mt-1"></i>
                                            <div>
                                                <p class="text-sm font-medium text-cyan-900">Satellite Coverage</p>
                                                <p class="text-xs text-cyan-700" x-text="getSatelliteTypes().length + ' satellites active'"></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="p-3 bg-sky-50 rounded-lg">
                                        <div class="flex items-start space-x-3">
                                            <i class="fas fa-map-marked-alt text-sky-600 mt-1"></i>
                                            <div>
                                                <p class="text-sm font-medium text-sky-900">Geographic Coverage</p>
                                                <p class="text-xs text-sky-700" x-text="getProvinceCount() + ' provinces monitored'"></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="gradient-border hover-lift">
                            <div class="gradient-border-content p-6">
                                <h3 class="text-lg font-semibold text-gray-900">Quick Actions</h3>
                                <p class="text-sm text-gray-500 mb-6">Aksi cepat untuk monitoring</p>
                                <div class="space-y-3">
                                    <a href="/peta" class="block w-full bg-gradient-to-r from-blue-50 to-blue-100 hover:from-blue-100 hover:to-blue-200 text-blue-700 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-200 border border-blue-100">
                                        <i class="fas fa-map-marked-alt mr-2"></i>
                                        Open Interactive Map
                                    </a>
                                    <a href="/analitik" class="block w-full bg-gradient-to-r from-cyan-50 to-blue-50 hover:from-cyan-100 hover:to-blue-100 text-cyan-700 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-200 border border-cyan-100">
                                        <i class="fas fa-chart-line h-4 w-4 me-3"></i> 
                                        View Advanced Analytics
                                    </a>
                                    <a href="/laporan" class="block w-full bg-gradient-to-r from-sky-50 to-blue-50 hover:from-sky-100 hover:to-blue-100 text-sky-700 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-200 border border-sky-100">
                                        <i class="fas fa-file-alt mr-2"></i>
                                        Generate Reports
                                    </a>
                                    <button type="button" @click="exportData()" class="block w-full bg-gradient-to-r from-blue-100 to-blue-200 hover:from-blue-200 hover:to-blue-300 text-blue-700 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-200 border border-blue-200">
                                        <i class="fas fa-download mr-2"></i>
                                        Export Indonesian Data
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Advanced Analytics Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-10">
                    <!-- Predictive Analytics Chart -->
                    <div class="gradient-border hover-lift">
                        <div class="gradient-border-content">
                            <div class="px-6 py-4 border-b border-gray-100">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900">Risk Analysis</h3>
                                        <p class="text-sm text-gray-500">7-day analysis overview</p>
                                    </div>
                                    <div class="flex items-center space-x-1">
                                        <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                                        <span class="text-xs text-gray-500">Live Data</span>
                                    </div>
                                </div>
                            </div>
                            <div class="p-6">
                                <!-- Risk Summary Stats -->
                                <div class="grid grid-cols-2 gap-4 mb-6">
                                    <div class="bg-red-50 rounded-lg p-4 text-center">
                                        <div class="text-2xl font-bold text-red-600" x-text="calculateRisk().high"></div>
                                        <div class="text-xs text-red-700">High Risk</div>
                                    </div>
                                    <div class="bg-yellow-50 rounded-lg p-4 text-center">
                                        <div class="text-2xl font-bold text-yellow-600" x-text="calculateRisk().medium"></div>
                                        <div class="text-xs text-yellow-700">Medium Risk</div>
                                    </div>
                                </div>

                                <!-- Risk Analysis Details -->
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                         <div class="flex items-center space-x-3 min-w-0 flex-1">
                                            <i class="fas fa-fire text-red-500 flex-shrink-0"></i>
                                            <span class="text-sm font-medium text-gray-900 truncate">Active Hotspots</span>
                                        </div>
                                        <span class="text-sm font-bold text-gray-900" x-text="hotspots.length"></span>
                                    </div>
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <div class="flex items-center space-x-3">
                                            <i class="fas fa-percentage text-blue-500"></i>
                                            <span class="text-sm font-medium text-gray-900">Avg Confidence</span>
                                        </div>
                                        <span class="text-sm font-bold text-gray-900" x-text="calculateAverageConfidence() + '%'"></span>
                                    </div>
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <div class="flex items-center space-x-3">
                                            <i class="fas fa-thermometer-half text-orange-500"></i>
                                            <span class="text-sm font-medium text-gray-900">Avg Brightness</span>
                                        </div>
                                        <span class="text-sm font-bold text-gray-900" x-text="calculateAverageBrightness() + 'K'"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Real-time Activity Feed -->
                    <div class="gradient-border hover-lift">
                        <div class="gradient-border-content">
                            <div class="px-6 py-4 border-b border-gray-100">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900">Real-time Activity</h3>
                                        <p class="text-sm text-gray-500">Live system events dan alerts</p>
                                    </div>
                                    <button type="button" class="text-gray-400 hover:text-gray-600">
                                        <i class="fas fa-external-link-alt h-4 w-4"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="p-6">
                                <div class="space-y-4 max-h-64 overflow-y-auto">
                                    <template x-for="notification in notifications.slice(0, 6)" :key="notification.id">
                                        <div class="flex items-start space-x-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                                            <div class="flex-shrink-0 mt-1">
                                                <div class="w-3 h-3 rounded-full" 
                                                     :class="{
                                                         'bg-red-500 animate-pulse': notification.severity === 'high',
                                                         'bg-yellow-500': notification.severity === 'medium',
                                                         'bg-blue-500': notification.severity === 'low'
                                                     }"></div>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-start justify-between">
                                                    <div>
                                                        <p class="text-sm font-medium text-gray-900" x-text="notification.message"></p>
                                                        <p class="text-xs text-gray-500" x-text="notification.time"></p>
                                                    </div>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ml-2" 
                                                          :class="{
                                                              'bg-red-100 text-red-800': notification.severity === 'high',
                                                              'bg-yellow-100 text-yellow-800': notification.severity === 'medium',
                                                              'bg-blue-100 text-blue-800': notification.severity === 'low'
                                                          }" 
                                                          x-text="notification.type.toUpperCase()"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                    
                                    <!-- Real-time Activity Summary -->
                                    <div x-show="notifications.length > 0" class="mt-4 p-3 bg-blue-50 rounded-lg">
                                        <div class="flex items-center justify-between">
                                            <div class="text-sm font-medium text-blue-900">Activity Summary</div>
                                            <div class="text-xs text-blue-700">Last 24h</div>
                                        </div>
                                        <div class="mt-2 grid grid-cols-3 gap-4 text-center">
                                            <div>
                                                <div class="text-lg font-bold text-blue-600" x-text="notifications.filter(n => n.severity === 'high').length"></div>
                                                <div class="text-xs text-blue-700">High Alerts</div>
                                            </div>
                                            <div>
                                                <div class="text-lg font-bold text-blue-600" x-text="notifications.filter(n => n.type === 'system').length"></div>
                                                <div class="text-xs text-blue-700">System Events</div>
                                            </div>
                                            <div>
                                                <div class="text-lg font-bold text-blue-600" x-text="notifications.length"></div>
                                                <div class="text-xs text-blue-700">Total Events</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div x-show="notifications.length === 0" class="text-center py-8">
                                        <i class="fas fa-satellite-dish text-3xl text-gray-300 mb-3"></i>
                                        <p class="text-gray-500">Connecting to data stream...</p>
                                        <p class="text-xs text-gray-400">Waiting for real-time events</p>
                                    </div>
                                </div>
                                <div class="mt-4 pt-4 border-t border-gray-100">
                                    <a href="/laporan" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 flex items-center">
                                        View all activity 
                                        <i class="fas fa-arrow-right ml-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- NASA FIRMS Hotspot Data Section -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-10">
                    <!-- Recent Hotspots Table -->
                    <div class="gradient-border hover-lift">
                        <div class="gradient-border-content">
                            <div class="px-6 py-4 border-b border-gray-100">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900">Recent Indonesian Hotspots</h3>
                                        <p class="text-sm text-gray-500">
                                            <span x-show="hotspots.length > 0">
                                                Showing <span x-text="Math.min(hotspots.length, 8)"></span> of <span x-text="hotspots.length"></span> detected hotspots
                                            </span>
                                            <span x-show="hotspots.length === 0">
                                                Latest fire detections from Indonesian satellite monitoring
                                            </span>
                                        </p>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" 
                                              :class="{
                                                  'bg-green-100 text-green-800': realtimeStatus === 'connected',
                                                  'bg-yellow-100 text-yellow-800': realtimeStatus === 'connecting',
                                                  'bg-red-100 text-red-800': realtimeStatus === 'error'
                                              }">
                                            <i class="fas fa-satellite mr-1"></i>
                                            <span x-text="realtimeStatus === 'connected' ? 'Live Data' : 
                                                         realtimeStatus === 'connecting' ? 'Connecting...' : 'Error'"></span>
                                        </span>
                                        <button @click="loadIndoHotspotData()" 
                                                :disabled="refreshing"
                                                class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded transition-colors duration-200"
                                                :class="{ 'animate-spin': refreshing }">
                                            <i class="fas fa-sync-alt h-4 w-4"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="p-6">
                                <!-- Loading State -->
                                <div x-show="refreshing" class="text-center py-12">
                                    <div class="inline-flex flex-col items-center">
                                        <div class="relative">
                                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
                                            <div class="absolute top-0 left-0 w-12 h-12 rounded-full border-2 border-gray-200"></div>
                                        </div>
                                        <p class="mt-4 text-gray-600 font-medium">Loading Indonesian Hotspot Data...</p>
                                        <p class="mt-1 text-sm text-gray-500">Fetching real-time satellite detections</p>
                                        <div class="mt-2 flex items-center space-x-1 text-xs text-gray-400">
                                            <span>Source:</span>
                                            <span class="font-medium">Indonesian Hotspot API</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Hotspots List -->
                                <div x-show="!refreshing">
                                    <div class="space-y-4 max-h-80 overflow-y-auto">
                                        <template x-for="hotspot in hotspots.slice(0, 8)" :key="hotspot.id">
                                            <div class="flex items-start space-x-4 p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200 border border-gray-200">
                                                <div class="flex-shrink-0 mt-1">
                                                    <div class="w-12 h-12 rounded-lg flex items-center justify-center shadow-sm" 
                                                         :class="{
                                                             'bg-red-100': hotspot.severity === 'high',
                                                             'bg-yellow-100': hotspot.severity === 'medium',
                                                             'bg-green-100': hotspot.severity === 'low'
                                                         }">
                                                        <i class="fas fa-fire text-lg" 
                                                           :class="{
                                                               'text-red-600': hotspot.severity === 'high',
                                                               'text-yellow-600': hotspot.severity === 'medium',
                                                               'text-green-600': hotspot.severity === 'low'
                                                           }"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <!-- Header with Province and Severity -->
                                                    <div class="flex items-center justify-between mb-2">
                                                        <div class="flex flex-col">
                                                            <p class="text-sm font-semibold text-gray-900">
                                                                <span x-text="hotspot.provinsi || 'Unknown Province'"></span>
                                                            </p>
                                                            <p class="text-xs text-gray-600" x-show="hotspot.kabupaten">
                                                                <i class="fas fa-map-marker-alt mr-1"></i>
                                                                <span x-text="hotspot.kabupaten"></span>
                                                                <span x-show="hotspot.kecamatan" x-text="', ' + hotspot.kecamatan"></span>
                                                            </p>
                                                        </div>
                                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium" 
                                                              :class="getSeverityColor(hotspot.severity)" 
                                                              x-text="hotspot.severity.toUpperCase()"></span>
                                                    </div>
                                                    
                                                    <!-- Coordinates -->
                                                    <div class="mb-2">
                                                        <p class="text-xs text-gray-500">
                                                            <i class="fas fa-globe mr-1"></i>
                                                            <span x-text="formatCoordinates(hotspot.latitude, hotspot.longitude)"></span>
                                                        </p>
                                                    </div>
                                                    
                                                    <!-- Technical Details -->
                                                    <div class="grid grid-cols-3 gap-2 mb-2">
                                                        <div class="text-center p-2 bg-white rounded border">
                                                            <div class="text-xs font-medium text-gray-900" x-text="hotspot.brightness + 'K'"></div>
                                                            <div class="text-xs text-gray-500">Brightness</div>
                                                        </div>
                                                        <div class="text-center p-2 bg-white rounded border">
                                                            <div class="text-xs font-medium text-gray-900" x-text="hotspot.confidence + '%'"></div>
                                                            <div class="text-xs text-gray-500">Confidence</div>
                                                        </div>
                                                        <div class="text-center p-2 bg-white rounded border">
                                                            <div class="text-xs font-medium text-gray-900" x-text="(hotspot.frp || 0).toFixed(1) + ' MW'"></div>
                                                            <div class="text-xs text-gray-500">FRP</div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Time and Satellite Info -->
                                                    <div class="flex items-center justify-between text-xs text-gray-400">
                                                        <div class="flex items-center space-x-2">
                                                            <span>
                                                                <i class="fas fa-clock mr-1"></i>
                                                                <span x-text="hotspot.acq_time"></span>
                                                            </span>
                                                            <span>
                                                                <i class="fas fa-calendar mr-1"></i>
                                                                <span x-text="hotspot.acq_date"></span>
                                                            </span>
                                                        </div>
                                                        <div class="flex items-center">
                                                            <i class="fas fa-satellite mr-1"></i>
                                                            <span x-text="hotspot.satellite || 'Unknown'"></span>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Additional Location Info -->
                                                    <div x-show="hotspot.desa || hotspot.kawasan" class="mt-2 pt-2 border-t border-gray-200">
                                                        <div class="text-xs text-gray-500">
                                                            <span x-show="hotspot.desa">
                                                                <i class="fas fa-home mr-1"></i>
                                                                Desa: <span x-text="hotspot.desa"></span>
                                                            </span>
                                                            <span x-show="hotspot.kawasan" class="ml-2">
                                                                <i class="fas fa-tree mr-1"></i>
                                                                Kawasan: <span x-text="hotspot.kawasan"></span>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                    <!-- Recent Locations Compact List -->
                                    <div x-show="hotspots.length > 0" class="mt-6">
                                        <h4 class="text-sm font-medium text-gray-900 mb-2">Recent Locations</h4>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <template x-for="hotspot in hotspots.slice(0, 4)" :key="'recent-'+hotspot.id">
                                                <div class="bg-gray-50 rounded p-3 flex flex-col">
                                                    <span class="font-semibold text-gray-900 text-sm" x-text="hotspot.provinsi || '-'"></span>
                                                    <span class="text-xs text-gray-500" x-text="hotspot.kabupaten || '-'"></span>
                                                    <span class="text-xs text-gray-400" x-text="formatCoordinates(hotspot.latitude, hotspot.longitude)"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                                <!-- ...existing code for footer actions... -->
                            </div>
                        </div>
                    </div>

                    <!-- Indonesian API Status & Info -->
                    <div class="gradient-border hover-lift">
                        <div class="gradient-border-content">
                            <div class="px-6 py-4 border-b border-gray-100">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900">Indonesian Hotspot API Status</h3>
                                        <p class="text-sm text-gray-500">Real-time Indonesian satellite monitoring information</p>
                                    </div>
                                </div>
                            </div>
                            <div class="p-6">
                                <div class="space-y-6">
                                    <!-- API Configuration Info -->
                                    <div class="bg-blue-50 rounded-lg p-4">
                                        <div class="flex items-start space-x-3">
                                            <i class="fas fa-info-circle text-blue-600 mt-1"></i>
                                            <div>
                                                <h4 class="text-sm font-medium text-blue-900">API Configuration</h4>
                                                <div class="mt-2 text-xs text-blue-700 space-y-1">
                                                    <p><span class="font-medium">Satellites:</span> <span x-text="indoHotspotConfig.satelit.join(', ')"></span></p>
                                                    <p><span class="font-medium">Confidence:</span> <span x-text="indoHotspotConfig.confidence.join(', ')"></span></p>
                                                    <p><span class="font-medium">Late Hours:</span> <span x-text="indoHotspotConfig.late + ' hours'"></span></p>
                                                    <p><span class="font-medium">Last Update:</span> <span x-text="lastUpdate || 'Never'"></span></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Statistics Summary -->
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                                            <div class="text-2xl font-bold text-gray-900" x-text="hotspots.length"></div>
                                            <div class="text-xs text-gray-500">Total Detected</div>
                                        </div>
                                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                                            <div class="text-2xl font-bold text-green-600" x-text="hotspots.filter(h => h.is_active).length"></div>
                                            <div class="text-xs text-gray-500">Currently Active</div>
                                        </div>
                                    </div>

                                    <!-- Data Quality Indicators -->
                                    <div class="space-y-3 mt-4">
                                        <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                                            <div class="flex items-center space-x-3">
                                                <div class="w-3 h-3 bg-green-400 rounded-full" 
                                                     :class="realtimeStatus === 'connected' ? 'animate-pulse' : ''"></div>
                                                <span class="text-sm font-medium text-green-900">API Status</span>
                                            </div>
                                            <span class="text-sm text-green-700 capitalize" x-text="realtimeStatus"></span>
                                        </div>
                                        <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                                            <div class="flex items-center space-x-3">
                                                <i class="fas fa-chart-bar text-blue-600"></i>
                                                <span class="text-sm font-medium text-blue-900">Data Confidence</span>
                                            </div>
                                            <span class="text-sm text-blue-700" x-text="calculateAverageConfidence() + '%'"></span>
                                        </div>
                                        <div class="flex items-center justify-between p-3 bg-purple-50 rounded-lg">
                                            <div class="flex items-center space-x-3">
                                                <i class="fas fa-globe-asia text-purple-600"></i>
                                                <span class="text-sm font-medium text-purple-900">Coverage</span>
                                            </div>
                                            <span class="text-sm text-purple-700" x-text="getProvinceCount() + ' provinces'"></span>
                                        </div>
                                    </div>

                                    <!-- Quick Actions -->
                                    <div class="space-y-3">
                                        <button type="button" @click="refreshNASAData()" 
                                                :disabled="fetchingNASA"
                                                class="w-full bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white px-4 py-3 rounded-lg text-sm font-medium transition-all duration-200">
                                            <i class="fas fa-satellite mr-2" :class="{ 'animate-spin': fetchingNASA }"></i>
                                            <span x-text="fetchingNASA ? 'Fetching...' : 'Refresh Indonesian Data'"></span>
                                        </button>
                                        <button type="button" 
                                                class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-3 rounded-lg text-sm font-medium transition-colors duration-200">
                                            <i class="fas fa-download mr-2"></i>
                                            Export Hotspot Data
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Recent Alerts -->
                    <div class="lg:col-span-2">
                        <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-900">Alert Terbaru</h3>
                                <p class="text-sm text-gray-500">Notifikasi real-time dari sistem monitoring</p>
                            </div>
                            <div class="divide-y divide-gray-200">
                                <template x-for="notification in notifications" :key="notification.id">
                                    <div class="px-6 py-4">
                                        <div class="flex items-center space-x-3">
                                            <div class="flex-shrink-0">
                                                <div class="w-8 h-8 rounded-full flex items-center justify-center" 
                                                     :class="{
                                                         'bg-red-100': notification.severity === 'high',
                                                         'bg-yellow-100': notification.severity === 'medium', 
                                                         'bg-blue-100': notification.severity === 'low'
                                                     }">
                                                    <i class="fas fa-exclamation-triangle text-sm"
                                                       :class="{
                                                           'text-red-600': notification.severity === 'high',
                                                           'text-yellow-600': notification.severity === 'medium',
                                                           'text-blue-600': notification.severity === 'low'
                                                       }"></i>
                                                </div>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-900" x-text="notification.message"></p>
                                                <p class="text-sm text-gray-500" x-text="notification.time"></p>
                                            </div>
                                            <div class="flex-shrink-0">
                                                <button class="text-gray-400 hover:text-gray-600">
                                                    <i class="fas fa-chevron-right text-sm"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <div class="px-6 py-3 bg-gray-50">
                                <a href="/laporan" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                                    Lihat semua laporan <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                   

    <!-- Dashboard JavaScript -->
    <script>
        // Initialize Chart.js for dashboard analytics
        document.addEventListener('DOMContentLoaded', function() {
            // Configure Chart.js defaults for dashboard theme
            if (typeof Chart !== 'undefined') {
                Chart.defaults.font.family = 'Inter, sans-serif';
                Chart.defaults.color = '#6B7280';
                Chart.defaults.borderColor = '#E5E7EB';
                Chart.defaults.backgroundColor = 'rgba(99, 102, 241, 0.1)';
            }
            
            // Load dashboard data
            if (typeof window.Alpine !== 'undefined') {
                console.log('Dashboard initialized');
            }
        });

        // WebSocket connection for real-time data
        function initializeWebSocket() {
            // This would connect to your WebSocket endpoint
            // Example: wss://your-api.com/ws/dashboard
            console.log('WebSocket connection initialized');
        }

        // Auto-refresh for dashboard
        setInterval(() => {
            if (document.visibilityState === 'visible') {
                // Trigger data refresh when page is visible
                document.dispatchEvent(new CustomEvent('dashboard-refresh'));
            }
        }, 60000); // Refresh every minute

        // Initialize dashboard features
        document.addEventListener('alpine:init', () => {
            // Additional Alpine.js initialization for dashboard features
            console.log('Alpine.js Dashboard features initialized');
        });
    </script>
</body>
</html>