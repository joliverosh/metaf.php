# Metaf.php - METAR/SPECI/TAF Decoder Library

## Version Information

- **Library Version**: 1.4.9
- **Build Date**: 2026-03-12
- **Original C++ Library Version**: 5.7.1

## Overview

Metaf.php is a PHP library for decoding aviation weather reports (METAR, SPECI, and TAF). It is a port of the popular [metaf](https://github.com/nnaumenko/metaf) C++ library (version 5.7.1), adapted for PHP 7.4+ environments.

This library provides comprehensive parsing of aviation weather reports, extracting wind information, visibility, cloud layers, temperature, pressure, weather phenomena, and other meteorological data in a structured, easy-to-use format.

## Features

- **Multi-format Support**: Parses METAR, SPECI, and TAF report types
- **Complete Data Extraction**: Wind, visibility, clouds, weather, temperature, pressure
- **Unit Conversions**: Built-in conversion between metric and imperial units
- **Relative Humidity**: Automatic calculation from temperature and dewpoint
- **Trend Parsing**: Handles TAF trend groups (BECMG, TEMPO, PROB, FM)
- **Runway Visual Range (RVR)**: Parses runway-specific visibility data
- **Cloud Types**: Identifies cloud types including CB (Cumulonimbus), TCU (Towering Cumulus)
- **Flight Categories**: Automatic VFR/MVFR/IFR/LIFR classification
- **RMK Remarks Parsing**: Comprehensive parsing of METAR remarks section
- **Error Tolerance**: Handles malformed tokens gracefully with warning flags

## Installation

### Requirements

- PHP 7.4 or higher
- No external dependencies (pure PHP implementation)

### Usage

Simply include the library file in your PHP project:

```php
<?php
require_once 'metaf.php';

use Metaf\Decoder;

$decoder = new Decoder();
$result = $decoder->parse('SKBO 091056Z 36010KT 9999 FEW040TCU 24/22 A2990');
```

## Quick Start

```php
<?php
require_once 'metaf.php';

use Metaf\Decoder;

$decoder = new Decoder();

// Parse a METAR report
$metar = 'SKBO 091056Z 36010KT 9999 FEW040TCU 24/22 A2990';
$result = $decoder->parse($metar);

// Display formatted output
echo $decoder->format($result);
```

Output:
```
Station: SKBO
Type: metar
Time: Day 9 10:56Z
Wind: 36010kt
Visibility: 9999m
Clouds: few at 4000ft
Temperature: 24°C
Dewpoint: 22°C
Relative Humidity: 86.5%
Pressure: 29.9 inHg
```

## API Reference

### Main Decoder Class

#### `Decoder::parse(string $report): array`

Parses a complete METAR, SPECI, or TAF report and returns an associative array containing all extracted data.

**Parameters:**
- `$report` (string): The raw METAR/SPECI/TAF string

**Returns:** Associative array with the following structure:

```php
[
    'type' => 'metar',           // Report type: metar, taf, speci
    'station' => 'SKBO',         // ICAO station identifier
    'is_speci' => false,         // Boolean for SPECI reports
    'is_automated' => false,     // Boolean for automated stations
    'is_amended' => false,       // Boolean for amended reports
    'is_cancelled' => false,      // Boolean for cancelled reports
    'is_nil' => false,           // Boolean for NIL reports
    'observation_time' => [       // Observation time
        'day' => 9,
        'hour' => 10,
        'minute' => 56,
        'is_valid' => true
    ],
    'wind' => [
        'direction' => 360,       // Wind direction in degrees (or 'VRB' for variable)
        'direction_type' => 'degrees',  // degrees, variable, ndv
        'speed' => 10,           // Wind speed
        'gust' => null,          // Gust speed (if present)
        'unit' => 'kt',          // Speed unit
        'malformed' => false,    // True if token was malformed (e.g., VBR instead of VRB)
        'original' => null       // Original token if malformed
    ],
    'visibility' => [
        'prevailing' => 9999,    // Prevailing visibility in meters
        'minimum' => null,        // Minimum visibility (if variable)
        'maximum' => null,        // Maximum visibility (if variable)
        'unit' => 'm'            // Visibility unit
    ],
    'runway_visibility' => [],    // Array of RVR data
    'weather' => [],              // Array of weather phenomena
    'clouds' => [],               // Array of cloud layers
    'temperature' => 24,          // Temperature in Celsius
    'dewpoint' => 22,            // Dewpoint in Celsius
    'pressure' => 29.9,          // Altimeter setting
    'pressure_unit' => 'inhg',    // hpa or inhg
    'trend' => [],               // TAF trend information
    'remarks' => [              // Parsed remarks data
        'automated_station_type' => 'AO2',      // AO1, AO2, AO3
        'sea_level_pressure' => 1008.2,          // SLP in hPa
        'pressure_tendency' => [                  // 5-group pressure tendency
            'characteristic_code' => 3,
            'characteristic' => 'becoming_decreasing',
            'change_mb' => 0.2
        ],
        'precise_temperature' => 16.1,            // T-group temp to 0.1°C
        'precise_dewpoint' => 15.0,               // T-group dewpoint to 0.1°C
        'peak_wind' => [                          // PK WND data
            'direction' => 70,
            'speed' => 45,
            'time_hour' => 17,
            'time_minute' => 52
        ],
        'precipitation' => 0.124,                // P-group in inches
        'ceiling' => [                            // CIG data
            'minimum_ft' => 700,
            'maximum_ft' => 1200
        ],
        'thunderstorm' => [                       // TS data
            'type' => 'began',
            'time_hour' => 18
        ],
        'max_temperature_6h' => 30.6,             // 1-group max temp
        'min_temperature_6h' => 17.2,             // 2-group min temp
        'runway_visibility_remarks' => [],         // RVR in remarks
        'other' => []                             // Unparsed tokens
    ],
    'remarks_raw' => [],       // Raw remarks tokens
    'relative_humidity' => 86.5 // Calculated RH percentage
]
```

#### `Decoder::format(array $data): string`

Converts parsed data into a human-readable string format.

**Parameters:**
- `$data` (array): The parsed result from `Decoder::parse()`

**Returns:** Formatted string representation of the weather data

### Utility Classes

#### Direction Class

Represents wind direction with support for degrees, cardinal directions, and variable winds.

```php
use Metaf\Direction;

$dir = Direction::parse('360');
echo $dir->degrees;      // 360
echo $dir->cardinal;     // N
echo $dir->isVariable;   // false
```

#### Speed Class

Represents wind speed with automatic unit conversion.

```php
use Metaf\Speed;

$speed = Speed::parse('36010KT');
echo $speed->value;                    // 10
echo $speed->unit;                     // knots

// Convert to other units
echo $speed->toUnit('mps');             // 5.14 m/s
echo $speed->toUnit('kmh');            // 18.52 km/h
echo $speed->toUnit('mph');            // 11.51 mph
```

#### Distance Class

Represents visibility and cloud heights with unit conversion support.

```php
use Metaf\Distance;

// Parse visibility in meters
$vis = Distance::parseMeters('9999');
echo $vis->value;              // 9999
echo $vis->toUnit('sm');      // 6.21 statute miles

// Parse cloud height in feet
$height = Distance::parseHeight('040');
echo $height->value;           // 4000 feet
echo $height->toUnit('m');    // 1219.2 meters
```

#### Temperature Class

Represents temperature with relative humidity calculation capability.

```php
use Metaf\Temperature;

// Parse temperature
$temp = Temperature::parse('24');
echo $temp->value;            // 24
echo $temp->isFreezing;       // false
echo $temp->isPrecise;        // false

// Calculate relative humidity
$dewpoint = Temperature::parse('22');
$rh = Temperature::relativeHumidity($temp, $dewpoint);
echo $rh;                     // 86.5%

// Unit conversion
echo $temp->toUnit('F');      // 75.2
```

#### Pressure Class

Represents atmospheric pressure with altimeter setting parsing.

```php
use Metaf\Pressure;

// Parse altimeter setting (inches of mercury)
$pressure = Pressure::parse('A2990');
echo $pressure->value;        // 29.90
echo $pressure->unit;         // inhg

// Convert to hectopascals
echo $pressure->toUnit('hpa'); // 1012.6

// Parse QNH (hectopascals)
$qnh = Pressure::parse('Q1013');
echo $qnh->value;             // 1013
echo $qnh->unit;              // hpa
```

### Parser Classes

#### WeatherPhenomenaParser

Parses weather phenomena codes (30+ phenomena supported).

```php
use Metaf\WeatherPhenomenaParser;

$weather = WeatherPhenomenaParser::parse('+TSRA');
print_r($weather);
// Output:
// [
//     'qualifier' => 'heavy',
//     'descriptor' => 'thunderstorm',
//     'phenomenon' => 'rain'
// ]
```

**Supported Weather Phenomena:**

| Code | Phenomenon | Qualifiers |
|------|------------|------------|
| DZ | Drizzle | Light, Moderate, Heavy |
| RA | Rain | Light, Moderate, Heavy |
| SN | Snow | Light, Moderate, Heavy |
| FG | Fog | Light, Moderate, Heavy |
| BR | Mist | Light, Moderate, Heavy |
| TS | Thunderstorm | With rain, snow, hail |
| +/-
| SH | Showers | Rain, Snow |
| FZ | Freezing | Drizzle, Rain |
| VC | Vicinity | Within 5-10nm |
| RE | Recent | Recent weather |

#### CloudGroupParser

Parses cloud layer information including height and type.

```php
use Metaf\CloudGroupParser;

$cloud = CloudGroupParser::parse('FEW040TCU');
print_r($cloud);
// Output:
// [
//     'type' => 'cloud_layer',
//     'amount' => 'few',
//     'height' => 4000,
//     'height_ft' => 4000,
//     'cloud_type' => 'towering_cumulus'
// ]
```

**Cloud Amounts:**
- `NCD` - No Cloud Detected
- `NSC` - No Significant Cloud
- `CLR` / `SKC` - Clear
- `FEW` - Few (1-2 oktas)
- `SCT` - Scattered (3-4 oktas)
- `BKN` - Broken (5-7 oktas)
- `OVC` - Overcast (8 oktas)

**Cloud Types:**
- `CB` - Cumulonimbus
- `TCU` - Towering Cumulus
- `CU` - Cumulus
- `CF` - Cumulus Fractus
- `SC` - Stratocumulus
- `NS` - Nimbostratus
- `ST` - Stratus

#### MetafTimeParser

Parses observation times and time spans.

```php
use Metaf\MetafTimeParser;

// Parse observation time (DDHHMMZ)
$time = MetafTimeParser::parse('091056Z');
print_r($time);
// Output:
// [
//     'day' => 9,
//     'hour' => 10,
//     'minute' => 56,
//     'is_valid' => true
// ]

// Parse time span for TAF (DDHH/DDHH)
$span = MetafTimeParser::parseTimeSpan('0912/1006');
print_r($span);
// Output:
// [
//     'from_day' => 9,
//     'from_hour' => 12,
//     'until_day' => 10,
//     'until_hour' => 6
// ]
```

### Output Formatter Class

The Formatter class provides convenient methods to format decoded data for display. It simplifies the process of converting raw decoder output into human-readable strings.

```php
use Metaf\Formatter;

// Format a complete report with one call
$formatted = Formatter::formatReport($decoded, $rawTaf, 'taf');

// Or format individual components
$wind = Formatter::formatWind($decoded['wind']);
$visibility = Formatter::formatVisibility($decoded['visibility']);
$weather = Formatter::formatWeather($decoded['weather']);
$clouds = Formatter::formatClouds($decoded['clouds']);
```

#### Formatter::formatReport()

Formats a complete decoded report for display with a single call.

```php
$formatted = Formatter::formatReport($decoded, $rawReport, 'taf');

// Returns:
// [
//     'raw' => 'TAF RAW STRING',
//     'station' => 'SKBO',
//     'type' => 'taf',
//     'wind' => ['direction' => '360°', 'speed' => '10 kt', 'gust' => '', 'raw' => '360° 10 kt'],
//     'visibility' => ['prevailing' => '10+ km (6+ SM)', ...],
//     'weather' => ['Thunderstorm in Vicinity'],
//     'clouds' => [['type' => 'FEW 4000ft cumulonimbus', ...]],
//     'trends' => [...],
//     ...
// ]
```

#### Formatter::formatWind()

Formats wind data for display.

```php
$wind = Formatter::formatWind($decoded['wind']);
// Returns: ['direction' => '360°', 'speed' => '10 kt', 'gust' => 'G25 kt', 'raw' => '360° 10 kt G25 kt']
```

#### Formatter::formatVisibility()

Formats visibility data including directional visibility.

```php
$vis = Formatter::formatVisibility($decoded['visibility']);
// Returns: ['prevailing' => '9999 m (6.2 SM)', 'minimum' => '', 'maximum' => '', 'directional' => [...]]
```

#### Formatter::formatWeather()

Formats weather phenomena including VC (vicinity) phenomena properly.

```php
$weather = Formatter::formatWeather($decoded['weather']);
// Returns: ['Thunderstorm in Vicinity', 'Rain'] instead of raw codes
```

#### Formatter::formatClouds()

Formats cloud layers with types.

```php
$clouds = Formatter::formatClouds($decoded['clouds']);
// Returns: [['type' => 'FEW 4000ft cumulonimbus', 'amount' => 'FEW', 'altitude' => '4000 ft', 'cloud_type' => 'CB']]
```

#### Formatter::formatTafTime()

Extracts and formats TAF time information.

```php
$time = Formatter::formatTafTime($rawTaf);
// Returns: ['issued_at' => '09/10 21:00Z', 'valid_from' => 'Day 09 21:00Z', 'valid_until' => 'Day 10 24:00Z']
```

#### Formatter::formatTrend()

Formats a TAF trend group.

```php
$trend = Formatter::formatTrend($decoded['trend'][0]);
// Returns: ['type' => 'PROB30', 'time' => 'From 19:00', 'wind' => '...', 'visibility' => '...', 'weather' => [...], 'clouds' => [...]]
```

## Examples

### Basic METAR Parsing

```php
<?php
require_once 'metaf.php';

use Metaf\Decoder;

$decoder = new Decoder();

// Colombian airport METAR
$metar = 'SKBO 091056Z 36010KT 9999 FEW040 24/22 A2990';
$result = $decoder->parse($metar);

echo "Station: {$result['station']}\n";
echo "Wind: {$result['wind']['direction']}@{$result['wind']['speed']}kt\n";
echo "Visibility: {$result['visibility']['prevailing']}m\n";
echo "Temperature: {$result['temperature']}°C\n";
echo "Pressure: {$result['pressure']} {$result['pressure_unit']}\n";
```

### METAR with Weather

```php
<?php
require_once 'metaf.php';

use Metaf\Decoder;

$decoder = new Decoder();

// METAR with thunderstorm and rain
$metar = 'SKRG 091200Z 27015G25KT 4000 +TSRA BKN020 18/16 A3000';
$result = $decoder->parse($metar);

// Access weather information
foreach ($result['weather'] as $wx) {
    echo "Weather: ";
    echo $wx['qualifier'] . ' ';
    echo ($wx['descriptor'] ?? '') . ' ';
    echo $wx['phenomenon'] . "\n";
}
```

### METAR with RVR

```php
<?php
require_once 'metaf.php';

use Metaf\Decoder;

$decoder = new Decoder();

// METAR with Runway Visual Range
$metar = 'SKBQ 091056Z 05010KT 1200 R09/1500U FG VV005 18/18 A3000';
$result = $decoder->parse($metar);

// Access RVR data
foreach ($result['runway_visibility'] as $rvr) {
    echo "Runway: {$rvr['runway']}\n";
    echo "Visual Range: {$rvr['visual_range']}m\n";
    echo "Trend: {$rvr['trend']}\n";
}
```

### TAF Parsing

```php
<?php
require_once 'metaf.php';

use Metaf\Decoder;

$decoder = new Decoder();

// TAF with trend
$taf = 'TAF SKBO 091000Z 0910/1006 36010KT 9999 FEW040 BECMG 0912/0914 36015G25KT';
$result = $decoder->parse($taf);

echo "Type: {$result['type']}\n";
echo "Station: {$result['station']}\n";

// Access trend information
foreach ($result['trend'] as $trend) {
    echo "Trend Type: {$trend['type']}\n";
    if (isset($trend['wind'])) {
        echo "Trend Wind: {$trend['wind']['direction']}@{$trend['wind']['speed']}kt\n";
    }
}
```

### Automated METAR

```php
<?php
require_once 'metaf.php';

use Metaf\Decoder;

$decoder = new Decoder();

// Automated station with fog
$metar = 'SKCL 091056Z AUTO 00000KT 0100 FG VV002 12/12 A3010';
$result = $decoder->parse($metar);

echo "Automated: " . ($result['is_automated'] ? 'Yes' : 'No') . "\n";
echo "Temperature: {$result['temperature']}°C\n";
echo "Dewpoint: {$result['dewpoint']}°C\n";
```

### RMK Remarks Parsing

```php
<?php
require_once 'metaf.php';

use Metaf\Decoder;

$decoder = new Decoder();

// METAR with comprehensive RMK section
$metar = 'KLAX 090553Z 11008KT 10SM CLR 17/12 A2983 RMK AO2 SLP098 T01720122 10306 20172 51017 $';
$result = $decoder->parse($metar);

// Access parsed remarks
$remarks = $result['remarks'];

echo "Automated Station: {$remarks['automated_station_type']}\n";
echo "Sea Level Pressure: {$remarks['sea_level_pressure']} hPa\n";
echo "Precise Temperature: {$remarks['precise_temperature']}°C\n";
echo "Precise Dewpoint: {$remarks['precise_dewpoint']}°C\n";
echo "Max Temp 6h: {$remarks['max_temperature_6h']}°C\n";
echo "Min Temp 6h: {$remarks['min_temperature_6h']}°C\n";
echo "Pressure Tendency: {$remarks['pressure_tendency']['characteristic']} ({$remarks['pressure_tendency']['change_mb']} mb)\n";
```

### Flight Category Calculation

```php
<?php
require_once 'metaf.php';

use Metaf\Decoder;

function getFlightCategory($metar) {
    $decoder = new Decoder();
    $result = $decoder->parse($metar);

    // Get ceiling (lowest cloud base)
    $ceiling = null;
    if (!empty($result['clouds'])) {
        foreach ($result['clouds'] as $cloud) {
            if (in_array($cloud['amount'], ['BKN', 'OVC'])) = $cloud[' {
                $heightheight_ft'] ?? 99999;
                if ($ceiling === null || $height < $ceiling) {
                    $ceiling = $height;
                }
            }
        }
    }

    // Get visibility in statute miles
    $visSM = ($result['visibility']['prevailing'] ?? 99999) / 1609.34;

    // Determine flight category
    if ($ceiling && $ceiling < 500 || $visSM < 1) {
        return 'LIFR';
    } elseif ($ceiling && $ceiling < 1000 || $visSM < 3) {
        return 'IFR';
    } elseif ($ceiling && $ceiling < 3000 || $visSM < 5) {
        return 'MVFR';
    } else {
        return 'VFR';
    }
}

// Test
$metar = 'SKBO 091056Z 36010KT 5SM FEW040 24/22 A2990';
echo getFlightCategory($metar); // MVFR
```

## Enumerations

The library provides the following enumeration classes for type-safe operations:

### SpeedUnit

```php
use Metaf\SpeedUnit;

SpeedUnit::KNOTS;           // 'knots'
SpeedUnit::METERS_PER_SECOND; // 'mps'
SpeedUnit::KILOMETERS_PER_HOUR; // 'kmh'
SpeedUnit::MILES_PER_HOUR;  // 'mph'
```

### DistanceUnit

```php
use Metaf\DistanceUnit;

DistanceUnit::METERS;        // 'm'
DistanceUnit::STATUTE_MILES; // 'sm'
DistanceUnit::FEET;          // 'ft'
```

### PressureUnit

```php
use Metaf\PressureUnit;

PressureUnit::HECTOPASCAL;   // 'hpa'
PressureUnit::INCHES_HG;     // 'inhg'
PressureUnit::MM_HG;         // 'mmhg'
```

### TemperatureUnit

```php
use Metaf\TemperatureUnit;

TemperatureUnit::CELSIUS;    // 'C'
TemperatureUnit::FAHRENHEIT; // 'F'
```

## Error Handling

The decoder is designed to be tolerant of malformed input. It will return partial results even if some elements cannot be parsed:

```php
<?php
require_once 'metaf.php';

use Metaf\Decoder;

$decoder = new Decoder();

// Partial/incomplete METAR
$metar = 'SKBO 091056Z 36010KT';
$result = $decoder->parse($metar);

// Still returns available data
echo $result['station'];    // SKBO
echo $result['wind']['speed']; // 10
echo $result['temperature']; // null (not present)
```

## Test Cases

The library includes comprehensive test cases covering all METAR/TAF formats and RMK parsing:

### Basic METAR Tests

| METAR | Expected Result |
|-------|-----------------|
| `SKBO 091056Z 36010KT 9999 FEW040 24/22 A2990` | Station: SKBO, Wind: 360°@10kt, Temp: 24°C |
| `SKRG 091200Z 27015G25KT 4000 +RA BKN020 18/16 A3000` | Wind: 270°@15kt G25kt, Weather: Heavy Rain |
| `SKSP 091300Z 18005KT 9999 TS FEW020CB 26/24 A2985` | Weather: Thunderstorm, Cloud: CB at 2000ft |

### RMK Parsing Tests

| METAR | Expected RMK Fields |
|-------|---------------------|
| `KLAX 090553Z 11008KT 10SM CLR 17/12 A2983 RMK AO2 SLP098 T01720122 10306 20172 51017 $` | AO2, SLP: 1009.8hPa, T: 17.2/12.2°C, Max: 30.6°C, Min: 17.2°C, Tendency: +1.7mb |
| `KSAT 071753Z 07030G45KT 1/2SM R12R/2200V2800FT + RA FG BKN008 BKN014 OVC021 23/23 A2958 RMK AO2 PK WND 07045/1752 SLP999 P0124` | PK WND: 070°@45kt@17:52, SLP: 999.9hPa, Precip: 0.0124in |
| `KSLK 032151Z AUTO 26004KT 4SM -RA BR FEW011 BKN022 OVC047 16/15 A2978 RMK AO2 RAE02B30 SLP082 T01610150 53002` | SLP: 1008.2hPa, T: 16.1/15.0°C, Tendency: 0.2mb |

### TAF Parsing Tests

| TAF | Expected Result |
|-----|-----------------|
| `TAF SKBO 091000Z 0910/1006 36010KT 9999 FEW040 BECMG 0912/0914 36015G25KT` | Trend: BECMG, Wind change to 360°@15kt G25kt |
| `TAF SKRG 091000Z 0910/1006 27010KT 9999 BKN020 PROB30 0915/0918 4000 TSRA BKN015CB` | PROB30: 30% probability thunderstorm rain |

### Pressure Tendency Codes

| Code | Meaning |
|------|---------|
| 0 | Continuously decreasing |
| 1 | Continuously increasing |
| 2 | Continuously steady |
| 3 | Becoming decreasing |
| 4 | Becoming increasing |
| 5 | Becoming steady |
| 6 | Increasing then decreasing |
| 7 | Decreasing then increasing |
| 8 | Fluctuating |

### Run Test Script

```bash
# Run the test script to verify all parsing functionality
php test_metaf.php
php test_rmk.php
```

## Performance Considerations

- The library uses regular expressions for parsing and is optimized for typical METAR/TAF lengths (under 1000 characters)
- For batch processing of multiple reports, instantiate the Decoder once and reuse it
- The library has no external dependencies, minimizing memory overhead

## Differences from Original C++ Library

This PHP port maintains the core functionality of the original metaf library with the following adaptations:

1. **Language-Specific Features**: Uses PHP-native arrays instead of C++ std::variant
2. **Simplified API**: Streamlined for typical PHP use cases
3. **No External Dependencies**: Pure PHP implementation without C++ extensions
4. **Error Handling**: PHP-style nullable returns instead of std::optional

## License

MIT License - See LICENSE file for details

## Credits

- Original C++ library: [metaf](https://github.com/nnaumenko/metaf) by Nick Naumenko
- PHP Port: WX Aeroccidente Development Team

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.4.9 | 2026-03-12 | Enhanced TAF decoding with full trend parsing (BECMG, TEMPO, PROB30/PROB40), temperature forecast extraction (TX/TN), and comprehensive error handling for str_pad() function |
| 1.4.8 | 2026-03-11 | Improved weather phenomena formatting for showers and thunderstorms, added support for all cloud types in trends |
| 1.4.7 | 2026-03-11 | Enhanced cloud formatting with proper altitude display in feet, fixed string casting issues |
| 1.4.6 | 2026-03-10 | Added THUNDERSTORM constant back to WeatherPhenomenon class for backward compatibility, fixed VCTS parsing with string literals |
| 1.4.5 | 2026-03-10 | Added Formatter class with methods: formatWind(), formatVisibility(), formatWeather(), formatClouds(), formatTafTime(), formatTrend(), formatReport() - simplifies output formatting in decoder applications |
| 1.4.4 | 2026-03-10 | Added VC phenomena support (VCTS, VCBR, VCFG, etc.), improved getFullName() for proper "Thunderstorm in Vicinity" display |
| 1.4.3 | 2026-03-10 | Fixed TAF PROB30/PROB40 trends parsing clouds with types (SCT020CB, BKN050TCU, etc.) |
| 1.2.0 | 2026-03-10 | Fixed PROB30/PROB40 without TEMPO/BECMG - creates trend when meteorological data appears, fixed wind direction not showing in trend periods, added handling for malformed VBR wind tokens (treated as VRB with warning flag), added TCU/CB cloud type display support |
| 1.1.2 | 2026-03-09 | Fixed multiple trends parsing - now properly handles consecutive TEMPO/BECMG trends, added weather phenomena parsing in trends (SHRA, DZ, BCFG, etc.) |
| 1.1.1 | 2026-03-09 | Fixed PROB## probability parsing in TAF trends, fixed trend time format detection (DDHH/DDHH, FMHHMM) |
| 1.1.0 | 2026-03-09 | Added RMK remarks parsing (SLP, T-group, pressure tendency, PK WND, precipitation, CIG, TS, max/min temp) |
| 1.0.0 | 2026-03-09 | Initial PHP port from metaf 5.7.1 |
