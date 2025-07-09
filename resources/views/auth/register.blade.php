<!DOCTYPE html>
<html lang="en" class="h-full bg-gradient-to-br from-blue-50 to-indigo-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Registrasi - Hotspot Vigilance</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="h-full" x-data="{ 
    showPassword: false, 
    showConfirmPassword: false,
    
    submitForm() {
        // Debug form data
        const formData = new FormData(document.querySelector('form'));
        console.log('Form data being submitted:');
        for (let [key, value] of formData.entries()) {
            console.log(key, value);
        }
        return true; // Allow form submission
    }
}">
    <div class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <!-- Logo and Title -->
            <div class="flex flex-col items-center">
                <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-user-plus text-white text-2xl"></i>
                </div>
                <h2 class="text-center text-3xl font-bold text-gray-900">
                    Buat Akun Baru
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Bergabunglah dengan Hotspot Vigilance
                </p>
            </div>
        </div>        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-4 shadow-xl sm:rounded-lg sm:px-10 border border-gray-200">
                
                <!-- Display any session errors -->
                @if($errors->any())
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-md">
                        <div class="text-sm text-red-600">
                            <ul class="list-disc list-inside">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                <!-- Display success message -->
                @if(session('success'))
                    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-md">
                        <div class="text-sm text-green-600">
                            {{ session('success') }}
                        </div>
                    </div>
                @endif
                
                <form method="POST" action="{{ route('register') }}" class="space-y-6" @submit="submitForm()">
                    @csrf

                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">
                            Nama Lengkap
                        </label>
                        <div class="mt-1 relative">
                            <input 
                                id="name" 
                                name="name" 
                                type="text" 
                                value="{{ old('name') }}"
                                required 
                                autofocus
                                autocomplete="name"
                                class="appearance-none block w-full px-3 py-2 pl-10 border @error('name') border-red-300 @else border-gray-300 @enderror rounded-md placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                placeholder="Masukkan nama lengkap"
                            >
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                        </div>
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email Address -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">
                            Alamat Email
                        </label>
                        <div class="mt-1 relative">
                            <input 
                                id="email" 
                                name="email" 
                                type="email" 
                                value="{{ old('email') }}"
                                required 
                                autocomplete="email"
                                class="appearance-none block w-full px-3 py-2 pl-10 border @error('email') border-red-300 @else border-gray-300 @enderror rounded-md placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                placeholder="contoh@email.com"
                            >
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-gray-400"></i>
                            </div>
                        </div>
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Phone Number -->
                    <div>
                        <label for="phone_number" class="block text-sm font-medium text-gray-700">
                            Nomor Telepon
                        </label>
                        <div class="mt-1 relative">
                            <input 
                                id="phone_number" 
                                name="phone_number" 
                                type="tel" 
                                value="{{ old('phone_number') }}"
                                required 
                                autocomplete="tel"
                                class="appearance-none block w-full px-3 py-2 pl-10 border @error('phone_number') border-red-300 @else border-gray-300 @enderror rounded-md placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                placeholder="08xxxxxxxxxx"
                            >
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-phone text-gray-400"></i>
                            </div>
                        </div>
                        @error('phone_number')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">
                            Password
                        </label>                        <div class="mt-1 relative">
                            <input 
                                id="password" 
                                name="password" 
                                type="password"
                                x-bind:type="showPassword ? 'text' : 'password'"
                                required
                                autocomplete="new-password"
                                class="appearance-none block w-full px-3 py-2 pl-10 pr-10 border @error('password') border-red-300 @else border-gray-300 @enderror rounded-md placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                placeholder="Minimal 8 karakter"
                            >
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <button 
                                type="button" 
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center"
                            >
                                <i class="fas" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'" class="text-gray-400 hover:text-gray-600"></i>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                            Konfirmasi Password
                        </label>                        <div class="mt-1 relative">
                            <input 
                                id="password_confirmation" 
                                name="password_confirmation" 
                                type="password"
                                x-bind:type="showConfirmPassword ? 'text' : 'password'"
                                required
                                autocomplete="new-password"
                                class="appearance-none block w-full px-3 py-2 pl-10 pr-10 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                placeholder="Ulangi password"
                            >
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <button 
                                type="button" 
                                @click="showConfirmPassword = !showConfirmPassword"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center"
                            >
                                <i class="fas" :class="showConfirmPassword ? 'fa-eye-slash' : 'fa-eye'" class="text-gray-400 hover:text-gray-600"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div>
                        <button 
                            type="submit" 
                            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-gradient-to-r from-indigo-500 to-blue-600 hover:from-indigo-600 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200 transform hover:scale-105"
                        >
                            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                                <i class="fas fa-user-plus text-indigo-300 group-hover:text-indigo-200"></i>
                            </span>
                            Buat Akun
                        </button>
                    </div>
                </form>

                <!-- Login Link -->
                <div class="mt-6">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-white text-gray-500">Sudah punya akun?</span>
                        </div>
                    </div>

                    <div class="mt-6">
                        <a 
                            href="{{ route('login') }}" 
                            class="w-full flex justify-center py-2 px-4 border border-indigo-300 rounded-md text-sm font-medium text-indigo-600 bg-white hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200"
                        >
                            Masuk ke akun
                        </a>
                    </div>
                </div>

                <!-- Back to Home -->
                <div class="mt-6 text-center">
                    <a 
                        href="{{ route('home') }}" 
                        class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 transition-colors duration-200"
                    >
                        <i class="fas fa-arrow-left mr-2"></i>
                        Kembali ke Beranda
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
