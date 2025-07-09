<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class Hotspot extends Model
{
    use HasFactory;

    protected $fillable = [
        'hs_id',
        'api_source',
        'latitude',
        'longitude',
        'brightness',
        'confidence',
        'frp',
        'acq_date',
        'acq_time',
        'detection_datetime',
        'satellite',
        'instrument',
        'severity',
        'is_active',
        'provinsi',
        'kabupaten',
        'kecamatan',
        'desa',
        'kawasan',
        'confidence_level',
        'raw_data',
        'last_verified_at',
        'is_processed'
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'brightness' => 'decimal:2',
        'confidence' => 'decimal:2',
        'frp' => 'decimal:2',
        'acq_date' => 'date',
        'acq_time' => 'datetime:H:i:s',
        'detection_datetime' => 'datetime',
        'is_active' => 'boolean',
        'is_processed' => 'boolean',
        'raw_data' => 'array',
        'last_verified_at' => 'datetime'
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeHighSeverity($query)
    {
        return $query->where('severity', 'high');
    }

    public function scopeRecentDetections($query, $hours = 24)
    {
        return $query->where('detection_datetime', '>=', Carbon::now()->subHours($hours));
    }

    public function scopeByProvince($query, $province)
    {
        return $query->where('provinsi', 'like', "%{$province}%");
    }

    public function scopeByConfidenceLevel($query, $minConfidence = 60)
    {
        return $query->where('confidence', '>=', $minConfidence);
    }

    // Accessors
    protected function coordinates(): Attribute
    {
        return Attribute::make(
            get: fn () => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude
            ]
        );
    }

    protected function formattedCoordinates(): Attribute
    {
        return Attribute::make(
            get: fn () => number_format($this->latitude, 4) . ', ' . number_format($this->longitude, 4)
        );
    }

    protected function confidenceLevel(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->confidence >= 80) return 'Very High';
                if ($this->confidence >= 60) return 'High';
                if ($this->confidence >= 40) return 'Medium';
                return 'Low';
            }
        );
    }

    protected function timeAgo(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->detection_datetime) return 'Unknown';
                
                $diffInHours = Carbon::now()->diffInHours($this->detection_datetime);
                if ($diffInHours > 24) {
                    $days = floor($diffInHours / 24);
                    return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
                } else if ($diffInHours > 0) {
                    $minutes = Carbon::now()->diffInMinutes($this->detection_datetime) % 60;
                    return $diffInHours . 'h ' . $minutes . 'm ago';
                } else {
                    return Carbon::now()->diffInMinutes($this->detection_datetime) . 'm ago';
                }
            }
        );
    }

    // Helper methods
    public static function createFromApiData($apiData)
    {
        $hotspot = new self();
        
        // Handle GeoJSON feature format
        $properties = $apiData;
        $coordinates = [0, 0];
        
        if (isset($apiData['type']) && $apiData['type'] === 'Feature') {
            $properties = $apiData['properties'] ?? [];
            $coordinates = $apiData['geometry']['coordinates'] ?? [0, 0];
        }
        
        // Map API data to model attributes
        $hotspot->fill([
            'hs_id' => $properties['hs_id'] ?? $properties['id'] ?? null,
            'api_source' => 'indonesian_hotspot',
            'latitude' => $coordinates[1] ?? $properties['lat'] ?? $properties['latitude'] ?? 0,
            'longitude' => $coordinates[0] ?? $properties['long'] ?? $properties['longitude'] ?? 0,
            'brightness' => $properties['brightness'] ?? $properties['bright_ti4'] ?? $properties['bright_ti5'] ?? null,
            'confidence' => $hotspot->parseConfidenceValue($properties['confidence'] ?? $properties['confidence_level'] ?? 0),
            'frp' => $properties['frp'] ?? $properties['fire_radiative_power'] ?? null,
            'satellite' => $properties['sumber'] ?? $properties['satelit'] ?? $properties['satellite'] ?? 'Unknown',
            'instrument' => 'MODIS',
            'provinsi' => $properties['nama_provinsi'] ?? $properties['provinsi'] ?? null,
            'kabupaten' => $properties['kabkota'] ?? $properties['kabupaten'] ?? null,
            'kecamatan' => $properties['kecamatan'] ?? null,
            'desa' => $properties['desa'] ?? null,
            'kawasan' => $properties['kawasan'] ?? null,
            'confidence_level' => $properties['confidence_level'] ?? null,
            'raw_data' => $apiData,
            'is_active' => true,
            'is_processed' => false
        ]);
        
        // Parse date and time
        $dateTime = $hotspot->parseDatetime($properties);
        $hotspot->acq_date = $dateTime['date'];
        $hotspot->acq_time = $dateTime['time'];
        $hotspot->detection_datetime = $dateTime['datetime'];
        
        // Calculate severity
        $hotspot->severity = $hotspot->calculateSeverity();
        
        return $hotspot;
    }

    private function parseConfidenceValue($confidence)
    {
        if (is_string($confidence)) {
            $lowerConf = strtolower($confidence);
            if (in_array($lowerConf, ['high', 'tinggi'])) return 85;
            if (in_array($lowerConf, ['medium', 'sedang'])) return 65;
            if (in_array($lowerConf, ['low', 'rendah'])) return 45;
            return floatval($confidence) ?: 50;
        }
        return floatval($confidence) ?: 50;
    }

    private function parseDatetime($properties)
    {
        $dateStr = $properties['date_hotspot_ori'] ?? $properties['date_hotspot'] ?? $properties['acq_date'] ?? null;
        
        if ($dateStr) {
            try {
                $dateObj = Carbon::parse($dateStr);
                return [
                    'date' => $dateObj->toDateString(),
                    'time' => $dateObj->toTimeString(),
                    'datetime' => $dateObj
                ];
            } catch (\Exception $e) {
                // Fall back to current time if parsing fails
            }
        }
        
        $now = Carbon::now();
        return [
            'date' => $now->toDateString(),
            'time' => $now->toTimeString(),
            'datetime' => $now
        ];
    }

    private function calculateSeverity()
    {
        $confScore = $this->confidence >= 80 ? 3 : ($this->confidence >= 60 ? 2 : 1);
        $brightScore = $this->brightness >= 350 ? 3 : ($this->brightness >= 320 ? 2 : 1);
        $frpScore = $this->frp >= 50 ? 3 : ($this->frp >= 20 ? 2 : 1);
        
        $totalScore = $confScore + $brightScore + $frpScore;
        
        if ($totalScore >= 8) return 'high';
        if ($totalScore >= 5) return 'medium';
        return 'low';
    }

    // Static methods for data analysis
    public static function getStatistics()
    {
        return [
            'total' => static::count(),
            'active' => static::active()->count(),
            'high_severity' => static::highSeverity()->count(),
            'recent_24h' => static::recentDetections(24)->count(),
            'provinces_count' => static::distinct('provinsi')->count('provinsi'),
            'avg_confidence' => static::avg('confidence')
        ];
    }
}
