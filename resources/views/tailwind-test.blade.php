<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tailwind CSS Test</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full">
    <div class="min-h-full flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div class="text-center">
                <h1 class="text-4xl font-bold text-gray-900 mb-4">
                    🎉 Tailwind CSS Berhasil Diinstal!
                </h1>
                <p class="text-lg text-gray-600 mb-8">
                    Semua konfigurasi sudah siap untuk digunakan
                </p>
                <div class="space-y-4">
                    <button class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg transition-colors duration-200">
                        Primary Button
                    </button>
                    <button class="w-full bg-gray-600 hover:bg-gray-700 text-white font-medium py-3 px-4 rounded-lg transition-colors duration-200">
                        Secondary Button
                    </button>
                </div>
                <div class="mt-8 p-6 bg-white rounded-lg shadow-sm border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Card Component</h3>
                    <p class="text-gray-600">Ini adalah contoh card yang dibuat dengan Tailwind CSS</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
