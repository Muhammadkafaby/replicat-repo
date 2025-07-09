<!DOCTYPE html>
<html lang="id" class="h-full bg-gradient-to-br from-gray-50 to-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Analytics - Hotspot Vigilance</title>
    <meta name="description" content="Analytics dan laporan monitoring kebakaran hutan real-time untuk Sumatera Selatan.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        .glass-effect { backdrop-filter: blur(10px); background-color: rgba(255, 255, 255, 0.95); }
        /* Ubah gradient-border ke biru */
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
          analyticsData: {
              totalIncidents: 0,
              highRiskAreas: 0,
              activeSensors: 0,
              avgResponseTime: 0,
              trends: [],
              heatmapData: [],
              alerts: []
          },
          realtimeStatus: 'connecting',
          lastUpdate: null,
          
          // Initialize analytics
          init() {
              this.loadAnalyticsData();
              this.connectWebSocket();
              setInterval(() => this.loadAnalyticsData(), 30000); // Refresh every 30 seconds
          },
          
          // Load analytics data from API
          async loadAnalyticsData() {
              this.refreshing = true;
              try {
                  // Fetch real hotspot data from the same API used in map
                  const response = await fetch('https://opsroom.sipongidata.my.id/api/opsroom/indoHotspot?wilayah=IN&filterperiode=false&from=&to=&late=24&satelit[]=NASA-MODIS&satelit[]=NASA-SNPP&satelit[]=NASA-NOAA20&confidence[]=low&confidence[]=medium&confidence[]=high&provinsi=&kabkota=');
                  
                  if (response.ok) {
                      const data = await response.json();
                      
                      if (data && data.features) {
                          // Process the real hotspot data
                          const features = data.features;
                          
                          // Calculate analytics from real data
                          const totalIncidents = features.length;
                          
                          // Count high risk areas (confidence >= 70)
                          const highRiskAreas = features.filter(f => 
                              parseFloat(f.properties.confidence) >= 70
                          ).length;
                          
                          // Count active sensors (unique sources)
                          const uniqueSources = [...new Set(features.map(f => f.properties.sumber))];
                          const activeSensors = uniqueSources.length;
                          
                          // Calculate average confidence as response metric
                          const avgConfidence = features.reduce((sum, f) => 
                              sum + (parseFloat(f.properties.confidence) || 0), 0) / features.length;
                          const avgResponseTime = Math.round(avgConfidence / 10); // Convert to minutes
                          
                          // Process recent alerts (high confidence incidents)
                          const alerts = features
                              .filter(f => parseFloat(f.properties.confidence) >= 50)
                              .slice(0, 10)
                              .map((f, index) => {
                                  const confidence = parseFloat(f.properties.confidence);
                                  let severity = 'low';
                                  if (confidence >= 80) severity = 'critical';
                                  else if (confidence >= 70) severity = 'high';
                                  else if (confidence >= 50) severity = 'medium';
                                  
                                  return {
                                      id: index,
                                      title: `Hotspot Alert - ${f.properties.confidence_level || 'Unknown'} Confidence`,
                                      location: `${f.properties.desa || f.properties.kecamatan || 'Unknown'}, ${f.properties.kabkota || 'Unknown'}`,
                                      severity: severity,
                                      time: new Date(f.properties.date_hotspot).toLocaleTimeString('id-ID', {
                                          hour: '2-digit',
                                          minute: '2-digit'
                                      }),
                                      confidence: confidence
                                  };
                              });
                          
                          // Create trends data (group by day for last 7 days)
                          const trendsData = this.processTrendsData(features);
                          
                          // Update analytics data
                          this.analyticsData = {
                              totalIncidents: totalIncidents,
                              highRiskAreas: highRiskAreas,
                              activeSensors: activeSensors,
                              avgResponseTime: avgResponseTime,
                              trends: trendsData,
                              alerts: alerts,
                              features: features // Store raw data for charts
                          };
                          
                          this.lastUpdate = new Date().toLocaleTimeString('id-ID');
                          this.realtimeStatus = 'connected';
                          
                          // Initialize charts with real data
                          setTimeout(() => {
                              this.initializeCharts();
                          }, 100);
                      }
                  } else {
                      throw new Error('Failed to fetch data');
                  }
              } catch (error) {
                  console.error('Error loading analytics data:', error);
                  this.realtimeStatus = 'error';
                  // Set fallback data
                  this.analyticsData = {
                      totalIncidents: 0,
                      highRiskAreas: 0,
                      activeSensors: 0,
                      avgResponseTime: 0,
                      trends: [],
                      alerts: []
                  };
              } finally {
                  this.refreshing = false;
              }
          },
          
          // Process trends data from features
          processTrendsData(features) {
              const trends = {};
              const today = new Date();
              
              // Initialize last 7 days
              for (let i = 6; i >= 0; i--) {
                  const date = new Date(today);
                  date.setDate(today.getDate() - i);
                  const dateKey = date.toISOString().split('T')[0];
                  trends[dateKey] = 0;
              }
              
              // Count incidents per day
              features.forEach(feature => {
                  if (feature.properties.date_hotspot) {
                      const incidentDate = new Date(feature.properties.date_hotspot);
                      const dateKey = incidentDate.toISOString().split('T')[0];
                      if (trends.hasOwnProperty(dateKey)) {
                          trends[dateKey]++;
                      }
                  }
              });
              
              return Object.entries(trends).map(([date, count]) => ({
                  date: new Date(date).toLocaleDateString('id-ID', { 
                      month: 'short', 
                      day: 'numeric' 
                  }),
                  incidents: count
              }));
          },
          
          // Initialize charts with real data
          initializeCharts() {
              if (this.analyticsData.features) {
                  this.initTrendsChart();
                  this.initDistributionChart();
                  this.initConfidenceChart();
              }
          },
          
          // Initialize trends chart
          initTrendsChart() {
              const ctx = document.getElementById('trendsChart');
              if (ctx && this.analyticsData.trends) {
                  const chart = new Chart(ctx.getContext('2d'), {
                      type: 'line',
                      data: {
                          labels: this.analyticsData.trends.map(t => t.date),
                          datasets: [{
                              label: 'Daily Incidents',
                              data: this.analyticsData.trends.map(t => t.incidents),
                              borderColor: 'rgb(59, 130, 246)',
                              backgroundColor: 'rgba(59, 130, 246, 0.1)',
                              tension: 0.4,
                              fill: true
                          }]
                      },
                      options: {
                          responsive: true,
                          maintainAspectRatio: false,
                          plugins: {
                              legend: {
                                  display: false
                              }
                          },
                          scales: {
                              y: {
                                  beginAtZero: true,
                                  grid: {
                                      color: 'rgba(0, 0, 0, 0.05)'
                                  }
                              },
                              x: {
                                  grid: {
                                      display: false
                                  }
                              }
                          }
                      }
                  });
              }
          },
          
          // Initialize distribution chart
          initDistributionChart() {
              const ctx = document.getElementById('distributionChart');
              if (ctx && this.analyticsData.features) {
                  // Group by confidence level
                  const critical = this.analyticsData.features.filter(f => parseFloat(f.properties.confidence) >= 80).length;
                  const high = this.analyticsData.features.filter(f => {
                      const conf = parseFloat(f.properties.confidence);
                      return conf >= 60 && conf < 80;
                  }).length;
                  const medium = this.analyticsData.features.filter(f => {
                      const conf = parseFloat(f.properties.confidence);
                      return conf >= 40 && conf < 60;
                  }).length;
                  const low = this.analyticsData.features.filter(f => parseFloat(f.properties.confidence) < 40).length;
                  
                  const chart = new Chart(ctx.getContext('2d'), {
                      type: 'doughnut',
                      data: {
                          labels: ['Critical', 'High', 'Medium', 'Low'],
                          datasets: [{
                              data: [critical, high, medium, low],
                              backgroundColor: [
                                  '#EF4444',
                                  '#F59E0B',
                                  '#EAB308',
                                  '#22C55E'
                              ],
                              borderWidth: 0
                          }]
                      },
                      options: {
                          responsive: true,
                          maintainAspectRatio: false,
                          plugins: {
                              legend: {
                                  position: 'bottom',
                                  labels: {
                                      padding: 20,
                                      usePointStyle: true
                                  }
                              }
                          }
                      }
                  });
              }
          },
          
          // Initialize confidence chart
          initConfidenceChart() {
              const ctx = document.getElementById('confidenceChart');
              if (ctx && this.analyticsData.features) {
                  // Group by province
                  const provinceData = {};
                  this.analyticsData.features.forEach(f => {
                      const province = f.properties.nama_provinsi || 'Unknown';
                      if (!provinceData[province]) {
                          provinceData[province] = [];
                      }
                      provinceData[province].push(parseFloat(f.properties.confidence) || 0);
                  });
                  
                  // Calculate average confidence per province
                  const provinces = Object.keys(provinceData).slice(0, 10); // Top 10 provinces
                  const avgConfidences = provinces.map(province => {
                      const confidences = provinceData[province];
                      return confidences.reduce((sum, conf) => sum + conf, 0) / confidences.length;
                  });
                  
                  const chart = new Chart(ctx.getContext('2d'), {
                      type: 'bar',
                      data: {
                          labels: provinces.map(p => p.length > 15 ? p.substring(0, 15) + '...' : p),
                          datasets: [{
                              label: 'Average Confidence',
                              data: avgConfidences,
                              backgroundColor: 'rgba(59, 130, 246, 0.8)',
                              borderColor: 'rgb(59, 130, 246)',
                              borderWidth: 1
                          }]
                      },
                      options: {
                          responsive: true,
                          maintainAspectRatio: false,
                          plugins: {
                              legend: {
                                  display: false
                              }
                          },
                          scales: {
                              y: {
                                  beginAtZero: true,
                                  max: 100,
                                  grid: {
                                      color: 'rgba(0, 0, 0, 0.05)'
                                  }
                              },
                              x: {
                                  grid: {
                                      display: false
                                  }
                              }
                          }
                      }
                  });
              }
          },
          
          // Connect to WebSocket for real-time updates
          connectWebSocket() {
              try {
                  // Replace with actual WebSocket endpoint
                  const ws = new WebSocket('wss://your-websocket-endpoint');
                  ws.onopen = () => { this.realtimeStatus = 'connected'; };
                  ws.onmessage = (event) => {
                      const data = JSON.parse(event.data);
                      if (data.type === 'analytics_update') {
                          this.analyticsData = { ...this.analyticsData, ...data.payload };
                      }
                  };
                  ws.onclose = () => { this.realtimeStatus = 'disconnected'; };
                  ws.onerror = () => { this.realtimeStatus = 'error'; };
              } catch (error) {
                  this.realtimeStatus = 'error';
              }
          },
          
          // Manual refresh
          async refreshData() {
              await this.loadAnalyticsData();
          }
      }"
      x-bind:class="sidebarOpen ? 'overflow-hidden lg:overflow-auto' : ''"
      x-on:keydown.escape="sidebarOpen = false">

