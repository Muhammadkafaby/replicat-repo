<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - Hotspot Vigilance</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100">
<div class="flex min-h-screen bg-gray-100">
    <!-- SIDEBAR -->
    <aside class="flex flex-col w-64 bg-white border-r border-gray-200 min-h-screen">
        <!-- Logo & App Name -->
        <div class="flex items-center h-20 px-6 border-b border-gray-100">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-fire text-white text-xl"></i>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-gray-900">Hotspot Vigilance</h1>
                    <p class="text-xs text-blue-600 font-medium">Monitoring</p>
                </div>
            </div>
        </div>
        <!-- Sidebar Navigation -->
        <nav class="flex-1 py-6 px-4 space-y-2">
            <a href="/dashboard" class="flex items-center px-4 py-3 rounded-lg font-semibold text-sm transition {{ Request::is('dashboard') ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-blue-50 hover:text-blue-700' }}">
                <i class="fas fa-chart-line mr-3"></i>
                Dashboard
            </a>
            <a href="/peta" class="flex items-center px-4 py-3 rounded-lg font-semibold text-sm transition {{ Request::is('peta') ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-blue-50 hover:text-blue-700' }}">
                <i class="fas fa-map-marked-alt mr-3"></i>
                Peta Interaktif
            </a>
            <a href="/analitik" class="flex items-center px-4 py-3 rounded-lg font-semibold text-sm transition {{ Request::is('analitik') ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-blue-50 hover:text-blue-700' }}">
                <i class="fas fa-chart-bar mr-3"></i>
                Analytics
            </a>
            <a href="/laporan" class="flex items-center px-4 py-3 rounded-lg font-semibold text-sm transition {{ Request::is('laporan') ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-blue-50 hover:text-blue-700' }}">
                <i class="fas fa-file-alt mr-3"></i>
                Laporan
            </a>
        </nav>
        <!-- Sidebar User Profile -->
        <div class="border-t border-gray-100 px-6 py-4 flex items-center space-x-3">
            <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center shadow-lg">
                <i class="fas fa-user text-white text-sm"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-900 truncate">{{ Auth::user()->name ?? 'asep' }}</p>
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

    <!-- MAIN CONTENT -->
    <div class="flex-1 flex flex-col min-h-screen">
        <!-- Navbar -->
        <header class="sticky top-0 z-30 flex items-center h-20 bg-white border-b border-gray-200 shadow px-6">
            <div class="flex-1 flex items-center">
                <nav class="flex items-center space-x-2">
                    <i class="fas fa-user text-blue-500"></i>
                    <span class="text-gray-800 font-semibold ml-2">Profil Saya</span>
                </nav>
                <span class="text-xs ml-4 text-green-600 flex items-center">
                    <span class="w-2 h-2 rounded-full bg-green-500 mr-1 animate-pulse"></span>Live Data
                </span>
                <span class="text-xs ml-2 text-gray-400">Last update: {{ now()->format('H.i.s') }}</span>
            </div>
            <div class="flex items-center gap-4">
                <button class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition">
                    <i class="fas fa-sync-alt"></i>
                </button>
                <button class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition">
                    <i class="fas fa-bell"></i>
                </button>
                <div class="relative flex items-center">
                    <button class="w-9 h-9 bg-blue-100 hover:bg-blue-200 transition rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-blue-600"></i>
                    </button>
                    <span class="ml-2 text-gray-900 font-semibold">{{ Auth::user()->name ?? 'asep' }}</span>
                </div>
            </div>
        </header>

        <!-- Profile Content -->
        <main class="flex-1 p-8 bg-gray-100">
            <div class="max-w-5xl mx-auto">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-4xl font-extrabold text-gray-900 mb-1">Profil Saya</h1>
                    <p class="text-lg text-gray-500">Kelola informasi akun dan data pribadi Anda</p>
                </div>
                <!-- Card Profile -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-10">
                    <div class="col-span-1 flex flex-col items-center">
                        <div class="w-36 h-36 rounded-full border-4 border-blue-200 shadow-lg mb-4 overflow-hidden bg-white flex items-center justify-center">
                            <img src="{{ Auth::user()->photo_url ?? asset('images/default-avatar.png') }}" alt="Profile Photo" class="object-cover w-full h-full">
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-1">{{ Auth::user()->name }}</h2>
                        <div class="flex gap-2 mb-2">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">
                                <i class="fas fa-user-circle mr-1"></i>
                                {{ Auth::user()->role ?? 'User' }}
                            </span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <i class="fas fa-check-circle mr-1"></i> Aktif
                            </span>
                        </div>
                        <p class="text-gray-600 text-sm mb-1 flex items-center justify-center">
                            <i class="fas fa-envelope mr-2 text-blue-400"></i>{{ Auth::user()->email }}
                        </p>
                        <p class="text-xs text-blue-700 mt-1 flex items-center justify-center">
                            <i class="fas fa-clock mr-1"></i> Bergabung: {{ Auth::user()->created_at->format('d M Y') }}
                        </p>
                    </div>
                    <div class="col-span-2">
                        <div class="bg-white border border-blue-200 rounded-2xl shadow-md px-8 py-10">
                            <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-3">
                                <i class="fas fa-user-edit text-blue-500"></i> Edit Profil
                            </h3>
                            <form action="{{ route('profile.update') }}" method="POST" class="space-y-8">
                                @csrf
                                @method('PUT')
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama</label>
                                        <input type="text" name="name" value="{{ Auth::user()->name }}"
                                            class="block w-full rounded-lg border-blue-200 focus:ring-blue-500 focus:border-blue-500 px-4 py-2 bg-gray-50 transition shadow-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                        <input type="email" name="email" value="{{ Auth::user()->email }}"
                                            class="block w-full rounded-lg border-blue-200 focus:ring-blue-500 focus:border-blue-500 px-4 py-2 bg-gray-50 transition shadow-sm">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi Profil</label>
                                    <textarea name="profile_description" rows="5"
                                        class="block w-full rounded-lg border-blue-200 focus:ring-blue-500 focus:border-blue-500 px-4 py-2 bg-gray-50 transition shadow-sm"
                                    >{{ Auth::user()->profile_description }}</textarea>
                                </div>
                                <div class="flex justify-end">
                                    <button type="submit"
                                            class="inline-flex items-center px-8 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 shadow-lg transition font-semibold">
                                        <i class="fas fa-save mr-2"></i> Simpan Perubahan
                                    </button>
                                </div>
                            </form>
                        </div>
                        <!-- Deskripsi Card -->
                        <div class="mt-6 bg-white border border-blue-100 rounded-2xl shadow px-6 py-5">
                            <div class="flex items-center gap-2 mb-2">
                                <i class="fas fa-info-circle text-blue-400"></i>
                                <span class="font-semibold text-gray-700 text-sm">Deskripsi Profil</span>
                            </div>
                            <p class="text-gray-700 text-sm">
                                {{ Auth::user()->profile_description ?? 'Belum ada deskripsi profil.' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>