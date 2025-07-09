<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Hotspot Vigilance</title>
    <meta name="description" content="Solusi profesional untuk monitoring dan deteksi dini kebakaran hutan di Indonesia dengan teknologi real-time.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50" x-data="{ 
    mobileMenuOpen: false, 
    stats: { hotspots: 1247, provinces: 16, alerts: 89, coverage: 99.8 },
    showNotification: false,
    notificationMessage: 'New hotspot detected in Sumatera Selatan - Alert Level: Medium',
    systemStatus: 'operational'
}" x-init="
    setTimeout(() => { showNotification = true }, 3000);
    setTimeout(() => { showNotification = false }, 8000);
">
    
    <!-- Navigation -->
    <nav class="bg-white shadow-lg fixed w-full z-50 top-0">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-cyan-500 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-fire text-white text-lg"></i>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-gray-900">Hotspot Vigilance</h1>
                            <p class="text-xs text-gray-500">Advanced Access</p>
                        </div>
                    </div>
                </div>

                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#home" class="text-gray-700 hover:text-indigo-600 px-3 py-2 text-sm font-medium transition-colors duration-200">Beranda</a>
                    <a href="#features" class="text-gray-700 hover:text-indigo-600 px-3 py-2 text-sm font-medium transition-colors duration-200">Fitur</a>
                    <a href="#security" class="text-gray-700 hover:text-indigo-600 px-3 py-2 text-sm font-medium transition-colors duration-200">Keamanan</a>
                    <a href="#statistics" class="text-gray-700 hover:text-indigo-600 px-3 py-2 text-sm font-medium transition-colors duration-200">Statistik</a>
                    <a href="#contact" class="text-gray-700 hover:text-indigo-600 px-3 py-2 text-sm font-medium transition-colors duration-200">Kontak</a>
                    
                    <div class="flex items-center space-x-3">
                        <a href="{{ route('login') }}" class="text-blue-600 hover:text-blue-800 px-3 py-2 text-sm font-medium transition-colors duration-200">
                            Masuk
                        </a>
                        <a href="{{ route('register') }}" class="bg-gradient-to-r from-blue-500 to-cyan-500 hover:from-blue-600 hover:to-cyan-600 text-white px-6 py-2 rounded-lg text-sm font-medium transition-all duration-200 transform hover:scale-105">
                            Mulai Sekarang
                        </a>
                    </div>
                </div>

                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center">
                    <button @click="mobileMenuOpen = !mobileMenuOpen" class="text-gray-700 hover:text-indigo-600 focus:outline-none focus:text-indigo-600">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Navigation -->
        <div x-show="mobileMenuOpen" x-transition class="md:hidden bg-white border-t border-gray-200">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                <a href="#home" class="block px-3 py-2 text-gray-700 hover:text-indigo-600 text-base font-medium">Beranda</a>
                <a href="#features" class="block px-3 py-2 text-gray-700 hover:text-indigo-600 text-base font-medium">Fitur</a>
                <a href="#security" class="block px-3 py-2 text-gray-700 hover:text-indigo-600 text-base font-medium">Keamanan</a>
                <a href="#statistics" class="block px-3 py-2 text-gray-700 hover:text-indigo-600 text-base font-medium">Statistik</a>
                <a href="#contact" class="block px-3 py-2 text-gray-700 hover:text-indigo-600 text-base font-medium">Kontak</a>
                <div class="pt-4 border-t border-gray-200">
                    <a href="{{ route('login') }}" class="block px-3 py-2 text-indigo-600 text-base font-medium">Masuk</a>
                    <a href="{{ route('register') }}" class="block px-3 py-2 bg-indigo-600 text-white rounded-md text-base font-medium mt-2">Mulai Sekarang</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="pt-16 bg-gradient-to-br from-blue-50 via-cyan-50 to-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <!-- Hero Content -->
                <div class="space-y-8">
                    <div class="space-y-4">
                        <div class="inline-flex items-center px-4 py-2 bg-cyan-100 text-cyan-800 rounded-full text-sm font-medium">
                            <i class="fas fa-shield-alt mr-2"></i>
                            Solusi Profesional Andal
                        </div>
                        <h1 class="text-4xl lg:text-6xl font-bold text-gray-900 leading-tight">
                            Monitoring Kebakaran Hutan
                            <span class="bg-gradient-to-r from-yellow-400 via-orange-500 to-red-600 bg-clip-text text-transparent font-semibold">
                                Real-Time
                            </span>
                        </h1>
                        <p class="text-xl text-gray-600 leading-relaxed">
                            Platform profesional untuk deteksi dini dan monitoring kebakaran hutan di Indonesia. 
                            Analitik prediktif, dan sistem peringatan real-time untuk mitigasi risiko yang efisien.
                        </p>
                    </div>

                    <!-- CTA Buttons -->
                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-8 py-4 bg-gradient-to-r from-blue-500 to-cyan-500 hover:from-blue-600 hover:to-cyan-600 text-white font-semibold rounded-lg transition-all duration-200 transform hover:scale-105 shadow-lg">
                            <i class="fas fa-rocket mr-2"></i>
                            Mulai Uji Coba Gratis
                        </a>
                        <a href="#features" class="inline-flex items-center justify-center px-8 py-4 border-2 border-blue-500 text-blue-600 hover:bg-blue-600 hover:text-white font-semibold rounded-lg transition-all duration-200">
                            <i class="fas fa-play mr-2"></i>
                            Lihat Demo
                        </a>
                    </div>

                    <!-- Trust Indicators -->
                    <div class="grid grid-cols-3 gap-4 pt-8 border-t border-gray-200">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-600" x-data="{ count: 0 }" x-init="
                                let target = 1247;
                                let increment = target / 100;
                                let interval = setInterval(() => {
                                    if (count < target) {
                                        count = Math.min(count + increment, target);
                                    } else {
                                        clearInterval(interval);
                                    }
                                }, 20);
                            " x-text="Math.floor(count).toLocaleString()">0</div>
                            <div class="text-sm text-gray-600">Hotspot Dipantau</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-600" x-data="{ count: 0 }" x-init="
                                let target = 16;
                                let increment = target / 50;
                                let interval = setInterval(() => {
                                    if (count < target) {
                                        count = Math.min(count + increment, target);
                                    } else {
                                        clearInterval(interval);
                                    }
                                }, 30);
                            " x-text="Math.floor(count)">0</div>
                            <div class="text-sm text-gray-600">Provinsi Terdampak</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-600" x-data="{ count: 0 }" x-init="
                                let target = 99.8;
                                let increment = target / 80;
                                let interval = setInterval(() => {
                                    if (count < target) {
                                        count = Math.min(count + increment, target);
                                    } else {
                                        clearInterval(interval);
                                    }
                                }, 25);
                            " x-text="count.toFixed(1) + '%'">0%</div>
                            <div class="text-sm text-gray-600">Akurasi Deteksi</div>
                        </div>
                    </div>
                </div>

                <!-- Hero Image/Dashboard Preview -->
                <div class="relative">
                    <div class="relative bg-white rounded-2xl shadow-2xl p-6 transform rotate-2 hover:rotate-0 transition-transform duration-300">
                        <div class="bg-gradient-to-r from-red-600 via-orange-500 to-yellow-400 rounded-lg p-4 mb-4">
                            <div class="flex items-center justify-between text-white">
                                <div>
                                    <h3 class="font-semibold">Dashboard Monitoring</h3>
                                    <p class="text-sm opacity-90">Real-time Analytics</p>
                                </div>
                                <i class="fas fa-chart-line text-2xl"></i>
                            </div>
                        </div>
                        <!-- Mock Dashboard Content -->
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-red-50 p-3 rounded-lg">
                                    <div class="text-red-600 text-sm font-medium">Alert Tinggi</div>
                                    <div class="text-2xl font-bold text-red-700">23</div>
                                </div>
                                <div class="bg-yellow-50 p-3 rounded-lg">
                                    <div class="text-yellow-600 text-sm font-medium">Alert Sedang</div>
                                    <div class="text-2xl font-bold text-yellow-700">66</div>
                                </div>
                            </div>
                            
                            <div class="bg-gray-100 h-32 rounded-lg flex items-center justify-center">
                                <div class="text-center text-gray-500">
                                    <i class="fas fa-map-marked-alt text-3xl mb-2"></i>
                                    <div class="text-sm">Peta Interaktif</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Floating Elements -->
                    <div class="absolute -top-4 -right-4 bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">
                        <i class="fas fa-check-circle mr-1"></i>
                        Online
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- Features Section -->
    <section id="features" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-4">
                    Platform Monitoring Andal & Efisien
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Analytics, dan sistem peringatan multi-channel untuk perlindungan hutan yang optimal
                </p>
            </div>

            <!-- Responsive 3+2 Card Layout -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
                <!-- Card 1 -->
                <div class="group p-6 bg-white border border-gray-200 rounded-xl hover:shadow-lg hover:border-blue-300 transition-all duration-300 flex flex-col items-center text-center">
                    <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-cyan-500 rounded-lg flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-satellite text-white text-xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Monitoring Satelit Real-Time</h3>
                    <p class="text-gray-600">Deteksi hotspot menggunakan data satelit MODIS dan VIIRS dengan update setiap 15 menit untuk respons yang lebih cepat.</p>
                </div>
                <!-- Card 2 -->
                <div class="group p-6 bg-white border border-gray-200 rounded-xl hover:shadow-lg hover:border-blue-300 transition-all duration-300 flex flex-col items-center text-center">
                    <div class="w-12 h-12 bg-gradient-to-r from-red-500 to-pink-600 rounded-lg flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-bell text-white text-xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Alert System Multi-Channel</h3>
                    <p class="text-gray-600">Sistem peringatan otomatis melalui push notification untuk mendukung respons darurat secara cepat dan efisien.</p>
                </div>
                <!-- Card 3 -->
                <div class="group p-6 bg-white border border-gray-200 rounded-xl hover:shadow-lg hover:border-blue-300 transition-all duration-300 flex flex-col items-center text-center">
                    <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-cyan-500 rounded-lg flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-map-marked-alt text-white text-xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Peta Interaktif GIS</h3>
                    <p class="text-gray-600">Visualisasi real-time dengan layer multiple: hotspot, cuaca, infrastruktur, dan zona risiko tinggi.</p>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 justify-center max-w-3xl mx-auto">
                <!-- Card 4 -->
                <div class="group p-6 bg-white border border-gray-200 rounded-xl hover:shadow-lg hover:border-blue-300 transition-all duration-300 flex flex-col items-center text-center">
                    <div class="w-12 h-12 bg-gradient-to-r from-yellow-500 to-orange-600 rounded-lg flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-chart-line text-2xl text-white"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Advanced Analytics</h3>
                    <p class="text-gray-600">Dashboard analitik mendalam dengan trends, pola musiman, dan insights untuk strategi pencegahan.</p>
                </div>
                <!-- Card 5 -->
                <div class="group p-6 bg-white border border-gray-200 rounded-xl hover:shadow-lg hover:border-blue-300 transition-all duration-300 flex flex-col items-center text-center">
                    <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-blue-500 rounded-lg flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-file-alt text-xl text-white"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Smart Reporting System</h3>
                    <p class="text-gray-600">Laporan otomatis dengan visualisasi data yang dapat diunduh dalam format Excel, mendukung analisis dan dokumentasi secara efisien.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Solutions Section -->
    {{-- Dihapus seluruh bagian Solutions Section --}}
    <!-- End Solutions Section -->

    <!-- Testimonials Section -->
    {{-- Dihapus seluruh bagian Testimonials Section --}}
    <!-- End Testimonials Section -->

    <!-- Security & Compliance Section -->
    <section id="security" class="py-20 bg-gradient-to-br from-gray-50 to-blue-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <div class="inline-flex items-center px-4 py-2 bg-green-100 text-green-800 rounded-full text-sm font-medium mb-4">
                    <i class="fas fa-shield-alt mr-2"></i>
                    Enterprise Security
                </div>
                <h2 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-4">
                    Keamanan & Compliance Tingkat Enterprise
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Memenuhi standar keamanan internasional dengan audit trail lengkap dan compliance yang terintegrasi
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 mb-16">
                <!-- Security Features -->
                <div class="space-y-6">
                    <h3 class="text-2xl font-bold text-gray-900 mb-6">Security Features</h3>
                    
                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-lock text-green-600 text-xl"></i>
                            </div>
                        </div>
                        <div>
                            <h4 class="text-lg font-semibold text-gray-900">End-to-End Encryption</h4>
                            <p class="text-gray-600">AES-256 encryption untuk data at rest dan TLS 1.3 untuk data in transit</p>
                        </div>
                    </div>

                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-user-shield text-blue-600 text-xl"></i>
                            </div>
                        </div>
                        <div>
                            <h4 class="text-lg font-semibold text-gray-900">Multi-Factor Authentication</h4>
                            <p class="text-gray-600">MFA mandatory dengan support TOTP, SMS, dan hardware tokens</p>
                        </div>
                    </div>

                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-network-wired text-purple-600 text-xl"></i>
                            </div>
                        </div>
                        <div>
                            <h4 class="text-lg font-semibold text-gray-900">Network Security</h4>
                            <p class="text-gray-600">VPN support, IP whitelisting, dan private cloud deployment</p>
                        </div>
                    </div>

                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-history text-orange-600 text-xl"></i>
                            </div>
                        </div>
                        <div>
                            <h4 class="text-lg font-semibold text-gray-900">Audit Trail</h4>
                            <p class="text-gray-600">Logging lengkap semua aktivitas dengan retention up to 7 years</p>
                        </div>
                    </div>
                </div>

                <!-- Compliance Badges -->
                <div class="space-y-6">
                    <h3 class="text-2xl font-bold text-gray-900 mb-6">Compliance & Certifications</h3>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-white rounded-lg p-6 shadow-sm border border-gray-200 text-center">
                            <div class="w-16 h-16 bg-blue-100 rounded-lg flex items-center justify-center mx-auto mb-3">
                                <i class="fas fa-certificate text-blue-600 text-2xl"></i>
                            </div>
                            <h4 class="font-semibold text-gray-900">ISO 27001</h4>
                            <p class="text-sm text-gray-600 mt-1">Information Security Management</p>
                        </div>

                        <div class="bg-white rounded-lg p-6 shadow-sm border border-gray-200 text-center">
                            <div class="w-16 h-16 bg-green-100 rounded-lg flex items-center justify-center mx-auto mb-3">
                                <i class="fas fa-shield-alt text-green-600 text-2xl"></i>
                            </div>
                            <h4 class="font-semibold text-gray-900">SOC 2 Type II</h4>
                            <p class="text-sm text-gray-600 mt-1">Security & Availability</p>
                        </div>

                        <div class="bg-white rounded-lg p-6 shadow-sm border border-gray-200 text-center">
                            <div class="w-16 h-16 bg-purple-100 rounded-lg flex items-center justify-center mx-auto mb-3">
                                <i class="fas fa-globe text-purple-600 text-2xl"></i>
                            </div>
                            <h4 class="font-semibold text-gray-900">GDPR</h4>
                            <p class="text-sm text-gray-600 mt-1">Data Protection Regulation</p>
                        </div>

                        <div class="bg-white rounded-lg p-6 shadow-sm border border-gray-200 text-center">
                            <div class="w-16 h-16 bg-indigo-100 rounded-lg flex items-center justify-center mx-auto mb-3">
                                <i class="fas fa-flag text-blue-600 text-2xl"></i>
                            </div>
                            <h4 class="font-semibold text-gray-900">PDPA</h4>
                            <p class="text-sm text-gray-600 mt-1">Indonesia Data Protection</p>
                        </div>
                    </div>

                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg p-6 border border-green-200">
                        <div class="flex items-center mb-3">
                            <i class="fas fa-check-circle text-green-600 text-xl mr-3"></i>
                            <h4 class="font-semibold text-gray-900">99.9% Uptime SLA</h4>
                        </div>
                        <p class="text-gray-600">Guaranteed uptime dengan redundant infrastructure dan disaster recovery</p>
                    </div>
                </div>
            </div>

            <!-- Security Trust Indicators -->
            <div class="bg-white rounded-2xl shadow-lg p-8 border border-gray-200">
                <div class="text-center mb-8">
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Trusted by Government Agencies</h3>
                    <p class="text-gray-600">Platform yang telah dipercaya oleh instansi pemerintah dan BUMN</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="text-center">
                        <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-user-tie text-blue-600 text-2xl"></i>
                        </div>
                        <h4 class="font-semibold text-gray-900 mb-2">Dedicated CSO</h4>
                        <p class="text-gray-600 text-sm">Chief Security Officer untuk enterprise clients</p>
                    </div>

                    <div class="text-center">
                        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-clock text-green-600 text-2xl"></i>
                        </div>
                        <h4 class="font-semibold text-gray-900 mb-2">24/7 Monitoring</h4>
                        <p class="text-gray-600 text-sm">Security operations center dengan real-time threat detection</p>
                    </div>

                    <div class="text-center">
                        <div class="w-20 h-20 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-bug text-purple-600 text-2xl"></i>
                        </div>
                        <h4 class="font-semibold text-gray-900 mb-2">Penetration Testing</h4>
                        <p class="text-gray-600 text-sm">Quarterly security testing oleh third-party experts</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section id="statistics" class="py-20 bg-gradient-to-br from-blue-50 to-cyan-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-4">
                    Dampak Nyata untuk Indonesia
                </h2>
                <p class="text-xl text-gray-600">
                    Data dan pencapaian platform dalam mitigasi kebakaran hutan
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="text-center">
                    <div class="bg-white rounded-xl p-6 shadow-lg">
                        <div class="text-4xl font-bold text-indigo-600 mb-2" x-data="{ count: 0 }" x-init="
                            setTimeout(() => {
                                let target = 1247;
                                let increment = target / 80;
                                let interval = setInterval(() => {
                                    if (count < target) {
                                        count = Math.min(count + increment, target);
                                    } else {
                                        clearInterval(interval);
                                    }
                                }, 30);
                            }, 500);
                        " x-text="Math.floor(count).toLocaleString()">0</div>
                        <div class="text-gray-600 font-medium">Hotspot Terdeteksi</div>
                        <div class="text-sm text-gray-500 mt-1">Bulan ini</div>
                    </div>
                </div>

                <div class="text-center">
                    <div class="bg-white rounded-xl p-6 shadow-lg">
                        <div class="text-4xl font-bold text-green-600 mb-2" x-data="{ count: 0 }" x-init="
                            setTimeout(() => {
                                let target = 76;
                                let increment = target / 60;
                                let interval = setInterval(() => {
                                    if (count < target) {
                                        count = Math.min(count + increment, target);
                                    } else {
                                        clearInterval(interval);
                                    }
                                }, 40);
                            }, 800);
                        " x-text="Math.floor(count) + '%'">0%</div>
                        <div class="text-gray-600 font-medium">Pengurangan Kebakaran</div>
                        <div class="text-sm text-gray-500 mt-1">Dibanding tahun lalu</div>
                    </div>
                </div>

                <div class="text-center">
                    <div class="bg-white rounded-xl p-6 shadow-lg">
                        <div class="text-4xl font-bold text-blue-600 mb-2" x-data="{ count: 0 }" x-init="
                            setTimeout(() => {
                                let target = 5;
                                let increment = target / 40;
                                let interval = setInterval(() => {
                                    if (count < target) {
                                        count = Math.min(count + increment, target);
                                    } else {
                                        clearInterval(interval);
                                    }
                                }, 50);
                            }, 1100);
                        " x-text="'< ' + Math.floor(count) + ' min'">0 min</div>
                        <div class="text-gray-600 font-medium">Response Time</div>
                        <div class="text-sm text-gray-500 mt-1">Rata-rata deteksi</div>
                    </div>
                </div>

                <div class="text-center">
                    <div class="bg-white rounded-xl p-6 shadow-lg">
                        <div class="text-4xl font-bold text-purple-600 mb-2" x-data="{ count: 0 }" x-init="
                            setTimeout(() => {
                                let target = 99.8;
                                let increment = target / 70;
                                let interval = setInterval(() => {
                                    if (count < target) {
                                        count = Math.min(count + increment, target);
                                    } else {
                                        clearInterval(interval);
                                    }
                                }, 35);
                            }, 1400);
                        " x-text="count.toFixed(1) + '%'">0%</div>
                        <div class="text-gray-600 font-medium">Akurasi Sistem</div>
                        <div class="text-sm text-gray-500 mt-1">Validated by ground truth</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-gradient-to-r from-blue-600 to-cyan-600">
        <div class="max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl lg:text-4xl font-bold text-white mb-4">
                Siap untuk Melindungi Hutan Indonesia?
            </h2>
            <p class="text-xl text-indigo-100 mb-8">
                Bergabunglah dengan ratusan organisasi yang mempercayai platform kami untuk monitoring kebakaran hutan
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-8 py-4 bg-white text-blue-600 hover:bg-gray-100 font-semibold rounded-lg transition-all duration-200 transform hover:scale-105">
                    <i class="fas fa-rocket mr-2"></i>
                    Mulai Uji Coba 30 Hari Gratis
                </a>
                <a href="#contact" class="inline-flex items-center justify-center px-8 py-4 border-2 border-white text-white hover:bg-white hover:text-blue-600 font-semibold rounded-lg transition-all duration-200">
                    <i class="fas fa-phone mr-2"></i>
                    Konsultasi Gratis
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contact" class="bg-gray-900 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Company Info -->
                <div class="space-y-4">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-cyan-500 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-fire text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold">Hotspot Vigilance</h3>
                            <p class="text-sm text-gray-400">Professional Edition</p>
                        </div>
                    </div>
                    <p class="text-gray-400">
                        Platform profesional untuk monitoring dan mitigasi kebakaran hutan di Indonesia.
                    </p>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="text-lg font-semibold mb-4">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="#features" class="text-gray-400 hover:text-white transition-colors duration-200">Fitur</a></li>
                        {{-- <li><a href="#solutions" class="text-gray-400 hover:text-white transition-colors duration-200">Solusi</a></li> --}}
                        <li><a href="#security" class="text-gray-400 hover:text-white transition-colors duration-200">Keamanan</a></li>
                        <li><a href="#statistics" class="text-gray-400 hover:text-white transition-colors duration-200">Statistik</a></li>
                        <li><a href="#contact" class="text-gray-400 hover:text-white transition-colors duration-200">Kontak</a></li>
                        <li><a href="{{ route('login') }}" class="text-gray-400 hover:text-white transition-colors duration-200">Login</a></li>
                    </ul>
                </div>

                <!-- Enterprise -->
                <div>
                    <h4 class="text-lg font-semibold mb-4">Enterprise</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors duration-200">Security & Compliance</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors duration-200">Custom Integration</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors duration-200">White Label</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors duration-200">SLA Enterprise</a></li>
                    </ul>
                </div>

                <!-- Contact -->
                <div>
                    <h4 class="text-lg font-semibold mb-4">Kontak</h4>
                    <div class="space-y-2">
                        <div class="flex items-center text-gray-400">
                            <i class="fas fa-envelope mr-2"></i>
                            <span>enterprise@hotspotvigilance.id</span>
                        </div>
                        <div class="flex items-center text-gray-400">
                            <i class="fas fa-phone mr-2"></i>
                            <span>+62 21 xxx-xxxx</span>
                        </div>
                        <div class="flex items-center text-gray-400">
                            <i class="fas fa-map-marker-alt mr-2"></i>
                            <span>Jakarta, Indonesia</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-800 mt-12 pt-8 flex flex-col md:flex-row justify-between items-center">
                <p class="text-gray-400 text-sm">
                    © 2025 Hotspot Vigilance Enterprise. All rights reserved.
                </p>
                <div class="flex space-x-6 mt-4 md:mt-0">
                    <a href="#" class="text-gray-400 hover:text-white transition-colors duration-200">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors duration-200">
                        <i class="fab fa-linkedin"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors duration-200">
                        <i class="fab fa-github"></i>
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scroll to top button -->
    <button 
        @click="window.scrollTo({top: 0, behavior: 'smooth'})"
        class="fixed bottom-6 right-6 bg-indigo-600 hover:bg-indigo-700 text-white p-3 rounded-full shadow-lg transition-all duration-200 transform hover:scale-110"
        x-show="window.pageYOffset > 300"
        x-transition
    >
        <i class="fas fa-chevron-up"></i>
    </button>

    <!-- Live Notification -->
    <div 
        x-show="showNotification"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform translate-y-2"
        x-transition:enter-end="opacity-100 transform translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 transform translate-y-0"
        x-transition:leave-end="opacity-0 transform translate-y-2"
        class="fixed top-20 right-4 max-w-sm bg-white border-l-4 border-orange-500 rounded-lg shadow-lg p-4 z-50"
        x-cloak
    >
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-orange-600 text-sm"></i>
                </div>
            </div>
            <div class="ml-3 flex-1">
                <p class="text-sm font-medium text-gray-900">Live Alert</p>
                <p class="text-sm text-gray-600" x-text="notificationMessage"></p>
                <div class="mt-2 flex space-x-2">
                    <button @click="showNotification = false" class="text-xs text-gray-500 hover:text-gray-700">Dismiss</button>
                    <span class="text-xs text-gray-400">•</span>
                    <span class="text-xs text-gray-500">Real-time detection</span>
                </div>
            </div>
            <button @click="showNotification = false" class="flex-shrink-0 ml-2 text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>
    </div>
    <style>
        [x-cloak] { display: none !important; }
    </style>

    <!-- Status Indicator -->
    <div class="fixed bottom-6 left-6 bg-white rounded-lg shadow-lg p-3 border border-gray-200 z-40">
        <div class="flex items-center space-x-2">
            <div class="flex items-center space-x-1">
                <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                <span class="text-xs font-medium text-gray-700">System Status</span>
            </div>
            <div class="border-l border-gray-300 pl-2">
                <span class="text-xs text-green-600 font-medium" x-text="systemStatus.toUpperCase()">OPERATIONAL</span>
            </div>
        </div>
    </div>

</body>
</html>