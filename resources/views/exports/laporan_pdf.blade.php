<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #888; padding: 6px 8px; text-align: left; }
        th { background: #f2f2f2; }
        .header { text-align: center; }
        .timestamp { font-size: 11px; color: #666; margin-top: 4px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $title }}</h2>
        <div class="timestamp">Generated at: {{ $timestamp }}</div>
    </div>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Village</th>
                <th>District</th>
                <th>Regency</th>
                <th>Province</th>
                <th>Confidence</th>
                <th>Date</th>
                <th>Source</th>
                <th>Latitude</th>
                <th>Longitude</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $row)
            <tr>
                <td>{{ $row['id'] }}</td>
                <td>{{ $row['village'] }}</td>
                <td>{{ $row['district'] }}</td>
                <td>{{ $row['regency'] }}</td>
                <td>{{ $row['province'] }}</td>
                <td>{{ $row['confidence'] }}</td>
                <td>{{ $row['date'] }}</td>
                <td>{{ $row['source'] }}</td>
                <td>{{ $row['lat'] }}</td>
                <td>{{ $row['lng'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
