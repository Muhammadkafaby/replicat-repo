<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Models\Hotspot;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class HotspotController extends Controller
{
    private $nasaApiKey = '43ef163edea79166510847548934ecdb';
    private $baseUrl = 'https://firms.modaps.eosdis.nasa.gov/api/area/csv';
    
    // Indonesian Hotspot API Configuration
    private $indoHotspotConfig = [
        'API_URL' => 'https://opsroom.sipongidata.my.id/api/opsroom/indoHotspot',
        'wilayah' => 'IN',
        'filterperiode' => false,
        'from' => '',
        'to' => '',
        'late' => 24,
        'satelit' => ['NASA-MODIS', 'NASA-SNPP', 'NASA-NOAA20'],
        'confidence' => ['low', 'medium', 'high'],
        'provinsi' => '',
        'kabkota' => ''
    ];

    /**
     * Get NASA FIRMS hotspot data
     */
    public function getNASAData(Request $request)
    {
        try {
            $source = $request->get('source', 'MODIS_NRT');
            $area = $request->get('area', 'world');
            $dayRange = $request->get('day_range', 1);
            $date = $request->get('date', now()->format('Y-m-d'));
            
            // Create cache key
            $cacheKey = "nasa_firms_{$source}_{$area}_{$dayRange}_{$date}";
            
            // Try to get cached data first (cache for 5 minutes)
            $cachedData = Cache::get($cacheKey);
            if ($cachedData) {
                return response()->json([
                    'success' => true,
                    'data' => $cachedData,
                    'cached' => true,
                    'last_update' => Cache::get($cacheKey . '_timestamp')
                ]);
            }
            
            // Build API URL
            $url = "{$this->baseUrl}/{$this->nasaApiKey}/{$source}/{$area}/{$dayRange}/{$date}";
            
            // Make API request with timeout
            $response = Http::timeout(30)->get($url);
            
            if ($response->successful()) {
                $csvData = $response->body();
                $hotspots = $this->parseCsvData($csvData);
                
                // Filter for Indonesia/Sumatra region
                $filteredHotspots = $this->filterIndonesiaHotspots($hotspots);
                
                // Add additional metadata
                $processedData = [
                    'hotspots' => $filteredHotspots,
                    'total_count' => count($filteredHotspots),
                    'active_count' => count(array_filter($filteredHotspots, fn($h) => $h['confidence'] >= 50)),
                    'high_confidence_count' => count(array_filter($filteredHotspots, fn($h) => $h['confidence'] >= 80)),
                    'source' => $source,
                    'date_range' => $dayRange,
                    'last_update' => now()->toISOString()
                ];
                
                // Cache the results for 5 minutes
                Cache::put($cacheKey, $processedData, 300);
                Cache::put($cacheKey . '_timestamp', now()->format('H:i:s'), 300);
                
                return response()->json([
                    'success' => true,
                    'data' => $processedData,
                    'cached' => false
                ]);
                
            } else {
                throw new \Exception('NASA API request failed: ' . $response->status());
            }
            
        } catch (\Exception $e) {
            \Log::error('NASA FIRMS API Error: ' . $e->getMessage());
            
            // Return fallback data if API fails
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'data' => $this->getFallbackData()
            ], 500);
        }
    }
    
    /**
     * Parse CSV data from NASA FIRMS API
     */
    private function parseCsvData($csvData)
    {
        $lines = explode("\n", trim($csvData));
        $headers = str_getcsv(array_shift($lines));
        $hotspots = [];
        
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            
            $data = str_getcsv($line);
            if (count($data) >= count($headers)) {
                $hotspot = array_combine($headers, $data);
                
                // Convert and validate data
                $hotspots[] = [
                    'id' => md5($hotspot['latitude'] . $hotspot['longitude'] . $hotspot['acq_date'] . $hotspot['acq_time']),
                    'latitude' => (float) $hotspot['latitude'],
                    'longitude' => (float) $hotspot['longitude'],
                    'brightness' => (float) ($hotspot['brightness'] ?? 0),
                    'confidence' => (int) ($hotspot['confidence'] ?? 0),
                    'frp' => (float) ($hotspot['frp'] ?? 0),
                    'acq_date' => $hotspot['acq_date'] ?? now()->format('Y-m-d'),
                    'acq_time' => $hotspot['acq_time'] ?? now()->format('H:i'),
                    'satellite' => $hotspot['satellite'] ?? 'Unknown',
                    'instrument' => $hotspot['instrument'] ?? 'MODIS',
                    'daynight' => $hotspot['daynight'] ?? 'D',
                    'type' => (int) ($hotspot['type'] ?? 0),
                    'severity' => $this->calculateSeverity((int) ($hotspot['confidence'] ?? 0)),
                    'is_active' => ((int) ($hotspot['confidence'] ?? 0)) >= 50,
                    'coordinates_formatted' => number_format((float) $hotspot['latitude'], 4) . ', ' . number_format((float) $hotspot['longitude'], 4)
                ];
            }
        }
        
        return $hotspots;
    }
    
    /**
     * Filter hotspots for Indonesia/Sumatra region
     */
    private function filterIndonesiaHotspots($hotspots)
    {
        return array_filter($hotspots, function($hotspot) {
            $lat = $hotspot['latitude'];
            $lng = $hotspot['longitude'];
            
            // Indonesia bounding box (approximate)
            // Sumatra: lat -6 to 6, lng 95 to 110
            // Java: lat -9 to -5, lng 105 to 115
            // Kalimantan: lat -4 to 5, lng 108 to 119
            return (
                ($lat >= -6 && $lat <= 6 && $lng >= 95 && $lng <= 110) || // Sumatra
                ($lat >= -9 && $lat <= -5 && $lng >= 105 && $lng <= 115) || // Java
                ($lat >= -4 && $lat <= 5 && $lng >= 108 && $lng <= 119) // Kalimantan
            );
        });
    }
    
    /**
     * Calculate severity based on confidence
     */
    private function calculateSeverity($confidence)
    {
        if ($confidence >= 80) return 'high';
        if ($confidence >= 50) return 'medium';
        return 'low';
    }
    
    /**
     * Get fallback data when API fails
     */
    private function getFallbackData()
    {
        return [
            'hotspots' => [],
            'total_count' => 0,
            'active_count' => 0,
            'high_confidence_count' => 0,
            'source' => 'FALLBACK',
            'date_range' => 1,
            'last_update' => now()->toISOString(),
            'fallback' => true
        ];
    }
    
    /**
     * Get dashboard statistics
     */
    public function getStats()
    {
        try {
            // Get cached NASA data or fetch new
            $nasaData = $this->getNASAData(request());
            $data = $nasaData->getData(true);
            
            if ($data['success']) {
                $hotspotsData = $data['data'];
                
                return response()->json([
                    'success' => true,
                    'stats' => [
                        'activeHotspots' => $hotspotsData['active_count'],
                        'alertsToday' => $hotspotsData['high_confidence_count'],
                        'coverage' => 85, // Static for now
                        'responseTime' => 15 // Static for now
                    ],
                    'last_update' => $hotspotsData['last_update']
                ]);
            }
            
            throw new \Exception('Failed to get NASA data');
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'stats' => [
                    'activeHotspots' => 0,
                    'alertsToday' => 0,
                    'coverage' => 0,
                    'responseTime' => 0
                ]
            ], 500);
        }
    }
    
    /**
     * Get recent notifications based on hotspot data
     */
    public function getNotifications()
    {
        try {
            $nasaData = $this->getNASAData(request());
            $data = $nasaData->getData(true);
            
            if ($data['success']) {
                $hotspots = $data['data']['hotspots'];
                $notifications = [];
                
                // Generate notifications from recent high-confidence hotspots
                $highConfidenceHotspots = array_filter($hotspots, fn($h) => $h['confidence'] >= 80);
                $mediumConfidenceHotspots = array_filter($hotspots, fn($h) => $h['confidence'] >= 50 && $h['confidence'] < 80);
                
                foreach (array_slice($highConfidenceHotspots, 0, 3) as $index => $hotspot) {
                    $notifications[] = [
                        'id' => $index + 1,
                        'message' => "High confidence hotspot detected at {$hotspot['coordinates_formatted']}",
                        'time' => Carbon::parse($hotspot['acq_date'] . ' ' . $hotspot['acq_time'])->format('H:i:s'),
                        'severity' => 'high',
                        'type' => 'hotspot',
                        'coordinates' => [$hotspot['latitude'], $hotspot['longitude']]
                    ];
                }
                
                foreach (array_slice($mediumConfidenceHotspots, 0, 2) as $index => $hotspot) {
                    $notifications[] = [
                        'id' => count($notifications) + 1,
                        'message' => "Medium confidence hotspot at {$hotspot['coordinates_formatted']}",
                        'time' => Carbon::parse($hotspot['acq_date'] . ' ' . $hotspot['acq_time'])->format('H:i:s'),
                        'severity' => 'medium',
                        'type' => 'hotspot',
                        'coordinates' => [$hotspot['latitude'], $hotspot['longitude']]
                    ];
                }
                
                // Add system notification
                $notifications[] = [
                    'id' => count($notifications) + 1,
                    'message' => 'NASA FIRMS data successfully updated',
                    'time' => now()->format('H:i:s'),
                    'severity' => 'low',
                    'type' => 'system'
                ];
                
                return response()->json([
                    'success' => true,
                    'notifications' => $notifications
                ]);
            }
            
            throw new \Exception('Failed to get hotspot data');
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'notifications' => []
            ], 500);
        }
    }

    /**
     * Fetch Indonesian Hotspot data and save to database
     */
    public function fetchIndonesianHotspots(Request $request)
    {
        try {
            Log::info('Starting Indonesian Hotspot API fetch and database save');
            
            // Build query parameters
            $params = [
                'wilayah' => $this->indoHotspotConfig['wilayah'],
                'filterperiode' => $this->indoHotspotConfig['filterperiode'],
                'from' => $this->indoHotspotConfig['from'],
                'to' => $this->indoHotspotConfig['to'],
                'late' => $this->indoHotspotConfig['late'],
                'provinsi' => $this->indoHotspotConfig['provinsi'],
                'kabkota' => $this->indoHotspotConfig['kabkota']
            ];
            
            // Add array parameters
            foreach ($this->indoHotspotConfig['satelit'] as $satelit) {
                $params['satelit[]'] = $satelit;
            }
            
            foreach ($this->indoHotspotConfig['confidence'] as $confidence) {
                $params['confidence[]'] = $confidence;
            }
            
            Log::info('Indonesian API Request params:', $params);
            
            // Make API request
            $response = Http::timeout(60)
                ->retry(3, 1000)
                ->get($this->indoHotspotConfig['API_URL'], $params);
            
            if (!$response->successful()) {
                throw new \Exception('API request failed with status: ' . $response->status());
            }
            
            $responseData = $response->json();
            Log::info('Indonesian API Response received', ['data_count' => count($responseData['features'] ?? $responseData['data'] ?? [])]);
            
            // Extract features from GeoJSON or direct data
            $features = $responseData['features'] ?? $responseData['data'] ?? $responseData ?? [];
            
            if (empty($features)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No data received from Indonesian Hotspot API',
                    'data' => []
                ]);
            }
            
            // Process and save data to database
            $savedCount = $this->saveHotspotsToDatabase($features);
            
            // Get latest saved hotspots from database
            $latestHotspots = Hotspot::where('api_source', 'indonesian_hotspot')
                ->orderBy('detection_datetime', 'desc')
                ->take(50)
                ->get();
            
            // Update cache
            Cache::put('indonesian_hotspots_latest', $latestHotspots, now()->addMinutes(5));
            Cache::put('indonesian_hotspots_last_fetch', now(), now()->addDay());
            
            Log::info("Successfully processed Indonesian hotspots", [
                'total_received' => count($features),
                'saved_to_db' => $savedCount
            ]);
            
            return response()->json([
                'success' => true,
                'message' => "Successfully fetched and saved {$savedCount} hotspots to database",
                'data' => $latestHotspots,
                'statistics' => [
                    'total_received' => count($features),
                    'saved_to_database' => $savedCount,
                    'api_source' => 'indonesian_hotspot',
                    'last_update' => now()->toISOString()
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Indonesian Hotspot API Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch Indonesian hotspot data: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Save hotspots to database
     */
    private function saveHotspotsToDatabase($features)
    {
        $savedCount = 0;
        $updatedCount = 0;
        $processedIds = [];
        
        DB::beginTransaction();
        
        try {
            foreach ($features as $feature) {
                // Create hotspot from API data
                $hotspot = Hotspot::createFromApiData($feature);
                
                // Skip if we've already processed this ID in this batch
                if ($hotspot->hs_id && in_array($hotspot->hs_id, $processedIds)) {
                    continue;
                }
                
                // Check if hotspot already exists
                $existingHotspot = null;
                if ($hotspot->hs_id) {
                    $existingHotspot = Hotspot::where('hs_id', $hotspot->hs_id)
                        ->where('api_source', 'indonesian_hotspot')
                        ->first();
                }
                
                // If no hs_id, check by coordinates and time
                if (!$existingHotspot && $hotspot->latitude && $hotspot->longitude) {
                    $existingHotspot = Hotspot::where('latitude', $hotspot->latitude)
                        ->where('longitude', $hotspot->longitude)
                        ->where('acq_date', $hotspot->acq_date)
                        ->where('acq_time', $hotspot->acq_time)
                        ->where('api_source', 'indonesian_hotspot')
                        ->first();
                }
                
                if ($existingHotspot) {
                    // Update existing hotspot
                    $existingHotspot->update([
                        'confidence' => $hotspot->confidence,
                        'brightness' => $hotspot->brightness,
                        'frp' => $hotspot->frp,
                        'severity' => $hotspot->severity,
                        'is_active' => $hotspot->is_active,
                        'raw_data' => $hotspot->raw_data,
                        'last_verified_at' => now()
                    ]);
                    $updatedCount++;
                    Log::info("Updated existing hotspot: {$existingHotspot->id}");
                } else {
                    // Save new hotspot
                    $hotspot->save();
                    $savedCount++;
                    Log::info("Saved new hotspot: {$hotspot->id} at {$hotspot->provinsi}");
                }
                
                if ($hotspot->hs_id) {
                    $processedIds[] = $hotspot->hs_id;
                }
            }
            
            DB::commit();
            Log::info("Successfully processed hotspots", [
                'new_saved' => $savedCount, 
                'updated' => $updatedCount
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error saving hotspots to database: ' . $e->getMessage());
            throw $e;
        }
        
        return $savedCount;
    }

    /**
     * Get hotspots from database
     */
    public function getHotspots(Request $request)
    {
        try {
            $query = Hotspot::query();
            
            // Apply filters
            if ($request->has('province')) {
                $query->byProvince($request->province);
            }
            
            if ($request->has('severity')) {
                $query->where('severity', $request->severity);
            }
            
            if ($request->has('active_only') && $request->active_only) {
                $query->active();
            }
            
            if ($request->has('min_confidence')) {
                $query->byConfidenceLevel($request->min_confidence);
            }
            
            if ($request->has('hours')) {
                $query->recentDetections($request->hours);
            }
            
            // Order by detection time
            $query->orderBy('detection_datetime', 'desc');
            
            // Paginate or limit
            $limit = $request->get('limit', 50);
            $hotspots = $query->take($limit)->get();
            
            return response()->json([
                'success' => true,
                'data' => $hotspots,
                'statistics' => Hotspot::getStatistics(),
                'count' => $hotspots->count()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching hotspots from database: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch hotspots: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Get hotspot statistics from database
     */
    public function getStatistics()
    {
        try {
            $stats = Hotspot::getStatistics();
            
            // Additional statistics
            $stats['by_severity'] = [
                'high' => Hotspot::where('severity', 'high')->count(),
                'medium' => Hotspot::where('severity', 'medium')->count(),
                'low' => Hotspot::where('severity', 'low')->count()
            ];
            
            $stats['by_province'] = Hotspot::select('provinsi', DB::raw('count(*) as count'))
                ->whereNotNull('provinsi')
                ->groupBy('provinsi')
                ->orderBy('count', 'desc')
                ->take(10)
                ->get();
            
            $stats['recent_activity'] = [
                'last_hour' => Hotspot::recentDetections(1)->count(),
                'last_6_hours' => Hotspot::recentDetections(6)->count(),
                'last_24_hours' => Hotspot::recentDetections(24)->count(),
                'last_week' => Hotspot::recentDetections(168)->count()
            ];
            
            return response()->json([
                'success' => true,
                'statistics' => $stats
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error getting hotspot statistics: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export hotspots as CSV
     */
    public function exportCsv(Request $request)
    {
        try {
            $query = Hotspot::query();
            
            // Apply filters similar to getHotspots method
            if ($request->has('province')) {
                $query->byProvince($request->province);
            }
            
            if ($request->has('severity')) {
                $query->where('severity', $request->severity);
            }
            
            if ($request->has('active_only') && $request->active_only) {
                $query->active();
            }
            
            if ($request->has('hours')) {
                $query->recentDetections($request->hours);
            }
            
            $hotspots = $query->orderBy('detection_datetime', 'desc')->get();
            
            // Prepare CSV headers
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="indonesian_hotspots_' . now()->format('Y-m-d_H-i-s') . '.csv"',
            ];
            
            // Create CSV content
            $callback = function() use ($hotspots) {
                $file = fopen('php://output', 'w');
                
                // CSV headers
                fputcsv($file, [
                    'ID', 'HS_ID', 'Latitude', 'Longitude', 'Brightness', 'Confidence', 'FRP',
                    'Detection Date', 'Detection Time', 'Satellite', 'Instrument', 'Severity',
                    'Active', 'Provinsi', 'Kabupaten', 'Kecamatan', 'Desa', 'Kawasan',
                    'Confidence Level', 'API Source', 'Created At'
                ]);
                
                // CSV rows
                foreach ($hotspots as $hotspot) {
                    fputcsv($file, [
                        $hotspot->id,
                        $hotspot->hs_id,
                        $hotspot->latitude,
                        $hotspot->longitude,
                        $hotspot->brightness,
                        $hotspot->confidence,
                        $hotspot->frp,
                        $hotspot->acq_date,
                        $hotspot->acq_time,
                        $hotspot->satellite,
                        $hotspot->instrument,
                        $hotspot->severity,
                        $hotspot->is_active ? 'Yes' : 'No',
                        $hotspot->provinsi,
                        $hotspot->kabupaten,
                        $hotspot->kecamatan,
                        $hotspot->desa,
                        $hotspot->kawasan,
                        $hotspot->confidence_level,
                        $hotspot->api_source,
                        $hotspot->created_at ? $hotspot->created_at->format('Y-m-d H:i:s') : ''
                    ]);
                }
                
                fclose($file);
            };
            
            return response()->stream($callback, 200, $headers);
            
        } catch (\Exception $e) {
            Log::error('Error exporting hotspots CSV: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to export CSV: ' . $e->getMessage()
            ], 500);
        }
    }
}
