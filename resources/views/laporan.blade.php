<!DOCTYPE html>
<html lang="en" class="h-full bg-gradient-to-br from-gray-50 to-gray-100">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <title>Reports - Hotspot Vigilance</title>
    <style>
        * { font-family: 'Inter', sans-serif; }
        .glass-effect { backdrop-filter: blur(10px); background-color: rgba(255, 255, 255, 0.95); }
        .gradient-border { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); padding: 1px; border-radius: 12px; }
        .gradient-border-content { background: white; border-radius: 11px; }
        .hover-lift { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .hover-lift:hover { transform: translateY(-2px); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); }
    </style>
</head>
<body class="h-full bg-gradient-to-br from-gray-50 to-gray-100"
      x-data="{
          sidebarOpen: false,
          sidebarCollapsed: false,
          reportData: {
              hotspots: [],
              filters: {
                  dateRange: '24h',
                  confidence: 'all',
                  provinsi: '',
                  kabkota: '',
                  satelit: ['NASA-MODIS', 'NASA-SNPP', 'NASA-NOAA20']
              },
              stats: {
                  totalHotspots: 0,
                  highConfidence: 0,
                  mediumConfidence: 0,
                  lowConfidence: 0
              }
          },
          loading: false,
          
          init() {
              this.fetchHotspotData();
          },
          
          async fetchHotspotData() {
              this.loading = true;
              const filters = this.reportData.filters;
              const apiUrl = new URL('https://opsroom.sipongidata.my.id/api/opsroom/indoHotspot');
              
              // Build query parameters
              apiUrl.searchParams.append('wilayah', 'IN');
              apiUrl.searchParams.append('filterperiode', 'false');
              apiUrl.searchParams.append('from', '');
              apiUrl.searchParams.append('to', '');
              apiUrl.searchParams.append('late', filters.dateRange === '24h' ? '24' : '168');
              
              // Add satelit filters
              filters.satelit.forEach(sat => {
                  apiUrl.searchParams.append('satelit[]', sat);
              });
              
              // Add confidence filters
              if (filters.confidence === 'all') {
                  ['low', 'medium', 'high'].forEach(conf => {
                      apiUrl.searchParams.append('confidence[]', conf);
                  });
              } else {
                  apiUrl.searchParams.append('confidence[]', filters.confidence);
              }
              
              if (filters.provinsi) {
                  apiUrl.searchParams.append('provinsi', filters.provinsi);
              }
              
              if (filters.kabkota) {
                  apiUrl.searchParams.append('kabkota', filters.kabkota);
              }

              try {
                  const response = await fetch(apiUrl.toString());
                  const data = await response.json();
                  
                  if (data.features) {
                      this.reportData.hotspots = data.features.map(feature => ({
                          id: feature.properties.hs_id,
                          lat: feature.properties.lat,
                          lng: feature.properties.long,
                          confidence: feature.properties.confidence_level,
                          date: feature.properties.date_hotspot,
                          original_date: feature.properties.date_hotspot_ori,
                          source: feature.properties.sumber,
                          province: feature.properties.nama_provinsi,
                          regency: feature.properties.kabkota,
                          district: feature.properties.kecamatan,
                          village: feature.properties.desa,
                          coordinates: feature.geometry.coordinates
                      }));
                      
                      this.updateStats();
                  }
              } catch (error) {
                  console.error('Error fetching hotspot data:', error);
                  // Use dummy data as fallback
                  this.loadDummyData();
              } finally {
                  this.loading = false;
              }
          },
          
          loadDummyData() {
              this.reportData.hotspots = [
                  {
                      id: 'HS001',
                      lat: -6.2088,
                      lng: 106.8456,
                      confidence: 'high',
                      date: '2024-01-15 14:30:00',
                      original_date: '2024-01-15 14:30:00',
                      source: 'NASA-MODIS',
                      province: 'DKI Jakarta',
                      regency: 'Jakarta Pusat',
                      district: 'Gambir',
                      village: 'Kebon Kelapa',
                      coordinates: [106.8456, -6.2088]
                  },
                  {
                      id: 'HS002',
                      lat: -7.2504,
                      lng: 112.7688,
                      confidence: 'medium',
                      date: '2024-01-15 13:45:00',
                      original_date: '2024-01-15 13:45:00',
                      source: 'NASA-SNPP',
                      province: 'Jawa Timur',
                      regency: 'Surabaya',
                      district: 'Wonokromo',
                      village: 'Jagir',
                      coordinates: [112.7688, -7.2504]
                  }
              ];
              this.updateStats();
          },
          
          updateStats() {
              const hotspots = this.reportData.hotspots;
              this.reportData.stats.totalHotspots = hotspots.length;
              this.reportData.stats.highConfidence = hotspots.filter(h => h.confidence === 'high').length;
              this.reportData.stats.mediumConfidence = hotspots.filter(h => h.confidence === 'medium').length;
              this.reportData.stats.lowConfidence = hotspots.filter(h => h.confidence === 'low').length;
          },
          
          applyFilters() {
              this.fetchHotspotData();
          },
          
          formatDate(dateString) {
              const date = new Date(dateString);
              return date.toLocaleDateString('id-ID') + ' ' + date.toLocaleTimeString('id-ID');
          },
          
          getConfidenceColor(confidence) {
              switch(confidence) {
                  case 'high': return 'text-red-600 bg-red-100';
                  case 'medium': return 'text-yellow-600 bg-yellow-100';
                  case 'low': return 'text-green-600 bg-green-100';
                  default: return 'text-gray-600 bg-gray-100';
              }
          },
          
          async generateReport(type) {
              try {
                  const response = await fetch(`/api/reports/generate/${type}`, {
                      method: 'POST',
                      headers: { 'Content-Type': 'application/json' },
                      body: JSON.stringify(this.reportData.filters)
                  });

                  // Cek response header untuk memastikan file
                  const disposition = response.headers.get('Content-Disposition');
                  const contentType = response.headers.get('Content-Type');

                  if (response.ok && (
                      (type === 'pdf' && contentType === 'application/pdf') ||
                      (type === 'excel' && (contentType === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' || contentType === 'application/vnd.ms-excel')) ||
                      (disposition && disposition.includes('attachment'))
                  )) {
                      const blob = await response.blob();
                      const url = window.URL.createObjectURL(blob);
                      const a = document.createElement('a');
                      a.href = url;
                      a.download = `${type}_report_${new Date().toISOString().split('T')[0]}.${type === 'pdf' ? 'pdf' : 'xlsx'}`;
                      document.body.appendChild(a);
                      a.click();
                      setTimeout(() => {
                          window.URL.revokeObjectURL(url);
                          document.body.removeChild(a);
                      }, 100);
                  } else {
                      alert('Gagal mengunduh file. Pastikan backend mengembalikan file yang benar.');
                      console.error(`Failed to generate ${type.toUpperCase()} report.`, await response.text());
                  }
              } catch (error) {
                  alert('Terjadi kesalahan saat mengunduh file.');
                  console.error('Error generating report:', error);
              }
          },
          
          async refreshData() {
              await this.fetchHotspotData();
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
                    <p class="text-xs text-blue-600 font-medium">Reports</p>
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
                text-gray-700 hover:bg-blue-50 hover:text-blue-700">
                <i class="fas fa-chart-line h-4 w-4 me-3"></i>
                Analytics
            </a>
            <a href="/laporan" class="flex items-center px-4 py-3 rounded-lg font-semibold text-sm transition
                bg-gradient-to-r from-blue-500 to-blue-600 text-white shadow
                hover:from-blue-600 hover:to-blue-700">
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
                <p class="text-xs text-gray-500 truncate">Report Manager</p>
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
        <!-- Top navigation -->
        <div class="sticky top-0 z-40 flex h-16 glass-effect shadow-sm border-b border-gray-200/50">
            <button type="button" class="border-r border-gray-200 px-4 text-gray-500 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500 lg:hidden"
                    x-on:click="sidebarOpen = true">
                <span class="sr-only">Open sidebar</span>
                <i class="fas fa-bars h-6 w-6"></i>
            </button>
            <div class="flex flex-1 justify-between px-4">
                <div class="flex flex-1">
                    <div class="flex w-full md:ml-0">
                        <label for="search-field" class="sr-only">Search</label>
                        <div class="relative w-full text-gray-400 focus-within:text-gray-600">
                            <div class="absolute inset-y-0 left-0 flex items-center pointer-events-none">
                                <i class="fas fa-search h-5 w-5"></i>
                            </div>
                            <input id="search-field" class="block w-full h-full pl-8 pr-3 py-2 border-transparent text-gray-900 placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-0 focus:border-transparent bg-transparent" placeholder="Search reports..." type="search">
                        </div>
                    </div>
                </div>
                <div class="ml-4 flex items-center md:ml-6 space-x-4">
                    <!-- Report actions -->
                    <div class="flex items-center space-x-2">
                        <button type="button" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                x-on:click="generateReport('pdf')">
                            <i class="fas fa-file-pdf mr-2 h-4 w-4 text-red-500"></i>
                            PDF
                        </button>
                        <button type="button" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                x-on:click="generateReport('excel')">
                            <i class="fas fa-file-excel mr-2 h-4 w-4 text-green-500"></i>
                            Excel
                        </button>
                    </div>

                    <!-- Notifications -->
                    <button type="button" class="bg-white p-1 rounded-full text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <span class="sr-only">View notifications</span>
                        <i class="fas fa-bell h-6 w-6"></i>
                    </button>

                    <!-- Profile dropdown (match analitik/dashboard) -->
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
                             x-transition:leave="transition ease-in duration-75"
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

        <!-- Page header -->
        <div class="glass-effect border-b border-gray-200/50 px-6 py-6">
            <div class="flex items-center justify-between">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg mr-4">
                            <i class="fas fa-file-alt text-white text-lg"></i>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">
                                Reports & Analytics
                                <span x-show="loading" class="inline-flex items-center ml-3">
                                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-500"></div>
                                </span>
                            </h1>
                            <p class="text-sm text-gray-600 mt-1">
                                Generate comprehensive reports and analyze system performance
                                <span class="inline-flex items-center ml-2 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <span class="w-1.5 h-1.5 bg-green-400 rounded-full mr-1.5 animate-pulse"></span>
                                    Live Data
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <button type="button" x-on:click="refreshData()" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                        <i class="h-4 w-4 mr-2" :class="loading ? 'fas fa-spinner animate-spin' : 'fas fa-sync-alt'"></i>
                        <span x-text="loading ? 'Refreshing...' : 'Refresh'"></span>
                    </button>
                    <button type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                        <i class="fas fa-filter mr-2 h-4 w-4"></i>
                        Filter Reports
                    </button>
                    <button type="button" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                        <i class="fas fa-plus mr-2 h-4 w-4"></i>
                        New Report
                    </button>
                </div>
            </div>
        </div>

        <!-- Main content area -->
        <main class="flex-1 relative overflow-y-auto">
            <div class="p-6 space-y-8">
                <!-- Statistics Overview -->
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="gradient-border hover-lift">
                        <div class="gradient-border-content p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 bg-gradient-to-br from-red-500 to-red-600 rounded-xl flex items-center justify-center shadow-lg">
                                        <i class="fas fa-fire text-white text-lg"></i>
                                    </div>
                                </div>
                                <div class="ml-4 flex-1">
                                    <p class="text-sm font-medium text-gray-600">Total Hotspots</p>
                                    <p class="text-2xl font-bold text-gray-900" x-text="reportData.stats.totalHotspots || '0'"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="gradient-border hover-lift">
                        <div class="gradient-border-content p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 bg-gradient-to-br from-red-600 to-red-700 rounded-xl flex items-center justify-center shadow-lg">
                                        <i class="fas fa-exclamation-triangle text-white text-lg"></i>
                                    </div>
                                </div>
                                <div class="ml-4 flex-1">
                                    <p class="text-sm font-medium text-gray-600">High Confidence</p>
                                    <p class="text-2xl font-bold text-gray-900" x-text="reportData.stats.highConfidence || '0'"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="gradient-border hover-lift">
                        <div class="gradient-border-content p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl flex items-center justify-center shadow-lg">
                                        <i class="fas fa-question-circle text-white text-lg"></i>
                                    </div>
                                </div>
                                <div class="ml-4 flex-1">
                                    <p class="text-sm font-medium text-gray-600">Medium Confidence</p>
                                    <p class="text-2xl font-bold text-gray-900" x-text="reportData.stats.mediumConfidence || '0'"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="gradient-border hover-lift">
                        <div class="gradient-border-content p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center shadow-lg">
                                        <i class="fas fa-info-circle text-white text-lg"></i>
                                    </div>
                                </div>
                                <div class="ml-4 flex-1">
                                    <p class="text-sm font-medium text-gray-600">Low Confidence</p>
                                    <p class="text-2xl font-bold text-gray-900" x-text="reportData.stats.lowConfidence || '0'"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Report Generator -->
                <div class="gradient-border hover-lift">
                    <div class="gradient-border-content">
                        <div class="p-6 border-b border-gray-200/50">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Advanced Report Generator</h3>
                                    <p class="text-sm text-gray-600 mt-1">Create custom reports with advanced filtering and analytics</p>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <span class="w-1.5 h-1.5 bg-blue-400 rounded-full mr-1.5"></span>
                                        Ready
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Report Type</label>
                                    <select x-model="reportData.filters.type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                                        <option value="incident">Incident Analysis</option>
                                        <option value="performance">Performance Report</option>
                                        <option value="risk">Risk Assessment</option>
                                        <option value="summary">Monthly Summary</option>
                                        <option value="custom">Custom Report</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                                    <select x-model="reportData.filters.dateRange" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                                        <option value="24h">Last 24 Hours</option>
                                        <option value="7d">Last 7 Days</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Confidence Level</label>
                                    <select x-model="reportData.filters.confidence" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                                        <option value="all">All Levels</option>
                                        <option value="high">High Confidence</option>
                                        <option value="medium">Medium Confidence</option>
                                        <option value="low">Low Confidence</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Province</label>
                                    <select x-model="reportData.filters.provinsi" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                                        <option value="">All Provinces</option>
                                        <option value="11">Aceh</option>
                                        <option value="12">Sumatera Utara</option>
                                        <option value="13">Sumatera Barat</option>
                                        <option value="14">Riau</option>
                                        <option value="15">Jambi</option>
                                        <option value="16">Sumatera Selatan</option>
                                        <option value="17">Bengkulu</option>
                                        <option value="18">Lampung</option>
                                        <option value="19">Kepulauan Bangka Belitung</option>
                                        <option value="21">Kepulauan Riau</option>
                                        <option value="31">DKI Jakarta</option>
                                        <option value="32">Jawa Barat</option>
                                        <option value="33">Jawa Tengah</option>
                                        <option value="34">DI Yogyakarta</option>
                                        <option value="35">Jawa Timur</option>
                                        <option value="36">Banten</option>
                                        <option value="51">Bali</option>
                                        <option value="52">Nusa Tenggara Barat</option>
                                        <option value="53">Nusa Tenggara Timur</option>
                                        <option value="61">Kalimantan Barat</option>
                                        <option value="62">Kalimantan Tengah</option>
                                        <option value="63">Kalimantan Selatan</option>
                                        <option value="64">Kalimantan Timur</option>
                                        <option value="65">Kalimantan Utara</option>
                                        <option value="71">Sulawesi Utara</option>
                                        <option value="72">Sulawesi Tengah</option>
                                        <option value="73">Sulawesi Selatan</option>
                                        <option value="74">Sulawesi Tenggara</option>
                                        <option value="75">Gorontalo</option>
                                        <option value="76">Sulawesi Barat</option>
                                        <option value="81">Maluku</option>
                                        <option value="82">Maluku Utara</option>
                                        <option value="91">Papua Barat</option>
                                        <option value="94">Papua</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Format</label>
                                    <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                                        <option value="pdf">PDF Document</option>
                                        <option value="excel">Excel Spreadsheet</option>
                                        <option value="csv">CSV Data</option>
                                        <option value="json">JSON Export</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Include Charts</label>
                                    <div class="flex items-center mt-2">
                                        <input type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                        <label class="ml-2 text-sm text-gray-700">Include visual charts and graphs</label>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center justify-between mt-6 pt-6 border-t border-gray-200/50">
                                <div class="flex items-center space-x-4">
                                    <button type="button" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors duration-200">
                                        <i class="fas fa-eye mr-2 h-4 w-4"></i>
                                        Preview
                                    </button>
                                    <button type="button" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors duration-200">
                                        <i class="fas fa-save mr-2 h-4 w-4"></i>
                                        Save Template
                                    </button>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <button type="button" x-on:click="applyFilters()" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg hover:from-blue-600 hover:to-blue-700 transition-all duration-200 shadow-lg hover:shadow-xl">
                                        <i class="fas fa-filter mr-2 h-4 w-4"></i>
                                        Apply Filters
                                    </button>
                                    <button type="button" x-on:click="generateReport('pdf')" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-red-500 to-red-600 rounded-lg hover:from-red-600 hover:to-red-700 transition-all duration-200 shadow-lg hover:shadow-xl">
                                        <i class="fas fa-file-pdf mr-2 h-4 w-4"></i>
                                        Generate PDF
                                    </button>
                                    <button type="button" x-on:click="generateReport('excel')" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-green-500 to-green-600 rounded-lg hover:from-green-600 hover:to-green-700 transition-all duration-200 shadow-lg hover:shadow-xl">
                                        <i class="fas fa-file-excel mr-2 h-4 w-4"></i>
                                        Generate Excel
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hotspot Data -->
                <div class="gradient-border hover-lift">
                    <div class="gradient-border-content">
                        <div class="p-6 border-b border-gray-200/50">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Live Hotspot Data</h3>
                                    <p class="text-sm text-gray-600 mt-1">Real-time fire hotspot monitoring from Indonesian satellites</p>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button type="button" class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors duration-200">
                                        <i class="fas fa-filter mr-1 h-3 w-3"></i>
                                        Filter
                                    </button>
                                    <button type="button" class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors duration-200">
                                        <i class="fas fa-sort mr-1 h-3 w-3"></i>
                                        Sort
                                    </button>
                                    <button type="button" x-on:click="refreshData()" class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors duration-200">
                                        <i class="h-3 w-3 mr-1" :class="loading ? 'fas fa-spinner animate-spin' : 'fas fa-sync-alt'"></i>
                                        Refresh
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Loading State -->
                        <div x-show="loading" class="p-8 text-center">
                            <div class="inline-flex items-center">
                                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-500"></div>
                                <span class="ml-2 text-gray-600">Loading hotspot data...</span>
                            </div>
                        </div>

                        <!-- Empty State (API-ready placeholder) -->
                        <div x-show="!loading && (!reportData.hotspots || reportData.hotspots.length === 0)" class="text-center py-12">
                            <div class="w-16 h-16 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center mx-auto mb-4 shadow-sm">
                                <i class="fas fa-fire text-gray-400 text-2xl"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No hotspots found</h3>
                            <p class="text-gray-500 mb-6">No hotspot data found for the current filters. Try adjusting your filters or check back later.</p>
                            <button type="button" x-on:click="applyFilters()" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                                <i class="fas fa-sync-alt mr-2 h-4 w-4"></i>
                                Refresh Data
                            </button>
                        </div>

                        <!-- Hotspots Table (when data is available) -->
                        <div x-show="!loading && reportData.hotspots && reportData.hotspots.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50/50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hotspot ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Confidence</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Detected</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Coordinates</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <template x-for="hotspot in reportData.hotspots" :key="hotspot.id">
                                        <tr class="hover:bg-gray-50 transition-colors duration-200">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-10 w-10">
                                                        <div class="h-10 w-10 rounded-lg bg-red-100 flex items-center justify-center shadow-sm">
                                                            <i class="fas fa-fire text-red-600 text-sm"></i>
                                                        </div>
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900" x-text="hotspot.id"></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm text-gray-900" x-text="hotspot.village + ', ' + hotspot.district"></div>
                                                <div class="text-sm text-gray-500" x-text="hotspot.regency + ', ' + hotspot.province"></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize"
                                                      :class="getConfidenceColor(hotspot.confidence)"
                                                      x-text="hotspot.confidence"></span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="formatDate(hotspot.date)"></td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                                                      x-text="hotspot.source"></span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <div x-text="hotspot.lat.toFixed(4) + ', ' + hotspot.lng.toFixed(4)"></div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<style>
    [x-cloak] { display: none !important; }
    @media (min-width: 1024px) {
        aside[role="complementary"] {
            transform: translateX(0) !important;
        }
    }
</style>
</body>
</html>