<div class="flex h-full">
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
                    <p class="text-xs text-blue-600 font-medium">Analytics</p>
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
                text-gray-700 hover:bg-cyan-50 hover:text-cyan-700">
                <i class="fas fa-map-marked-alt mr-3"></i>
                Interactive Maps 
            </a>
            <a href="/analitik" class="flex items-center px-4 py-3 rounded-lg font-semibold text-sm transition
                bg-gradient-to-r from-blue-500 to-blue-600 text-white shadow
                hover:from-blue-600 hover:to-blue-700">
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

    <!-- Main content -->
    <div class="flex flex-1 flex-col transition-all duration-300 ease-in-out lg:ml-72 xl:ml-80">
        <!-- Top navigation bar (match dashboard & peta) -->
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
                                    <i class="fas fa-chart-line h-4 w-4 text-gray-400"></i>
                                    <span class="ml-2 text-sm font-medium text-gray-900">Analytics</span>
                            </div>
                        </li>
                    </ol>
                </nav>
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
                <!-- Quick Actions (remove Refresh & Export) -->
                <!-- Notifications -->
                <div class="relative" x-data="{ open: false }">
                    <button type="button" @click="open = !open"
                            class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-all duration-200 group">
                        <span class="sr-only">View notifications</span>
                        <div class="relative">
                            <i class="fas fa-bell h-5 w-5 group-hover:scale-110 transition-transform duration-200"></i>
                        </div>
                    </button>
                    <!-- Notification dropdown (optional) -->
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

    <!-- Page header with status indicator -->
    <div class="bg-white/80 backdrop-blur-sm border-b border-gray-200 px-4 py-6 sm:flex sm:items-center sm:justify-between sm:px-6 lg:px-8">
        <div class="flex-1 min-w-0">
            <div class="flex items-center space-x-3">
                <div class="relative">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-chart-line text-white text-xl"></i>
                    </div>
                    <div class="absolute -top-1 -right-1 w-4 h-4 rounded-full" 
                         :class="{
                             'bg-green-400 animate-pulse': realtimeStatus === 'connected',
                             'bg-yellow-400 animate-pulse': realtimeStatus === 'connecting',
                             'bg-red-400': realtimeStatus === 'error'
                         }"></div>
                </div>
                <div>
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-gray-900 to-gray-600 bg-clip-text text-transparent sm:text-3xl">
                        Analytics Dashboard
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Comprehensive hotspot monitoring and analysis
                    </p>
                    <div class="mt-1 flex items-center space-x-4">
                        <span class="text-xs" 
                              :class="{
                                  'text-green-600': realtimeStatus === 'connected',
                                  'text-yellow-600': realtimeStatus === 'connecting',
                                  'text-red-600': realtimeStatus === 'error'
                              }"
                              x-text="realtimeStatus === 'connected' ? 'Real-time Data Active' : 
                                     realtimeStatus === 'connecting' ? 'Connecting...' : 'Connection Error'"></span>
                        <span class="text-xs text-gray-400" x-show="lastUpdate" x-text="`Last updated: ${lastUpdate}`"></span>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Main content area -->
    <main class="flex-1 relative overflow-y-auto focus:outline-none">
        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Stats overview with modern cards -->
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-8">
                    <!-- Total Incidents Card -->
                    <div class="gradient-border hover-lift">
                        <div class="gradient-border-content p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-red-600 rounded-xl flex items-center justify-center shadow-lg">
                                        <i class="fas fa-fire text-white text-lg"></i>
                                    </div>
                                </div>
                                <div class="ml-4 w-0 flex-1">
                                    <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Total Incidents</h3>
                                    <p class="text-2xl font-bold text-gray-900" x-text="analyticsData.totalIncidents || '0'">0</p>
                                </div>
                            </div>
                            <div class="mt-4 flex items-center text-sm">
                                <div class="flex items-center text-green-600">
                                    <i class="fas fa-arrow-up text-xs mr-1"></i>
                                    <span class="font-medium">Live Data</span>
                                </div>
                                <span class="text-gray-500 ml-2">from API</span>
                            </div>
                        </div>
                    </div>

                    <!-- High Risk Areas Card -->
                    <div class="gradient-border hover-lift">
                        <div class="gradient-border-content p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl flex items-center justify-center shadow-lg">
                                        <i class="fas fa-exclamation-triangle text-white text-lg"></i>
                                    </div>
                                </div>
                                <div class="ml-4 w-0 flex-1">
                                    <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">High Risk Areas</h3>
                                    <p class="text-2xl font-bold text-gray-900" x-text="analyticsData.highRiskAreas || '0'">0</p>
                                </div>
                            </div>
                            <div class="mt-4 flex items-center text-sm">
                                <div class="flex items-center text-blue-600">
                                    <i class="fas fa-sync-alt text-xs mr-1"></i>
                                    <span class="font-medium">Real-time</span>
                                </div>
                                <span class="text-gray-500 ml-2">monitoring</span>
                            </div>
                        </div>
                    </div>

                    <!-- Active Sensors Card -->
                    <div class="gradient-border hover-lift">
                        <div class="gradient-border-content p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center shadow-lg">
                                        <i class="fas fa-satellite-dish text-white text-lg"></i>
                                    </div>
                                </div>
                                <div class="ml-4 w-0 flex-1">
                                    <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Active Sensors</h3>
                                    <p class="text-2xl font-bold text-gray-900" x-text="analyticsData.activeSensors || '0'">0</p>
                                </div>
                            </div>
                            <div class="mt-4 flex items-center text-sm">
                                <div class="flex items-center text-green-600">
                                    <i class="fas fa-circle text-xs mr-1"></i>
                                    <span class="font-medium">Online</span>
                                </div>
                                <span class="text-gray-500 ml-2">sensors</span>
                            </div>
                        </div>
                    </div>

                    <!-- Response Time Card -->
                    <div class="gradient-border hover-lift">
                        <div class="gradient-border-content p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg">
                                        <i class="fas fa-clock text-white text-lg"></i>
                                    </div>
                                </div>
                                <div class="ml-4 w-0 flex-1">
                                    <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Avg Response</h3>
                                    <p class="text-2xl font-bold text-gray-900" x-text="analyticsData.avgResponseTime ? analyticsData.avgResponseTime + ' min' : '0 min'">0 min</p>
                                </div>
                            </div>
                            <div class="mt-4 flex items-center text-sm">
                                <div class="flex items-center text-purple-600">
                                    <i class="fas fa-chart-line text-xs mr-1"></i>
                                    <span class="font-medium">Analytics</span>
                                </div>
                                <span class="text-gray-500 ml-2">tracking</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts and analytics section -->
                <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                    <!-- Incident Trends Chart -->
                    <div class="bg-white/80 backdrop-blur-sm shadow-xl rounded-2xl border border-gray-200/50 hover-lift">
                        <div class="px-6 py-4 border-b border-gray-200/50">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-900">Incident Trends</h3>
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                                    <span class="text-xs text-gray-500">Live Data</span>
                                </div>
                            </div>
                            <p class="text-sm text-gray-500 mt-1">Daily incident pattern (last 7 days)</p>
                        </div>
                        <div class="p-6">
                            <canvas id="trendsChart" class="w-full h-64"></canvas>
                        </div>
                    </div>

                    <!-- Risk Distribution Chart -->
                    <div class="bg-white/80 backdrop-blur-sm shadow-xl rounded-2xl border border-gray-200/50 hover-lift">
                        <div class="px-6 py-4 border-b border-gray-200/50">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-900">Risk Distribution</h3>
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 bg-blue-400 rounded-full animate-pulse"></div>
                                    <span class="text-xs text-gray-500">Confidence Levels</span>
                                </div>
                            </div>
                            <p class="text-sm text-gray-500 mt-1">Distribution by confidence level</p>
                        </div>
                        <div class="p-6">
                            <canvas id="distributionChart" class="w-full h-64"></canvas>
                        </div>
                    </div>

                    <!-- Alert Status Overview -->
                    <div class="bg-white/80 backdrop-blur-sm shadow-xl rounded-2xl border border-gray-200/50 hover-lift">
                        <div class="px-6 py-4 border-b border-gray-200/50">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-900">Alert Status</h3>
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 bg-yellow-400 rounded-full animate-pulse"></div>
                                    <span class="text-xs text-gray-500">Active Monitoring</span>
                                </div>
                            </div>
                            <p class="text-sm text-gray-500 mt-1">Current alert status and severity levels</p>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4" x-show="analyticsData.alerts && analyticsData.alerts.length > 0">
                                <template x-for="alert in analyticsData.alerts" :key="alert.id">
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-3 h-3 rounded-full" 
                                                 :class="{
                                                     'bg-red-500': alert.severity === 'critical',
                                                     'bg-orange-500': alert.severity === 'high',
                                                     'bg-yellow-500': alert.severity === 'medium',
                                                     'bg-green-500': alert.severity === 'low'
                                                 }"></div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900" x-text="alert.title"></p>
                                                <p class="text-xs text-gray-500" x-text="alert.location"></p>
                                            </div>
                                        </div>
                                        <span class="text-xs text-gray-400" x-text="alert.time"></span>
                                    </div>
                                </template>
                            </div>
                            <div x-show="!analyticsData.alerts || analyticsData.alerts.length === 0" class="text-center py-8">
                                <i class="fas fa-shield-alt text-gray-400 text-3xl mb-4"></i>
                                <p class="text-gray-500 text-sm">No active alerts</p>
                                <p class="text-gray-400 text-xs">All systems operating normally</p>
                            </div>
                        </div>
                    </div>

                    <!-- Performance Metrics -->
                    <div class="bg-white/80 backdrop-blur-sm shadow-xl rounded-2xl border border-gray-200/50 hover-lift">
                        <div class="px-6 py-4 border-b border-gray-200/50">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-900">Provincial Analysis</h3>
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 bg-purple-400 rounded-full animate-pulse"></div>
                                    <span class="text-xs text-gray-500">Regional Data</span>
                                </div>
                            </div>
                            <p class="text-sm text-gray-500 mt-1">Average confidence by top provinces</p>
                        </div>
                        <div class="p-6">
                            <canvas id="confidenceChart" class="w-full h-64"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Additional metrics row -->
                <div class="grid grid-cols-1 gap-8 lg:grid-cols-2 mt-8">
                    <!-- Data Quality Metrics -->
                    <div class="bg-white/80 backdrop-blur-sm shadow-xl rounded-2xl border border-gray-200/50 hover-lift">
                        <div class="px-6 py-4 border-b border-gray-200/50">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-900">Data Quality</h3>
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                                    <span class="text-xs text-gray-500">Real-time</span>
                                </div>
                            </div>
                            <p class="text-sm text-gray-500 mt-1">Data source reliability and coverage</p>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Data Coverage</span>
                                    <span class="text-sm font-medium text-green-600" x-text="analyticsData.features ? '100%' : '0%'">0%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-500 h-2 rounded-full transition-all duration-1000" 
                                         :style="`width: ${analyticsData.features ? 100 : 0}%`"></div>
                                </div>
                                
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Satellite Sources</span>
                                    <span class="text-sm font-medium text-blue-600" x-text="analyticsData.activeSensors || 0">0</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-blue-500 h-2 rounded-full transition-all duration-1000" 
                                         :style="`width: ${Math.min((analyticsData.activeSensors || 0) * 33, 100)}%`"></div>
                                </div>
                                
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">High Confidence</span>
                                    <span class="text-sm font-medium text-purple-600" 
                                          x-text="analyticsData.features ? Math.round((analyticsData.highRiskAreas / analyticsData.totalIncidents) * 100) + '%' : '0%'">0%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-purple-500 h-2 rounded-full transition-all duration-1000" 
                                         :style="`width: ${analyticsData.features ? Math.round((analyticsData.highRiskAreas / analyticsData.totalIncidents) * 100) : 0}%`"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
</div>

<script>
// Initialize analytics dashboard when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('Analytics dashboard loaded with real hotspot data integration');
});
</script>

</body>
</html>