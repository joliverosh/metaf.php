<?php
/**
 * Metaf.php - METAR/SPECI/TAF Decoder
 *
 * PHP port of the metaf C++ library (https://github.com/nnaumenko/metaf)
 * Original library version: 5.7.1
 *
 * This library provides comprehensive parsing of METAR, SPECI, and TAF
 * aviation weather reports, extracting wind, visibility, clouds, temperature,
 * pressure, weather phenomena, and other meteorological information.
 *
 * @version 1.4.8
 * @author WX Aeroccidente Development Team
 * @license MIT
 *
 * Usage:
 *   $decoder = new Metaf\Decoder();
 *   $result = decoder->parse('SKBO 091056Z 36010KT 9999 FEW040TCU 24/22 A2990');
 *   echo $result['station'];
 *   echo $result['wind']['direction'];
 *   echo $result['wind']['speed'];
 */

namespace Metaf {

// Library version
define('METAF_LIBRARY_VERSION', '1.4.9');
define('METAF_LIBRARY_BUILD', '20260312');

/**
 * Speed unit enumeration
 */
class SpeedUnit {
    const KNOTS = 'knots';
    const METERS_PER_SECOND = 'mps';
    const KILOMETERS_PER_HOUR = 'kmh';
    const MILES_PER_HOUR = 'mph';
}

/**
 * Distance unit enumeration
 */
class DistanceUnit {
    const METERS = 'm';
    const STATUTE_MILES = 'sm';
    const FEET = 'ft';
}

/**
 * Pressure unit enumeration
 */
class PressureUnit {
    const HECTOPASCAL = 'hpa';
    const INCHES_HG = 'inhg';
    const MM_HG = 'mmhg';
}

/**
 * Temperature unit enumeration
 */
class TemperatureUnit {
    const CELSIUS = 'C';
    const FAHRENHEIT = 'F';
}

/**
 * Cloud amount enumeration
 */
class CloudAmount {
    const NOT_REPORTED = 'not_reported';
    const NCD = 'ncd';
    const NSC = 'nsc';
    const NONE_CLR = 'none_clr';
    const NONE_SKC = 'none_skc';
    const FEW = 'few';
    const SCATTERED = 'scattered';
    const BROKEN = 'broken';
    const OVERCAST = 'overcast';
    const OBSCURED = 'obscured';
}

/**
 * Weather qualifier enumeration
 */
class WeatherQualifier {
    const NONE = 'none';
    const RECENT = 'recent';
    const VICINITY = 'vicinity';
    const LIGHT = 'light';
    const MODERATE = 'moderate';
    const HEAVY = 'heavy';
}

/**
 * Weather descriptor enumeration
 */
class WeatherDescriptor {
    const NONE = 'none';
    const SHALLOW = 'shallow';
    const PARTIAL = 'partial';
    const PATCHES = 'patches';
    const LOW_DRIFTING = 'low_drifting';
    const BLOWING = 'blowing';
    const SHOWERS = 'showers';
    const THUNDERSTORM = 'thunderstorm';
    const FREEZING = 'freezing';
}

/**
 * Weather phenomena enumeration
 */
class WeatherPhenomenon {
    const NOT_REPORTED = 'not_reported';
    const DRIZZLE = 'drizzle';
    const RAIN = 'rain';
    const SNOW = 'snow';
    const SNOW_GRAINS = 'snow_grains';
    const ICE_CRYSTALS = 'ice_crystals';
    const ICE_PELLETS = 'ice_pellets';
    const HAIL = 'hail';
    const SMALL_HAIL = 'small_hail';
    const UNDETERMINED = 'undetermined';
    const MIST = 'mist';
    const FOG = 'fog';
    const SMOKE = 'smoke';
    const VOLCANIC_ASH = 'volcanic_ash';
    const DUST = 'dust';
    const SAND = 'sand';
    const HAZE = 'haze';
    const SPRAY = 'spray';
    const TORNADO = 'tornado';
    const WATERSPOUT = 'waterspout';
    const DUST_WHIRLS = 'dust_whirls';
    const SQUALLS = 'squalls';
    const FUNNEL_CLOUD = 'funnel_cloud';
    const SANDSTORM = 'sandstorm';
    const DUSTSTORM = 'duststorm';
    const THUNDERSTORM = 'thunderstorm';
}

/**
 * Cloud type enumeration
 */
class CloudType {
    const NOT_REPORTED = 'not_reported';
    const CUMULONIMBUS = 'cumulonimbus';
    const TOWERING_CUMULUS = 'towering_cumulus';
    const CUMULUS = 'cumulus';
    const CUMULUS_FRACTUS = 'cumulus_fractus';
    const STRATOCUMULUS = 'stratocumulus';
    const NIMBOSTRATUS = 'nimbostratus';
    const STRATUS = 'stratus';
    const STRATUS_FRACTUS = 'stratus_fractus';
    const ALTOSTRATUS = 'altostratus';
    const ALTOCUMULUS = 'altocumulus';
    const ALTOCUMULUS_CASTELLANUS = 'altocumulus_castellanus';
    const CIRRUS = 'cirrus';
    const CIRROSTRATUS = 'cirrostratus';
    const CIRROCUMULUS = 'cirrocumulus';
}

/**
 * Report type enumeration
 */
class ReportType {
    const UNKNOWN = 'unknown';
    const METAR = 'metar';
    const TAF = 'taf';
}

/**
 * Report error enumeration
 */
class ReportError {
    const NONE = 'none';
    const EMPTY_REPORT = 'empty_report';
    const EXPECTED_REPORT_TYPE_OR_LOCATION = 'expected_report_type_or_location';
    const EXPECTED_LOCATION = 'expected_location';
    const EXPECTED_REPORT_TIME = 'expected_report_time';
    const EXPECTED_TIME_SPAN = 'expected_time_span';
    const UNEXPECTED_REPORT_END = 'unexpected_report_end';
    const UNEXPECTED_GROUP_AFTER_NIL = 'unexpected_group_after_nil';
    const UNEXPECTED_GROUP_AFTER_CNL = 'unexpected_group_after_cnl';
    const UNEXPECTED_NIL_OR_CNL_IN_REPORT_BODY = 'unexpected_nil_or_cnl_in_report_body';
    const AMD_ALLOWED_IN_TAF_ONLY = 'amd_allowed_in_taf_only';
    const CNL_ALLOWED_IN_TAF_ONLY = 'cnl_allowed_in_taf_only';
    const MAINTENANCE_INDICATOR_ALLOWED_IN_METAR_ONLY = 'maintenance_indicator_allowed_in_metar_only';
    const REPORT_TOO_LARGE = 'report_too_large';
}

/**
 * Direction class for wind and cloud directions
 */
class Direction {
    public $type;
    public $degrees;
    public $cardinal;
    public $isVariable;
    public $isNDV;

    public function __construct() {
        $this->type = 'not_reported';
        $this->degrees = null;
        $this->cardinal = null;
        $this->isVariable = false;
        $this->isNDV = false;
    }

    /**
     * Parse direction from string (e.g., "360", "VRB", "000M00")
     */
    public static function parse(string $str): Direction {
        $dir = new Direction();

        // Variable wind direction
        if ($str === 'VRB') {
            $dir->type = 'variable';
            $dir->isVariable = true;
            return $dir;
        }

        // No directional variation
        if ($str === 'NDV' || $str === 'N') {
            $dir->type = 'ndv';
            $dir->isNDV = true;
            return $dir;
        }

        // Parse degrees
        if (preg_match('/^(\d{3})$/', $str, $matches)) {
            $dir->type = 'degrees';
            $dir->degrees = (int)$matches[1];
            $dir->cardinal = self::degreesToCardinal($dir->degrees);
            return $dir;
        }

        // Cardinal directions
        $cardinals = [
            'N' => 0, 'NNE' => 22.5, 'NE' => 45, 'ENE' => 67.5,
            'E' => 90, 'ESE' => 112.5, 'SE' => 135, 'SSE' => 157.5,
            'S' => 180, 'SSW' => 202.5, 'SW' => 225, 'WSW' => 247.5,
            'W' => 270, 'WNW' => 292.5, 'NW' => 315, 'NNW' => 337.5
        ];

        if (isset($cardinals[strtoupper($str)])) {
            $dir->type = 'cardinal';
            $dir->degrees = $cardinals[strtoupper($str)];
            $dir->cardinal = strtoupper($str);
            return $dir;
        }

        return $dir;
    }

    private static function degreesToCardinal(int $degrees): string {
        $cardinals = ['N', 'NNE', 'NE', 'ENE', 'E', 'ESE', 'SE', 'SSE',
                      'S', 'SSW', 'SW', 'WSW', 'W', 'WNW', 'NW', 'NNW'];
        $index = round($degrees / 22.5) % 16;
        return $cardinals[$index];
    }
}

/**
 * Speed class for wind speed
 */
class Speed {
    public $value;
    public $unit;

    public function __construct(int $value = 0, string $unit = SpeedUnit::KNOTS) {
        $this->value = $value;
        $this->unit = $unit;
    }

    /**
     * Parse speed from string
     */
    public static function parse(string $str, string $unit = SpeedUnit::KNOTS): ?Speed {
        if (preg_match('/^(\d{2,3})(G(\d{2,3}))?KT$/', $str, $matches)) {
            return new Speed((int)$matches[1], SpeedUnit::KNOTS);
        }

        if (preg_match('/^(\d{1,2})MPS$/', $str, $matches)) {
            return new Speed((int)$matches[1], SpeedUnit::METERS_PER_SECOND);
        }

        if (preg_match('/^(\d{1,2})KMH$/', $str, $matches)) {
            return new Speed((int)$matches[1], SpeedUnit::KILOMETERS_PER_HOUR);
        }

        return null;
    }

    /**
     * Convert to different unit
     */
    public function toUnit(string $unit): float {
        // First convert to knots
        $knots = $this->value;
        switch ($this->unit) {
            case SpeedUnit::METERS_PER_SECOND:
                $knots = $this->value * 1.94384;
                break;
            case SpeedUnit::KILOMETERS_PER_HOUR:
                $knots = $this->value * 0.539957;
                break;
            case SpeedUnit::MILES_PER_HOUR:
                $knots = $this->value * 0.868976;
                break;
        }

        // Then convert to target unit
        switch ($unit) {
            case SpeedUnit::METERS_PER_SECOND:
                return $knots * 0.514444;
            case SpeedUnit::KILOMETERS_PER_HOUR:
                return $knots * 1.852;
            case SpeedUnit::MILES_PER_HOUR:
                return $knots * 1.15078;
            default:
                return $knots;
        }
    }
}

/**
 * Distance class for visibility and cloud heights
 */
class Distance {
    public $value;
    public $unit;
    public $modifier; // less_than, more_than, distant, vicinity

    public function __construct(float $value = 0, string $unit = DistanceUnit::METERS) {
        $this->value = $value;
        $this->unit = $unit;
        $this->modifier = 'none';
    }

    /**
     * Parse distance from string (visibility in meters)
     */
    public static function parseMeters(string $str): ?Distance {
        // 4-digit meters (e.g., "9999", "0500")
        if (preg_match('/^(\d{4})$/', $str, $matches)) {
            $dist = new Distance((float)$matches[1], DistanceUnit::METERS);
            return $dist;
        }

        // 4-digit with less than (e.g., "M1000")
        if (preg_match('/^M(\d{4})$/', $str, $matches)) {
            $dist = new Distance((float)$matches[1], DistanceUnit::METERS);
            $dist->modifier = 'less_than';
            return $dist;
        }

        // 6-digit meters (e.g., "010000" = 10km)
        if (preg_match('/^(\d{6})$/', $str, $matches)) {
            $dist = new Distance((float)$matches[1], DistanceUnit::METERS);
            return $dist;
        }

        return null;
    }

    /**
     * Parse distance from string (visibility in statute miles)
     */
    public static function parseMiles(string $str): ?Distance {
        // Integer + fraction (e.g., "1 1/2SM", "3/4SM")
        if (preg_match('/^(\d+)\s+(\d+)\/(\d+)SM$/', $str, $matches)) {
            $value = (float)$matches[1] + (float)$matches[2] / (float)$matches[3];
            return new Distance($value, DistanceUnit::STATUTE_MILES);
        }

        // Just fraction (e.g., "1/4SM")
        if (preg_match('/^(\d+)\/(\d+)SM$/', $str, $matches)) {
            $value = (float)$matches[1] / (float)$matches[2];
            return new Distance($value, DistanceUnit::STATUTE_MILES);
        }

        // Integer only (e.g., "1SM", "10SM")
        if (preg_match('/^(\d+)SM$/', $str, $matches)) {
            return new Distance((float)$matches[1], DistanceUnit::STATUTE_MILES);
        }

        return null;
    }

    /**
     * Parse distance from string (cloud height in feet)
     */
    public static function parseHeight(string $str): ?Distance {
        // 3-digit height in hundreds of feet (e.g., "040" = 4000ft)
        if (preg_match('/^(\d{3})$/', $str, $matches)) {
            return new Distance((float)$matches[1] * 100, DistanceUnit::FEET);
        }

        return null;
    }

    /**
     * Convert to different unit
     */
    public function toUnit(string $unit): float {
        // First convert to meters
        $meters = $this->value;
        switch ($this->unit) {
            case DistanceUnit::STATUTE_MILES:
                $meters = $this->value * 1609.34;
                break;
            case DistanceUnit::FEET:
                $meters = $this->value * 0.3048;
                break;
        }

        // Then convert to target unit
        switch ($unit) {
            case DistanceUnit::STATUTE_MILES:
                return $meters / 1609.34;
            case DistanceUnit::FEET:
                return $meters / 0.3048;
            default:
                return $meters;
        }
    }
}

/**
 * Temperature class
 */
class Temperature {
    public $value;
    public $unit;
    public $isFreezing;
    public $isPrecise;

    public function __construct(float $value = 0, string $unit = TemperatureUnit::CELSIUS) {
        $this->value = $value;
        $this->unit = $unit;
        $this->isFreezing = $value < 0;
        $this->isPrecise = false;
    }

    /**
     * Parse temperature from string (e.g., "24", "M02", "12.5")
     */
    public static function parse(string $str): ?Temperature {
        // Negative temperature (e.g., "M02")
        if (preg_match('/^M(\d{2})$/', $str, $matches)) {
            $temp = new Temperature(-(float)$matches[1], TemperatureUnit::CELSIUS);
            $temp->isFreezing = true;
            return $temp;
        }

        // Temperature with decimal (e.g., "12.5")
        if (preg_match('/^(\d{2}\.\d)$/', $str, $matches)) {
            $temp = new Temperature((float)$matches[1], TemperatureUnit::CELSIUS);
            $temp->isPrecise = true;
            $temp->isFreezing = $temp->value < 0;
            return $temp;
        }

        // Regular temperature (e.g., "24")
        if (preg_match('/^(\d{2})$/', $str, $matches)) {
            $temp = new Temperature((float)$matches[1], TemperatureUnit::CELSIUS);
            $temp->isFreezing = $temp->value < 0;
            return $temp;
        }

        return null;
    }

    /**
     * Convert to different unit
     */
    public function toUnit(string $unit): float {
        if ($unit === $this->unit) {
            return $this->value;
        }

        if ($unit === TemperatureUnit::FAHRENHEIT) {
            return ($this->value * 9/5) + 32;
        }

        return $this->value;
    }

    /**
     * Calculate relative humidity from temperature and dew point
     */
    public static function relativeHumidity(Temperature $temp, Temperature $dewpoint): float {
        $t = $temp->value;
        $td = $dewpoint->value;

        // Magnus formula
        $gamma = log($td / 100 * exp(($t - $td) / (273.15 + $t) * 17.27));
        return 100 * exp(17.27 * $gamma / (273.15 + $td));
    }
}

/**
 * Pressure class
 */
class Pressure {
    public $value;
    public $unit;

    public function __construct(float $value = 0, string $unit = PressureUnit::HECTOPASCAL) {
        $this->value = $value;
        $this->unit = $unit;
    }

    /**
     * Parse pressure from string (e.g., "A2990", "Q1013", "2990")
     */
    public static function parse(string $str): ?Pressure {
        // Altimeter setting (inches of mercury) - e.g., "A2990", "A3008"
        if (preg_match('/^A(\d{4})$/', $str, $matches)) {
            // Ensure we get exactly 4 digits, divide by 100 for inches of mercury
            $value = (float)$matches[1] / 100;
            // Round to avoid floating point precision issues (e.g., 30.08)
            $value = round($value, 2);
            return new Pressure($value, PressureUnit::INCHES_HG);
        }

        // QNH (hectopascals) - e.g., "Q1013"
        if (preg_match('/^Q(\d{4})$/', $str, $matches)) {
            return new Pressure((float)$matches[1], PressureUnit::HECTOPASCAL);
        }

        // QNH without Q prefix (4 digits) - e.g., "1013"
        if (preg_match('/^(\d{4})$/', $str, $matches)) {
            return new Pressure((float)$matches[1], PressureUnit::HECTOPASCAL);
        }

        return null;
    }

    /**
     * Parse SLP (sea level pressure) from string
     */
    public static function parseSlp(string $str): ?Pressure {
        // SLP - e.g., "SLP123" (123 = 1012.3 hPa)
        if (preg_match('/^SLP(\d{3})$/', $str, $matches)) {
            $value = 1000 + (float)$matches[1] / 10;
            return new Pressure($value, PressureUnit::HECTOPASCAL);
        }

        return null;
    }

    /**
     * Convert to different unit
     */
    public function toUnit(string $unit): float {
        // First convert to hPa
        $hpa = $this->value;
        switch ($this->unit) {
            case PressureUnit::INCHES_HG:
                $hpa = $this->value * 33.8639;
                break;
            case PressureUnit::MM_HG:
                $hpa = $this->value * 1.33322;
                break;
        }

        // Then convert to target unit
        switch ($unit) {
            case PressureUnit::INCHES_HG:
                return $hpa / 33.8639;
            case PressureUnit::MM_HG:
                return $hpa / 1.33322;
            default:
                return $hpa;
        }
    }
}

/**
 * Weather phenomena class
 * Comprehensive METAR weather codes based on ICAO/WMO standards
 */
class WeatherPhenomenaParser {
    // Human-readable names for all weather phenomena (ICAO standard names)
    public static $weatherNames = [
        // Precipitation
        'RA' => 'Rain',
        'SN' => 'Snow',
        'SG' => 'Snow Grains',
        'DZ' => 'Drizzle',
        'IC' => 'Ice Crystals',
        'PL' => 'Ice Pellets',
        'GS' => 'Small Hail',
        'GR' => 'Hail',
        'UP' => 'Unknown Precipitation',

        // Obscurations
        'BR' => 'Mist',
        'FG' => 'Fog',
        'FU' => 'Smoke',
        'VA' => 'Volcanic Ash',
        'SA' => 'Sand',
        'HZ' => 'Haze',
        'PY' => 'Spray',
        'DU' => 'Widespread Dust',

        // Other phenomena
        'SQ' => 'Squall',
        'SS' => 'Sandstorm',
        'DS' => 'Duststorm',
        'PO' => 'Dust/Sand Whirls',
        'FC' => 'Funnel Cloud',
        'FC+' => 'Tornado/Waterspout',

        // Descriptors
        'MI' => 'Shallow',
        'BC' => 'Patches',
        'PR' => 'Partial',
        'TS' => 'Thunderstorm',
        'BL' => 'Blowing',
        'SH' => 'Showers',
        'DR' => 'Drifting',
        'FZ' => 'Freezing',

        // Qualifiers
        'LIGHT' => 'Light',
        'MODERATE' => 'Moderate',
        'HEAVY' => 'Heavy',
        'VICINITY' => 'In Vicinity',
        'recent' => 'Recent',

        // Vicinity phenomena
        'VCTS' => 'Thunderstorm in Vicinity',
        'VCBR' => 'Mist in Vicinity',
        'VCFG' => 'Fog in Vicinity',
        'VCSN' => 'Snow in Vicinity',
        'VCRA' => 'Rain in Vicinity',
        'VCDS' => 'Duststorm in Vicinity',
        'VCSS' => 'Sandstorm in Vicinity',
        'VCSH' => 'Showers in Vicinity',
        'VCDZ' => 'Drizzle in Vicinity',

        // Recent phenomena
        'RETS' => 'Recent thunderstorm',

        // Phenomenon names (lowercase)
        'thunderstorm' => 'Thunderstorm',
    ];

    private static $weatherCodes = [
        // ========== PRECIPITATION ==========
        // Rain (RA)
        '+RA' => ['qualifier' => WeatherQualifier::HEAVY, 'phenomenon' => WeatherPhenomenon::RAIN],
        'RA' => ['qualifier' => WeatherQualifier::MODERATE, 'phenomenon' => WeatherPhenomenon::RAIN],
        '-RA' => ['qualifier' => WeatherQualifier::LIGHT, 'phenomenon' => WeatherPhenomenon::RAIN],

        // Snow (SN)
        '+SN' => ['qualifier' => WeatherQualifier::HEAVY, 'phenomenon' => WeatherPhenomenon::SNOW],
        'SN' => ['qualifier' => WeatherQualifier::MODERATE, 'phenomenon' => WeatherPhenomenon::SNOW],
        '-SN' => ['qualifier' => WeatherQualifier::LIGHT, 'phenomenon' => WeatherPhenomenon::SNOW],

        // Snow Grains (SG)
        '+SG' => ['qualifier' => WeatherQualifier::HEAVY, 'phenomenon' => WeatherPhenomenon::SNOW_GRAINS],
        'SG' => ['qualifier' => WeatherQualifier::MODERATE, 'phenomenon' => WeatherPhenomenon::SNOW_GRAINS],
        '-SG' => ['qualifier' => WeatherQualifier::LIGHT, 'phenomenon' => WeatherPhenomenon::SNOW_GRAINS],

        // Drizzle (DZ)
        '+DZ' => ['qualifier' => WeatherQualifier::HEAVY, 'phenomenon' => WeatherPhenomenon::DRIZZLE],
        'DZ' => ['qualifier' => WeatherQualifier::MODERATE, 'phenomenon' => WeatherPhenomenon::DRIZZLE],
        '-DZ' => ['qualifier' => WeatherQualifier::LIGHT, 'phenomenon' => WeatherPhenomenon::DRIZZLE],

        // Ice Crystals (IC)
        '+IC' => ['qualifier' => WeatherQualifier::HEAVY, 'phenomenon' => WeatherPhenomenon::ICE_CRYSTALS],
        'IC' => ['qualifier' => WeatherQualifier::MODERATE, 'phenomenon' => WeatherPhenomenon::ICE_CRYSTALS],
        '-IC' => ['qualifier' => WeatherQualifier::LIGHT, 'phenomenon' => WeatherPhenomenon::ICE_CRYSTALS],

        // Ice Pellets (PL)
        '+PL' => ['qualifier' => WeatherQualifier::HEAVY, 'phenomenon' => WeatherPhenomenon::ICE_PELLETS],
        'PL' => ['qualifier' => WeatherQualifier::MODERATE, 'phenomenon' => WeatherPhenomenon::ICE_PELLETS],
        '-PL' => ['qualifier' => WeatherQualifier::LIGHT, 'phenomenon' => WeatherPhenomenon::ICE_PELLETS],

        // Hail (GR)
        '+GR' => ['qualifier' => WeatherQualifier::HEAVY, 'phenomenon' => WeatherPhenomenon::HAIL],
        'GR' => ['qualifier' => WeatherQualifier::MODERATE, 'phenomenon' => WeatherPhenomenon::HAIL],
        '-GR' => ['qualifier' => WeatherQualifier::LIGHT, 'phenomenon' => WeatherPhenomenon::HAIL],

        // Small Hail (GS)
        '+GS' => ['qualifier' => WeatherQualifier::HEAVY, 'phenomenon' => WeatherPhenomenon::SMALL_HAIL],
        'GS' => ['qualifier' => WeatherQualifier::MODERATE, 'phenomenon' => WeatherPhenomenon::SMALL_HAIL],
        '-GS' => ['qualifier' => WeatherQualifier::LIGHT, 'phenomenon' => WeatherPhenomenon::SMALL_HAIL],

        // Unknown Precipitation (UP)
        'UP' => ['qualifier' => WeatherQualifier::MODERATE, 'phenomenon' => WeatherPhenomenon::UNDETERMINED],

        // ========== OBSCURATIONS ==========
        // Mist (BR) - >= 5/8 mile visibility
        '+BR' => ['qualifier' => WeatherQualifier::HEAVY, 'phenomenon' => WeatherPhenomenon::MIST],
        'BR' => ['qualifier' => WeatherQualifier::MODERATE, 'phenomenon' => WeatherPhenomenon::MIST],
        '-BR' => ['qualifier' => WeatherQualifier::LIGHT, 'phenomenon' => WeatherPhenomenon::MIST],

        // Fog (FG) - < 5/8 mile visibility
        '+FG' => ['qualifier' => WeatherQualifier::HEAVY, 'phenomenon' => WeatherPhenomenon::FOG],
        'FG' => ['qualifier' => WeatherQualifier::MODERATE, 'phenomenon' => WeatherPhenomenon::FOG],
        '-FG' => ['qualifier' => WeatherQualifier::LIGHT, 'phenomenon' => WeatherPhenomenon::FOG],

        // Patches of Fog (BCFG)
        'BCFG' => ['qualifier' => WeatherQualifier::MODERATE, 'descriptor' => 'BC', 'phenomenon' => WeatherPhenomenon::FOG],

        // Smoke (FU)
        'FU' => ['qualifier' => WeatherQualifier::MODERATE, 'phenomenon' => WeatherPhenomenon::SMOKE],

        // Volcanic Ash (VA)
        'VA' => ['qualifier' => WeatherQualifier::MODERATE, 'phenomenon' => WeatherPhenomenon::VOLCANIC_ASH],

        // Sand (SA)
        '+SA' => ['qualifier' => WeatherQualifier::HEAVY, 'phenomenon' => WeatherPhenomenon::SAND],
        'SA' => ['qualifier' => WeatherQualifier::MODERATE, 'phenomenon' => WeatherPhenomenon::SAND],
        '-SA' => ['qualifier' => WeatherQualifier::LIGHT, 'phenomenon' => WeatherPhenomenon::SAND],

        // Haze (HZ)
        '+HZ' => ['qualifier' => WeatherQualifier::HEAVY, 'phenomenon' => WeatherPhenomenon::HAZE],
        'HZ' => ['qualifier' => WeatherQualifier::MODERATE, 'phenomenon' => WeatherPhenomenon::HAZE],
        '-HZ' => ['qualifier' => WeatherQualifier::LIGHT, 'phenomenon' => WeatherPhenomenon::HAZE],

        // Spray (PY)
        '+PY' => ['qualifier' => WeatherQualifier::HEAVY, 'phenomenon' => WeatherPhenomenon::SPRAY],
        'PY' => ['qualifier' => WeatherQualifier::MODERATE, 'phenomenon' => WeatherPhenomenon::SPRAY],
        '-PY' => ['qualifier' => WeatherQualifier::LIGHT, 'phenomenon' => WeatherPhenomenon::SPRAY],

        // Widespread Dust (DU)
        '+DU' => ['qualifier' => WeatherQualifier::HEAVY, 'phenomenon' => WeatherPhenomenon::DUST],
        'DU' => ['qualifier' => WeatherQualifier::MODERATE, 'phenomenon' => WeatherPhenomenon::DUST],
        '-DU' => ['qualifier' => WeatherQualifier::LIGHT, 'phenomenon' => WeatherPhenomenon::DUST],

        // ========== OTHER PHENOMENA ==========
        // Squall (SQ)
        '+SQ' => ['qualifier' => WeatherQualifier::HEAVY, 'phenomenon' => WeatherPhenomenon::SQUALLS],
        'SQ' => ['qualifier' => WeatherQualifier::MODERATE, 'phenomenon' => WeatherPhenomenon::SQUALLS],

        // Sandstorm (SS)
        '+SS' => ['qualifier' => WeatherQualifier::HEAVY, 'phenomenon' => WeatherPhenomenon::SANDSTORM],
        'SS' => ['qualifier' => WeatherQualifier::MODERATE, 'phenomenon' => WeatherPhenomenon::SANDSTORM],
        '-SS' => ['qualifier' => WeatherQualifier::LIGHT, 'phenomenon' => WeatherPhenomenon::SANDSTORM],

        // Duststorm (DS)
        '+DS' => ['qualifier' => WeatherQualifier::HEAVY, 'phenomenon' => WeatherPhenomenon::DUSTSTORM],
        'DS' => ['qualifier' => WeatherQualifier::MODERATE, 'phenomenon' => WeatherPhenomenon::DUSTSTORM],
        '-DS' => ['qualifier' => WeatherQualifier::LIGHT, 'phenomenon' => WeatherPhenomenon::DUSTSTORM],

        // Dust/Sand Whirls (PO)
        '+PO' => ['qualifier' => WeatherQualifier::HEAVY, 'phenomenon' => WeatherPhenomenon::DUST_WHIRLS],
        'PO' => ['qualifier' => WeatherQualifier::MODERATE, 'phenomenon' => WeatherPhenomenon::DUST_WHIRLS],

        // Funnel Cloud (FC)
        'FC' => ['qualifier' => WeatherQualifier::MODERATE, 'phenomenon' => WeatherPhenomenon::FUNNEL_CLOUD],

        // Tornado/Waterspout (FC+)
        'FC+' => ['qualifier' => WeatherQualifier::HEAVY, 'phenomenon' => WeatherPhenomenon::TORNADO],

        // ========== DESCRIPTOR + PHENOMENON COMBINATIONS ==========
        // Freezing (FZ)
        'FZDZ' => ['qualifier' => WeatherQualifier::MODERATE, 'descriptor' => WeatherDescriptor::FREEZING, 'phenomenon' => WeatherPhenomenon::DRIZZLE],
        'FZRA' => ['qualifier' => WeatherQualifier::MODERATE, 'descriptor' => WeatherDescriptor::FREEZING, 'phenomenon' => WeatherPhenomenon::RAIN],
        'FZSN' => ['qualifier' => WeatherQualifier::MODERATE, 'descriptor' => WeatherDescriptor::FREEZING, 'phenomenon' => WeatherPhenomenon::SNOW],

        // Showers (SH)
        'SHRA' => ['qualifier' => WeatherQualifier::MODERATE, 'descriptor' => WeatherDescriptor::SHOWERS, 'phenomenon' => WeatherPhenomenon::RAIN],
        'SHSN' => ['qualifier' => WeatherQualifier::MODERATE, 'descriptor' => WeatherDescriptor::SHOWERS, 'phenomenon' => WeatherPhenomenon::SNOW],
        'SHGR' => ['qualifier' => WeatherQualifier::MODERATE, 'descriptor' => WeatherDescriptor::SHOWERS, 'phenomenon' => WeatherPhenomenon::HAIL],
        'SHGS' => ['qualifier' => WeatherQualifier::MODERATE, 'descriptor' => WeatherDescriptor::SHOWERS, 'phenomenon' => WeatherPhenomenon::SMALL_HAIL],
        'SHDZ' => ['qualifier' => WeatherQualifier::MODERATE, 'descriptor' => WeatherDescriptor::SHOWERS, 'phenomenon' => WeatherPhenomenon::DRIZZLE],
        '+SHRA' => ['qualifier' => WeatherQualifier::HEAVY, 'descriptor' => WeatherDescriptor::SHOWERS, 'phenomenon' => WeatherPhenomenon::RAIN],
        '-SHRA' => ['qualifier' => WeatherQualifier::LIGHT, 'descriptor' => WeatherDescriptor::SHOWERS, 'phenomenon' => WeatherPhenomenon::RAIN],
        '+SHSN' => ['qualifier' => WeatherQualifier::HEAVY, 'descriptor' => WeatherDescriptor::SHOWERS, 'phenomenon' => WeatherPhenomenon::SNOW],
        '-SHSN' => ['qualifier' => WeatherQualifier::LIGHT, 'descriptor' => WeatherDescriptor::SHOWERS, 'phenomenon' => WeatherPhenomenon::SNOW],

        // Thunderstorm (TS)
        'TSRA' => ['qualifier' => WeatherQualifier::MODERATE, 'descriptor' => WeatherDescriptor::THUNDERSTORM, 'phenomenon' => WeatherPhenomenon::RAIN],
        'TSSN' => ['qualifier' => WeatherQualifier::MODERATE, 'descriptor' => WeatherDescriptor::THUNDERSTORM, 'phenomenon' => WeatherPhenomenon::SNOW],
        'TSGR' => ['qualifier' => WeatherQualifier::MODERATE, 'descriptor' => WeatherDescriptor::THUNDERSTORM, 'phenomenon' => WeatherPhenomenon::HAIL],
        'TSGS' => ['qualifier' => WeatherQualifier::MODERATE, 'descriptor' => WeatherDescriptor::THUNDERSTORM, 'phenomenon' => WeatherPhenomenon::SMALL_HAIL],
        'TSDZ' => ['qualifier' => WeatherQualifier::MODERATE, 'descriptor' => WeatherDescriptor::THUNDERSTORM, 'phenomenon' => WeatherPhenomenon::DRIZZLE],
        'TSFG' => ['qualifier' => WeatherQualifier::MODERATE, 'descriptor' => WeatherDescriptor::THUNDERSTORM, 'phenomenon' => WeatherPhenomenon::FOG],
        '+TSRA' => ['qualifier' => WeatherQualifier::HEAVY, 'descriptor' => WeatherDescriptor::THUNDERSTORM, 'phenomenon' => WeatherPhenomenon::RAIN],
        '-TSRA' => ['qualifier' => WeatherQualifier::LIGHT, 'descriptor' => WeatherDescriptor::THUNDERSTORM, 'phenomenon' => WeatherPhenomenon::RAIN],
        '+TSSN' => ['qualifier' => WeatherQualifier::HEAVY, 'descriptor' => WeatherDescriptor::THUNDERSTORM, 'phenomenon' => WeatherPhenomenon::SNOW],
        'TSRAGR' => ['qualifier' => WeatherQualifier::MODERATE, 'descriptor' => WeatherDescriptor::THUNDERSTORM, 'phenomenon' => WeatherPhenomenon::RAIN, 'secondary' => WeatherPhenomenon::HAIL],

        // Blowing (BL)
        'BLSN' => ['qualifier' => WeatherQualifier::MODERATE, 'descriptor' => WeatherDescriptor::BLOWING, 'phenomenon' => WeatherPhenomenon::SNOW],
        'BLDU' => ['qualifier' => WeatherQualifier::MODERATE, 'descriptor' => WeatherDescriptor::BLOWING, 'phenomenon' => WeatherPhenomenon::DUST],
        'BLSA' => ['qualifier' => WeatherQualifier::MODERATE, 'descriptor' => WeatherDescriptor::BLOWING, 'phenomenon' => WeatherPhenomenon::SAND],
        '+BLSN' => ['qualifier' => WeatherQualifier::HEAVY, 'descriptor' => WeatherDescriptor::BLOWING, 'phenomenon' => WeatherPhenomenon::SNOW],
        '-BLSN' => ['qualifier' => WeatherQualifier::LIGHT, 'descriptor' => WeatherDescriptor::BLOWING, 'phenomenon' => WeatherPhenomenon::SNOW],
        '+BLDU' => ['qualifier' => WeatherQualifier::HEAVY, 'descriptor' => WeatherDescriptor::BLOWING, 'phenomenon' => WeatherPhenomenon::DUST],
        '+BLSA' => ['qualifier' => WeatherQualifier::HEAVY, 'descriptor' => WeatherDescriptor::BLOWING, 'phenomenon' => WeatherPhenomenon::SAND],

        // Drifting (DR)
        'DRSN' => ['qualifier' => WeatherQualifier::MODERATE, 'descriptor' => WeatherDescriptor::LOW_DRIFTING, 'phenomenon' => WeatherPhenomenon::SNOW],
        'DRDU' => ['qualifier' => WeatherQualifier::MODERATE, 'descriptor' => WeatherDescriptor::LOW_DRIFTING, 'phenomenon' => WeatherPhenomenon::DUST],
        'DRSA' => ['qualifier' => WeatherQualifier::MODERATE, 'descriptor' => WeatherDescriptor::LOW_DRIFTING, 'phenomenon' => WeatherPhenomenon::SAND],
        '+DRSN' => ['qualifier' => WeatherQualifier::HEAVY, 'descriptor' => WeatherDescriptor::LOW_DRIFTING, 'phenomenon' => WeatherPhenomenon::SNOW],

        // Patches (BC)
        'BCFG' => ['qualifier' => WeatherQualifier::MODERATE, 'descriptor' => WeatherDescriptor::PATCHES, 'phenomenon' => WeatherPhenomenon::FOG],
        'BCBR' => ['qualifier' => WeatherQualifier::MODERATE, 'descriptor' => WeatherDescriptor::PATCHES, 'phenomenon' => WeatherPhenomenon::MIST],

        // Partial (PR)
        'PRFG' => ['qualifier' => WeatherQualifier::MODERATE, 'descriptor' => WeatherDescriptor::PARTIAL, 'phenomenon' => WeatherPhenomenon::FOG],

        // Shallow (MI)
        'MIFG' => ['qualifier' => WeatherQualifier::MODERATE, 'descriptor' => WeatherDescriptor::SHALLOW, 'phenomenon' => WeatherPhenomenon::FOG],
        'MIBR' => ['qualifier' => WeatherQualifier::MODERATE, 'descriptor' => WeatherDescriptor::SHALLOW, 'phenomenon' => WeatherPhenomenon::MIST],

        // ========== RECENT (RE) ==========
        'RETS' => ['qualifier' => WeatherQualifier::RECENT, 'descriptor' => WeatherDescriptor::THUNDERSTORM, 'phenomenon' => WeatherPhenomenon::THUNDERSTORM],
        'RETSRA' => ['qualifier' => WeatherQualifier::RECENT, 'descriptor' => WeatherDescriptor::THUNDERSTORM, 'phenomenon' => WeatherPhenomenon::RAIN],
        'RETSGR' => ['qualifier' => WeatherQualifier::RECENT, 'descriptor' => WeatherDescriptor::THUNDERSTORM, 'phenomenon' => WeatherPhenomenon::HAIL],
        'RETSGS' => ['qualifier' => WeatherQualifier::RECENT, 'descriptor' => WeatherDescriptor::THUNDERSTORM, 'phenomenon' => WeatherPhenomenon::SMALL_HAIL],

        // ========== VICINITY (VC) ==========
        'VCTS' => ['qualifier' => WeatherQualifier::VICINITY, 'descriptor' => 'thunderstorm', 'phenomenon' => 'thunderstorm'],
        'VCFG' => ['qualifier' => WeatherQualifier::VICINITY, 'phenomenon' => WeatherPhenomenon::FOG],
        'VCBR' => ['qualifier' => WeatherQualifier::VICINITY, 'phenomenon' => WeatherPhenomenon::MIST],
        'VCSN' => ['qualifier' => WeatherQualifier::VICINITY, 'phenomenon' => WeatherPhenomenon::SNOW],
        'VCRA' => ['qualifier' => WeatherQualifier::VICINITY, 'phenomenon' => WeatherPhenomenon::RAIN],
        'VCDS' => ['qualifier' => WeatherQualifier::VICINITY, 'phenomenon' => WeatherPhenomenon::DUSTSTORM],
        'VCSS' => ['qualifier' => WeatherQualifier::VICINITY, 'phenomenon' => WeatherPhenomenon::SANDSTORM],
        'VCSH' => ['qualifier' => WeatherQualifier::VICINITY, 'descriptor' => WeatherDescriptor::SHOWERS, 'phenomenon' => 'showers'],
        'VCDZ' => ['qualifier' => WeatherQualifier::VICINITY, 'phenomenon' => WeatherPhenomenon::DRIZZLE],
    ];

    /**
     * Get human-readable name for weather phenomenon
     */
    public static function getName(string $code): string {
        return self::$weatherNames[$code] ?? $code;
    }

    /**
     * Get full descriptive name for a parsed weather entry
     */
    public static function getFullName(array $weather): string {
        $parts = [];

        // Check if this is a VC (vicinity) phenomenon - use direct lookup
        if (!empty($weather['qualifier']) && $weather['qualifier'] === WeatherQualifier::VICINITY) {
            // Build the VC code: VCTS, VCBR, etc.
            if (!empty($weather['phenomenon'])) {
                // Map phenomenon names to VC codes
                $vcMap = [
                    'thunderstorm' => 'VCTS',
                    'fog' => 'VCFG',
                    'mist' => 'VCBR',
                    'snow' => 'VCSN',
                    'rain' => 'VCRA',
                    'showers' => 'VCSH',
                    'drizzle' => 'VCDZ',
                    'duststorm' => 'VCDS',
                    'sandstorm' => 'VCSS'
                ];
                $vcCode = $vcMap[$weather['phenomenon']] ?? 'VC' . strtoupper(substr($weather['phenomenon'], 0, 2));
                if (isset(self::$weatherNames[$vcCode])) {
                    return self::$weatherNames[$vcCode];
                }
            }
        }

        // Add qualifier
        if (!empty($weather['qualifier']) && $weather['qualifier'] !== WeatherQualifier::NONE) {
            $parts[] = self::$weatherNames[$weather['qualifier']] ?? $weather['qualifier'];
        }

        // Add descriptor
        if (!empty($weather['descriptor']) && $weather['descriptor'] !== WeatherDescriptor::NONE) {
            $parts[] = self::$weatherNames[$weather['descriptor']] ?? $weather['descriptor'];
        }

        // Add phenomenon
        if (!empty($weather['phenomenon'])) {
            $parts[] = self::$weatherNames[$weather['phenomenon']] ?? $weather['phenomenon'];
        }

        return implode(' ', $parts);
    }

    /**
     * Parse weather phenomena from string
     */
    public static function parse(string $str): ?array {
        // Remove CB (cumulonimbus) suffix
        $str = preg_replace('/CB$/', '', $str);

        // Check for proximity indicators
        if (strpos($str, 'VC') === 0) {
            $base = substr($str, 2);
            if (isset(self::$weatherCodes[$base])) {
                $result = self::$weatherCodes[$base];
                $result['qualifier'] = WeatherQualifier::VICINITY;
                return $result;
            }
        }

        // Check for recent weather (RE prefix)
        $recent = false;
        if (strpos($str, 'RE') === 0) {
            $recent = true;
            $str = substr($str, 2);
        }

        // Check for descriptor + phenomena combinations
        $descriptors = ['SH', 'TS', 'FZ', 'DR', 'BL', 'MI', 'BC', 'PR'];
        foreach ($descriptors as $desc) {
            if (strpos($str, $desc) === 0) {
                $phenom = substr($str, strlen($desc));
                $fullCode = $desc . $phenom;
                if (isset(self::$weatherCodes[$fullCode])) {
                    $result = self::$weatherCodes[$fullCode];
                    if ($recent) {
                        $result['qualifier'] = WeatherQualifier::RECENT;
                    }
                    return $result;
                }
            }
        }

        // Direct lookup
        if (isset(self::$weatherCodes[$str])) {
            $result = self::$weatherCodes[$str];
            if ($recent) {
                $result['qualifier'] = WeatherQualifier::RECENT;
            }
            return $result;
        }

        return null;
    }
}

/**
 * Output formatter class
 * Provides methods to format decoded data for display
 */
class Formatter {

    /**
     * Format wind data for display
     * @param array $wind Wind data from decoder
     * @return array Formatted wind data
     */
    public static function formatWind(array $wind): array {
        $formatted = [
            'direction' => '',
            'speed' => '',
            'gust' => '',
            'raw' => ''
        ];

        if (empty($wind)) {
            return $formatted;
        }

        $dir = $wind['direction'] ?? '';
        $speed = $wind['speed'] ?? '';
        $gust = $wind['gust'] ?? '';
        $dirType = $wind['direction_type'] ?? 'degrees';

        // Format direction
        if ($dirType === 'variable' || $dir === 'VRB') {
            $formatted['direction'] = 'VRB';
        } elseif (is_numeric($dir)) {
            $formatted['direction'] = $dir . '°';
        } elseif (!empty($dir)) {
            $formatted['direction'] = $dir;
        }

        // Format speed
        if ($speed !== '' && $speed !== null) {
            $formatted['speed'] = $speed . ' kt';
        }

        // Format gust
        if (!empty($gust)) {
            $formatted['gust'] = 'G' . $gust . ' kt';
        }

        // Build raw string
        $parts = [];
        if ($formatted['direction']) $parts[] = $formatted['direction'];
        if ($formatted['speed']) $parts[] = $formatted['speed'];
        if ($formatted['gust']) $parts[] = $formatted['gust'];
        $formatted['raw'] = implode(' ', $parts);

        return $formatted;
    }

    /**
     * Format visibility data for display
     * @param array $visibility Visibility data from decoder
     * @return array Formatted visibility data
     */
    public static function formatVisibility(array $visibility): array {
        $formatted = [
            'prevailing' => '',
            'minimum' => '',
            'maximum' => '',
            'directional' => [],
            'raw' => ''
        ];

        if (empty($visibility)) {
            return $formatted;
        }

        $prevailing = $visibility['prevailing'] ?? null;

        // Format prevailing visibility
        if ($prevailing !== null) {
            if ($prevailing >= 9999) {
                $formatted['prevailing'] = '10+ km (6+ SM)';
            } else {
                $visSM = round($prevailing / 1609.34, 1);
                if ($visSM >= 1) {
                    $formatted['prevailing'] = round($prevailing) . ' m (' . $visSM . ' SM)';
                } else {
                    $formatted['prevailing'] = round($prevailing) . ' m';
                }
            }
        }

        // Format minimum/maximum
        if (!empty($visibility['minimum'])) {
            $formatted['minimum'] = round($visibility['minimum']) . ' m';
        }
        if (!empty($visibility['maximum'])) {
            $formatted['maximum'] = round($visibility['maximum']) . ' m';
        }

        // Format directional visibility
        if (!empty($visibility['directional'])) {
            foreach ($visibility['directional'] as $dir => $vis) {
                $formatted['directional'][$dir] = round($vis) . ' m';
            }
        }

        return $formatted;
    }

    /**
     * Format weather phenomena for display
     * @param array $weather Weather data from decoder
     * @return array Formatted weather data
     */
    public static function formatWeather(array $weather): array {
        $formatted = [];

        if (empty($weather)) {
            return $formatted;
        }

        foreach ($weather as $wx) {
            $weatherName = '';

            // Get weather components
            $phenom = !empty($wx['phenomenon']) ? $wx['phenomenon'] : '';
            $qualifier = !empty($wx['qualifier']) ? $wx['qualifier'] : '';
            $descriptor = !empty($wx['descriptor']) ? $wx['descriptor'] : '';
            $raw = !empty($wx['raw']) ? $wx['raw'] : '';

            // Check if it's a VC (vicinity) phenomenon
            $isVC = false;
            $vcType = '';

            if ($qualifier === 'vicinity' || $qualifier === 'Vicinity') {
                $isVC = true;
            }

            // Also check if raw field indicates VC
            if (!$isVC && !empty($raw)) {
                $rawUpper = strtoupper($raw);
                if (strpos($rawUpper, 'VC') === 0) {
                    $isVC = true;
                    $vcType = substr($rawUpper, 2);
                }
            }

            // Handle VC phenomena
            if ($isVC || ($descriptor === 'thunderstorm' && $phenom === 'thunderstorm')) {
                $vcMap = [
                    'thunderstorm' => 'Thunderstorm in Vicinity',
                    'TS' => 'Thunderstorm in Vicinity',
                    'fog' => 'Fog in Vicinity',
                    'FG' => 'Fog in Vicinity',
                    'mist' => 'Mist in Vicinity',
                    'BR' => 'Mist in Vicinity',
                    'snow' => 'Snow in Vicinity',
                    'SN' => 'Snow in Vicinity',
                    'rain' => 'Rain in Vicinity',
                    'RA' => 'Rain in Vicinity',
                    'duststorm' => 'Duststorm in Vicinity',
                    'DS' => 'Duststorm in Vicinity',
                    'sandstorm' => 'Sandstorm in Vicinity',
                    'SS' => 'Sandstorm in Vicinity'
                ];

                $vcKey = $vcType ?: $phenom;
                if (isset($vcMap[$vcKey])) {
                    $weatherName = $vcMap[$vcKey];
                } elseif (!empty($raw)) {
                    $weatherName = strtoupper($raw);
                }
            }

            // If still empty, use getFullName or manual formatting
            if (empty($weatherName)) {
                $weatherName = WeatherPhenomenaParser::getFullName($wx);
                if (empty($weatherName)) {
                    // Manual formatting fallback
                    $intensity = '';
                    if (!empty($wx['qualifier'])) {
                        if ($wx['qualifier'] === 'light') $intensity = 'Light ';
                        elseif ($wx['qualifier'] === 'heavy') $intensity = 'Heavy ';
                    }

                    // Descriptor mapping
                    $descriptorMap = [
                        'shallow' => 'Shallow',
                        'partial' => 'Partial',
                        'patches' => 'Patches',
                        'bc' => 'Patches',
                        'low_drifting' => 'Low Drifting',
                        'dr' => 'Low Drifting',
                        'blowing' => 'Blowing',
                        'bl' => 'Blowing',
                        'showers' => 'Showers',
                        'sh' => 'Showers',
                        'thunderstorm' => 'Thunderstorm',
                        'ts' => 'Thunderstorm',
                        'freezing' => 'Freezing',
                        'fz' => 'Freezing'
                    ];

                    $desc = '';
                    if (!empty($wx['descriptor']) && $wx['descriptor'] !== 'none') {
                        $descKey = strtolower($wx['descriptor']);
                        $desc = ($descriptorMap[$descKey] ?? ucfirst($wx['descriptor'])) . ' ';
                    }

                    // Phenomenon mapping
                    $phenomenonMap = [
                        'fog' => 'Fog',
                        'fg' => 'Fog',
                        'mist' => 'Mist',
                        'br' => 'Mist',
                        'rain' => 'Rain',
                        'ra' => 'Rain',
                        'snow' => 'Snow',
                        'sn' => 'Snow',
                        'drizzle' => 'Drizzle',
                        'dz' => 'Drizzle',
                        'haze' => 'Haze',
                        'hz' => 'Haze',
                        'smoke' => 'Smoke',
                        'fu' => 'Smoke',
                        'dust' => 'Dust',
                        'du' => 'Dust',
                        'sand' => 'Sand',
                        'sa' => 'Sand',
                        'sandstorm' => 'Sandstorm',
                        'ss' => 'Sandstorm',
                        'duststorm' => 'Duststorm',
                        'ds' => 'Duststorm'
                    ];

                    $phenomKey = !empty($wx['phenomenon']) ? strtolower($wx['phenomenon']) : '';
                    $phenomName = $phenomenonMap[$phenomKey] ?? ( !empty($wx['phenomenon']) ? ucfirst($wx['phenomenon']) : '' );

                    $weatherName = $intensity . $desc . $phenomName;
                }
            }

            if (!empty($weatherName)) {
                $formatted[] = $weatherName;
            }
        }

        return $formatted;
    }

    /**
     * Format cloud data for display
     * @param array $clouds Cloud data from decoder
     * @return array Formatted cloud data
     */
    public static function formatClouds(array $clouds): array {
        $formatted = [];

        if (empty($clouds)) {
            return $formatted;
        }

        $typeMap = [
            'few' => 'FEW',
            'scattered' => 'SCT',
            'broken' => 'BKN',
            'overcast' => 'OVC',
            'vertical_visibility' => 'VV',
            'none_clr' => 'Clear',
            'none_skc' => 'Clear',
            'nsc' => 'Clear',
            'skc' => 'Clear'
        ];

        foreach ($clouds as $cloud) {
            $amount = $cloud['amount'] ?? '';
            $height = $cloud['height_ft'] ?? '';
            $cloudType = $cloud['cloud_type'] ?? '';

            $cloudStr = $typeMap[$amount] ?? $amount;

            if ($height && $cloudStr !== 'Clear') {
                $cloudStr .= ' ' . round($height) . 'ft';
            }

            // Add cloud type
            if (!empty($cloudType) && $cloudStr !== 'Clear') {
                // Map cloud type codes to names
                $typeNames = [
                    'CB' => 'cumulonimbus',
                    'TCU' => 'towering_cumulus',
                    'CU' => 'cumulus',
                    'CF' => 'cumulus_fractus',
                    'SC' => 'stratocumulus',
                    'NS' => 'nimbostratus',
                    'ST' => 'stratus'
                ];
                $typeName = $typeNames[$cloudType] ?? $cloudType;
                $cloudStr .= ' ' . $typeName;
            }

            $cloudStr = trim($cloudStr);

            if (!empty($cloudStr)) {
                // For clear sky conditions, altitude should be empty
                $isClear = in_array($amount, ['none_clr', 'none_skc', 'nsc', 'skc']) || $cloudStr === 'Clear';
                $formatted[] = [
                    'type' => $cloudStr,
                    'amount' => $typeMap[$amount] ?? $amount,
                    'altitude' => ($height && !$isClear) ? round($height) . ' ft' : '',
                    'cloud_type' => $cloudType
                ];
            }
        }

        return $formatted;
    }

    /**
     * Format TAF time period for display
     * @param string $taf Raw TAF string
     * @return array Formatted time data
     */
    public static function formatTafTime(string $taf): array {
        $formatted = [
            'issued_at' => '',
            'valid_from' => '',
            'valid_until' => ''
        ];

        // Extract issuance time (e.g., "071655Z")
        if (preg_match('/(\d{2})(\d{2})(\d{2})Z/', $taf, $match)) {
            $formatted['issued_at'] = $match[1] . '/' . $match[2] . ' ' . $match[3] . ':00Z';
        }

        // Extract validity period (e.g., "0718/0818")
        if (preg_match('/(\d{4})\/(\d{4})/', $taf, $match)) {
            $fromDay = substr($match[1], 0, 2);
            $fromHour = substr($match[1], 2, 2);
            $toDay = substr($match[2], 0, 2);
            $toHour = substr($match[2], 2, 2);
            $formatted['valid_from'] = 'Day ' . $fromDay . ' ' . $fromHour . ':00Z';
            $formatted['valid_until'] = 'Day ' . $toDay . ' ' . $toHour . ':00Z';
        }

        return $formatted;
    }

    /**
     * Format TAF trend for display
     * @param array $trend Trend data from decoder
     * @return array Formatted trend data
     */
    public static function formatTrend(array $trend): array {
        $formatted = [
            'type' => $trend['type'] ?? '',
            'time' => '',
            'wind' => '',
            'visibility' => '',
            'weather' => [],
            'clouds' => []
        ];

        // Format time
        if (!empty($trend['time'])) {
            $t = $trend['time'];
            if (!empty($t['from_hour'])) {
                $formatted['time'] = 'From ' . str_pad($t['from_hour'], 2, '0', STR_PAD_LEFT) . ':' .
                                   str_pad($t['from_minute'] ?? 0, 2, '0', STR_PAD_LEFT);
            }
        }

        // Format wind
        if (!empty($trend['wind'])) {
            $formatted['wind'] = self::formatWind($trend['wind'])['raw'];
        }

        // Format visibility
        if (!empty($trend['visibility'])) {
            $visMeters = $trend['visibility'];
            if ($visMeters >= 9999) {
                $formatted['visibility'] = '10+ km (6+ SM)';
            } else {
                $visSM = round($visMeters / 1609.34, 1);
                if ($visSM >= 1) {
                    $formatted['visibility'] = round($visMeters) . ' m (' . $visSM . ' SM)';
                } else {
                    $formatted['visibility'] = round($visMeters) . ' m';
                }
            }
        }

        // Format weather
        if (!empty($trend['weather'])) {
            $formatted['weather'] = self::formatWeather($trend['weather']);
        }

        // Format clouds
        if (!empty($trend['clouds'])) {
            $formatted['clouds'] = self::formatClouds($trend['clouds']);
        }

        return $formatted;
    }

    /**
     * Format complete decoded report for display
     * @param array $decoded Decoded data from Metaf\Decoder
     * @param string $raw Raw report string
     * @param string $reportType 'metar' or 'taf'
     * @return array Formatted data ready for display
     */
    public static function formatReport(array $decoded, string $raw = '', string $reportType = 'metar'): array {
        $formatted = [
            'raw' => $raw,
            'station' => $decoded['station'] ?? '',
            'type' => $decoded['type'] ?? $reportType,
            'is_speci' => $decoded['is_speci'] ?? false,
            'is_automated' => $decoded['is_automated'] ?? false,
            'is_amended' => $decoded['is_amended'] ?? false,
            'is_cancelled' => $decoded['is_cancelled'] ?? false,
            'observation_time' => $decoded['observation_time'] ?? null,
            'wind' => [],
            'visibility' => [],
            'weather' => [],
            'clouds' => [],
            'temperature' => null,
            'dewpoint' => null,
            'pressure' => null,
            'trends' => [],
            'remarks' => [],
            'raw_remarks' => $decoded['remarks_raw'] ?? []
        ];

        // Format basic info
        if ($reportType === 'taf') {
            $timeData = self::formatTafTime($raw);
            $formatted['issued_at'] = $timeData['issued_at'];
            $formatted['valid_from'] = $timeData['valid_from'];
            $formatted['valid_until'] = $timeData['valid_until'];
        }

        // Format wind
        if (!empty($decoded['wind'])) {
            $formatted['wind'] = self::formatWind($decoded['wind']);
        }

        // Format visibility
        if (!empty($decoded['visibility'])) {
            $formatted['visibility'] = self::formatVisibility($decoded['visibility']);
        }

        // Format weather
        if (!empty($decoded['weather'])) {
            $formatted['weather'] = self::formatWeather($decoded['weather']);
        }

        // Format clouds
        if (!empty($decoded['clouds'])) {
            $formatted['clouds'] = self::formatClouds($decoded['clouds']);
        }

        // Format temperature/dewpoint
        if (!empty($decoded['temperature'])) {
            $formatted['temperature'] = $decoded['temperature'] . '°C';
        }
        if (!empty($decoded['dewpoint'])) {
            $formatted['dewpoint'] = $decoded['dewpoint'] . '°C';
        }

        // Format pressure
        if (!empty($decoded['pressure'])) {
            $formatted['pressure'] = $decoded['pressure'] . ' hPa';
        }

        // Format trends (TAF only)
        if (!empty($decoded['trend'])) {
            foreach ($decoded['trend'] as $trend) {
                $formatted['trends'][] = self::formatTrend($trend);
            }
        }

        return $formatted;
    }
}

/**
 * Cloud group parser
 */
class CloudGroupParser {
    private static $cloudAmounts = [
        'NCD' => CloudAmount::NCD,
        'NSC' => CloudAmount::NSC,
        'CLR' => CloudAmount::NONE_CLR,
        'SKC' => CloudAmount::NONE_SKC,
        'FEW' => CloudAmount::FEW,
        'SCT' => CloudAmount::SCATTERED,
        'BKN' => CloudAmount::BROKEN,
        'OVC' => CloudAmount::OVERCAST,
        'VV' => 'vertical_visibility'
    ];

    private static $cloudTypes = [
        'CB' => CloudType::CUMULONIMBUS,
        'TCU' => CloudType::TOWERING_CUMULUS,
        'CU' => CloudType::CUMULUS,
        'CF' => CloudType::CUMULUS_FRACTUS,
        'SC' => CloudType::STRATOCUMULUS,
        'NS' => CloudType::NIMBOSTRATUS,
        'ST' => CloudType::STRATUS,
        'SF' => CloudType::STRATUS_FRACTUS,
        'AS' => CloudType::ALTOSTRATUS,
        'AC' => CloudType::ALTOCUMULUS,
        'ACC' => CloudType::ALTOCUMULUS_CASTELLANUS,
        'CI' => CloudType::CIRRUS,
        'CS' => CloudType::CIRROSTRATUS,
        'CC' => CloudType::CIRROCUMULUS
    ];

    /**
     * Parse cloud group from string (e.g., "FEW040", "BKN050CB", "OVC100TCU", "FEW038///")
     */
    public static function parse(string $str): ?array {
        $result = [
            'type' => 'cloud_layer',
            'amount' => null,
            'height' => null,
            'height_ft' => null,
            'cloud_type' => null,
            'is_vertical_visibility' => false
        ];

        // Check for no clouds
        if (isset(self::$cloudAmounts[$str])) {
            $result['amount'] = self::$cloudAmounts[$str];
            return $result;
        }

        // Parse amount and height with cloud type (e.g., "FEW040CB", "BKN050TCU")
        if (preg_match('/^([A-Z]{2,3})(\d{3})([A-Z]{2,3})?$/', $str, $matches)) {
            $result['amount'] = self::$cloudAmounts[$matches[1]] ?? CloudAmount::NOT_REPORTED;
            $result['height'] = (int)$matches[2] * 100;
            $result['height_ft'] = $result['height'];

            if (isset($matches[3]) && $matches[3] !== '///') {
                $result['cloud_type'] = self::$cloudTypes[$matches[3]] ?? null;
            }
            // If ///, cloud_type remains null (no type detected)

            return $result;
        }

        // Parse amount and height WITHOUT cloud type (e.g., "FEW038///", "SCT048///", "BKN110///")
        // Format: 3 letters + 3 digits + ///
        if (preg_match('/^([A-Z]{2,3})(\d{3})\/\/\/$/', $str, $matches)) {
            $result['amount'] = self::$cloudAmounts[$matches[1]] ?? CloudAmount::NOT_REPORTED;
            $result['height'] = (int)$matches[2] * 100;
            $result['height_ft'] = $result['height'];
            $result['cloud_type'] = null; // No type detected (///)

            return $result;
        }

        // Vertical visibility (e.g., "VV010")
        if (preg_match('/^VV(\d{3})$/', $str, $matches)) {
            $result['type'] = 'vertical_visibility';
            $result['height'] = (int)$matches[1] * 100;
            $result['height_ft'] = $result['height'];
            $result['is_vertical_visibility'] = true;
            return $result;
        }

        // Vertical visibility with no height detected (e.g., "VV///")
        if (preg_match('/^VV\/\/\/$/', $str, $matches)) {
            $result['type'] = 'vertical_visibility';
            $result['height'] = null;
            $result['height_ft'] = null;
            $result['is_vertical_visibility'] = true;
            return $result;
        }

        return null;
    }
}

/**
 * Runway parser
 */
class RunwayParser {
    /**
     * Parse runway designator from string (e.g., "09L", "18R", "36C")
     */
    public static function parse(string $str): ?array {
        if (preg_match('/^(\d{2})([LRC]?)$/', $str, $matches)) {
            return [
                'number' => (int)$matches[1],
                'designator' => $matches[2] ?: null,
                'is_valid' => (int)$matches[1] <= 36
            ];
        }

        return null;
    }
}

/**
 * MetafTime parser
 */
class MetafTimeParser {
    /**
     * Parse time from DDHHMM or HHMM format
     */
    public static function parse(string $str): ?array {
        // DDHHMM format (e.g., "091056Z")
        if (preg_match('/^(\d{2})(\d{2})(\d{2})Z$/', $str, $matches)) {
            return [
                'day' => (int)$matches[1],
                'hour' => (int)$matches[2],
                'minute' => (int)$matches[3],
                'is_valid' => true
            ];
        }

        // HHMM format (e.g., "1056Z")
        if (preg_match('/^(\d{2})(\d{2})Z$/', $str, $matches)) {
            return [
                'day' => null,
                'hour' => (int)$matches[1],
                'minute' => (int)$matches[2],
                'is_valid' => true
            ];
        }

        return null;
    }

    /**
     * Parse time span from DDHH/ format
     */
    public static function parseTimeSpan(string $str): ?array {
        if (preg_match('/^(\d{2})(\d{2})\/(\d{2})(\d{2})$/', $str, $matches)) {
            return [
                'from_day' => (int)$matches[1],
                'from_hour' => (int)$matches[2],
                'until_day' => (int)$matches[3],
                'until_hour' => (int)$matches[4],
                'is_valid' => true
            ];
        }

        // FM format (from time) - FMYYZZXX or FMZZXX (day + hour + minute)
        // FM091600 = FM + Day 09 + Hour 16 + Minute 00
        if (preg_match('/^FM(\d{2})(\d{2})(\d{2})$/', $str, $matches)) {
            return [
                'from_day' => (int)$matches[1],
                'from_hour' => (int)$matches[2],
                'from_minute' => (int)$matches[3],
                'is_valid' => true
            ];
        }

        // FMZZXX format (hour + minute, no day specified)
        if (preg_match('/^FM(\d{4})$/', $str, $matches)) {
            return [
                'from_hour' => (int)substr($matches[1], 0, 2),
                'from_minute' => (int)substr($matches[1], 2, 2),
                'is_valid' => true
            ];
        }

        return null;
    }
}

/**
 * Main Decoder class for METAR/SPECI/TAF
 */
class Decoder {

    /**
     * Parse a complete METAR, SPECI, or TAF report
     *
     * @param string $report The raw METAR/SPECI/TAF string
     * @return array Parsed report data
     */
    public function parse(string $report): array {
        // Normalize the report (remove extra spaces, convert to uppercase)
        $report = strtoupper(trim($report));

        // Determine report type
        $reportType = $this->detectReportType($report);

        // Initialize result
        $result = [
            'type' => $reportType,
            'station' => null,
            'is_speci' => false,
            'is_automated' => false,
            'is_amended' => false,
            'is_cancelled' => false,
            'is_nil' => false,
            'is_cavok' => false,
            'observation_time' => null,
            'wind' => [
                'direction' => null,
                'direction_type' => null,
                'speed' => null,
                'gust' => null,
                'unit' => 'kt'
            ],
            'visibility' => [
                'prevailing' => null,
                'minimum' => null,
                'maximum' => null,
                'directional' => [],
                'unit' => 'm'
            ],
            'runway_visibility' => [],
            'weather' => [],
            'clouds' => [],
            'temperature' => null,
            'dewpoint' => null,
            'pressure' => null,
            'pressure_unit' => 'hpa',
            'trend' => [],
            'remarks' => [],
            'remarks_raw' => [],
            'raw' => $report
        ];

        // Split into tokens
        $tokens = $this->tokenize($report);

        $state = 'header'; // header, metar, taf, trend, remarks
        $i = 0;
        $n = count($tokens);

        while ($i < $n) {
            $token = $tokens[$i];

            switch ($state) {
                case 'header':
                    if ($this->isLocation($token)) {
                        $result['station'] = $token;
                        $state = ($reportType === 'taf') ? 'taf' : 'metar';
                    } elseif ($token === 'SPECI') {
                        $result['is_speci'] = true;
                    } elseif ($token === 'AUTO') {
                        $result['is_automated'] = true;
                    } elseif ($token === 'COR') {
                        $result['is_amended'] = true;
                    } elseif ($token === 'NIL') {
                        $result['is_nil'] = true;
                    }
                    break;

                case 'metar':
                case 'taf':
                    // Check for time
                    if ($result['observation_time'] === null && $this->isTime($token)) {
                        $result['observation_time'] = MetafTimeParser::parse($token);
                    }
                    // Check for wind
                    elseif ($this->isWind($token)) {
                        $result['wind'] = $this->parseWind($token);
                    }
                    // Check for variable wind direction
                    elseif ($token === 'VRB') {
                        $result['wind']['direction'] = 'VRB';
                        $result['wind']['direction_type'] = 'variable';
                    }
                    // Check for CAVOK
                    elseif ($token === 'CAVOK') {
                        $result['is_cavok'] = true;
                        $result['visibility']['prevailing'] = 10000;
                        $result['wind']['direction'] = 'VRB';
                        $result['wind']['direction_type'] = 'variable';
                    }
                    // Check for variable wind direction (e.g., "130V200")
                    elseif ($this->isVariableWindDirection($token)) {
                        $varWind = $this->parseVariableWindDirection($token);
                        $result['wind']['variable_from'] = $varWind['variable_from'] ?? null;
                        $result['wind']['variable_to'] = $varWind['variable_to'] ?? null;
                        $result['wind']['direction_type'] = 'variable_range';
                    }
                    // Check for visibility (meters)
                    elseif ($this->isVisibilityMeters($token)) {
                        $vis = $this->parseVisibilityMeters($token);
                        $result['visibility']['prevailing'] = $vis;
                    }
                    // Check for visibility (statute miles)
                    elseif ($this->isVisibilityMiles($token)) {
                        $vis = $this->parseVisibilityMiles($token);
                        $result['visibility']['prevailing'] = $vis * 1609.34; // Convert to meters
                    }
                    // Check for directional visibility (e.g., 4400SE, 1500NE)
                    elseif ($this->isDirectionalVisibility($token)) {
                        $dirVis = $this->parseDirectionalVisibility($token);
                        if ($dirVis['visibility'] !== null) {
                            $result['visibility']['directional'][] = [
                                'visibility' => $dirVis['visibility'],
                                'direction' => $dirVis['direction']
                            ];
                        }
                    }
                    // Check for RVR (runway visual range)
                    elseif ($this->isRVR($token)) {
                        $result['runway_visibility'][] = $this->parseRVR($token);
                    }
                    // Check for weather phenomena
                    elseif ($this->isWeather($token)) {
                        $weather = WeatherPhenomenaParser::parse($token);
                        if ($weather) {
                            $result['weather'][] = $weather;
                        }
                    }
                    // Check for clouds
                    elseif ($this->isCloud($token)) {
                        $cloud = CloudGroupParser::parse($token);
                        if ($cloud) {
                            $result['clouds'][] = $cloud;
                        }
                    }
                    // Check for vertical visibility (e.g., "VV010" or "VV///")
                    elseif (preg_match('/^VV(\d{3}|\/{3})$/', $token)) {
                        $height = null;
                        if (preg_match('/^VV(\d{3})$/', $token, $matches)) {
                            $height = (int)$matches[1] * 100;
                        }
                        $result['clouds'][] = [
                            'type' => 'vertical_visibility',
                            'height' => $height,
                            'is_vertical_visibility' => true
                        ];
                    }
                    // Check for temperature/dewpoint (format: "24/22" or "M02/M05")
                    elseif ($this->isTemperatureDewpoint($token)) {
                        $temps = $this->parseTemperatureDewpoint($token);
                        $result['temperature'] = $temps['temperature'];
                        $result['dewpoint'] = $temps['dewpoint'];
                    }
                    // Check for pressure
                    elseif ($this->isPressure($token)) {
                        $pressure = Pressure::parse($token);
                        if ($pressure) {
                            $result['pressure'] = $pressure->value;
                            $result['pressure_unit'] = $pressure->unit;
                        }
                    }
                    // Check for remarks
                    elseif ($token === 'RMK' || $token === 'NOSIG') {
                        $state = 'remarks';
                        if ($token === 'NOSIG') {
                            $result['trend'][] = ['type' => 'nosig'];
                        }
                    }
                    // Check for trend (BECMG, TEMPO, INTER, FM)
                    elseif ($token === 'BECMG' || $token === 'TEMPO' || $token === 'INTER' || $token === 'FM') {
                        $state = 'trend';
                        $result['trend'][] = ['type' => strtolower($token)];
                    }
                    // Check for FM with time (FM091400, FM100300) - create new FM trend
                    elseif (preg_match('/^FM\d{4,6}$/', $token)) {
                        $state = 'trend';
                        $result['trend'][] = ['type' => 'fm'];
                        // Parse the time into the new trend
                        $result['trend'][count($result['trend']) - 1]['time'] = MetafTimeParser::parseTimeSpan($token);
                    }
                    // Check for PROB## (probability) - don't create trend yet, just mark pending
                    elseif (preg_match('/^PROB(\d{2})$/', $token, $matches)) {
                        $state = 'trend';
                        // Store probability for the next TEMPO/BECMG trend
                        // Don't create trend entry yet - wait for TEMPO or BECMG
                        $result['trend_pending_prob'] = (int)$matches[1];
                    }
                    break;

                case 'trend':
                    // Continue parsing trend information
                    // Check for PROB## (probability) - combine with existing TEMPO/BECMG
                    if (preg_match('/^PROB(\d{2})$/', $token, $matches)) {
                        // Attach probability to the last trend
                        $lastIdx = count($result['trend']) - 1;
                        if ($lastIdx >= 0) {
                            $result['trend'][$lastIdx]['probability'] = (int)$matches[1];
                        }
                    }
                    // Check for new trend starting (another TEMPO, BECMG, INTER, FM)
                    // Check if there's a pending probability from earlier PROB
                    elseif ($token === 'TEMPO' || $token === 'BECMG' || $token === 'INTER' || $token === 'FM') {
                        $trendData = ['type' => strtolower($token)];
                        // Check if there was a pending probability (PROB came before TEMPO/BECMG)
                        if (!empty($result['trend_pending_prob'])) {
                            $trendData['probability'] = $result['trend_pending_prob'];
                            unset($result['trend_pending_prob']);
                        }
                        $result['trend'][] = $trendData;
                    }
                    // Check for trend time format (DDHH/DDHH or FMHHMM or FM DDHHMM)
                    elseif (preg_match('/^\d{4}\/\d{4}$/', $token, $matches)) {
                        // If there's a pending probability but no trend created yet, create one
                        if (!empty($result['trend_pending_prob']) && count($result['trend']) === 0) {
                            $result['trend'][] = ['type' => 'tempo', 'probability' => $result['trend_pending_prob']];
                            unset($result['trend_pending_prob']);
                        }
                        $result['trend'][count($result['trend']) - 1]['time'] = MetafTimeParser::parseTimeSpan($token);
                    }
                    // FM with time in trend state (FM091400) - creates new FM trend
                    elseif (preg_match('/^FM\d{4,6}$/', $token)) {
                        // Create new FM trend
                        $result['trend'][] = ['type' => 'fm'];
                        // Parse the time into the new trend
                        $result['trend'][count($result['trend']) - 1]['time'] = MetafTimeParser::parseTimeSpan($token);
                    } elseif ($this->isTime($token)) {
                        $result['trend'][count($result['trend']) - 1]['time'] = MetafTimeParser::parseTimeSpan($token);
                    } elseif ($this->isWind($token)) {
                        // If there's a pending probability but no trend created yet, create one
                        if (!empty($result['trend_pending_prob']) && count($result['trend']) === 0) {
                            $result['trend'][] = ['type' => 'tempo', 'probability' => $result['trend_pending_prob']];
                            unset($result['trend_pending_prob']);
                        }
                        $result['trend'][count($result['trend']) - 1]['wind'] = $this->parseWind($token);
                    } elseif ($this->isVisibilityMeters($token)) {
                        // If there's a pending probability but no trend created yet, create one
                        if (!empty($result['trend_pending_prob']) && count($result['trend']) === 0) {
                            $result['trend'][] = ['type' => 'tempo', 'probability' => $result['trend_pending_prob']];
                            unset($result['trend_pending_prob']);
                        }
                        $result['trend'][count($result['trend']) - 1]['visibility'] = $this->parseVisibilityMeters($token);
                    } elseif ($this->isVisibilityMiles($token)) {
                        // If there's a pending probability but no trend created yet, create one
                        if (!empty($result['trend_pending_prob']) && count($result['trend']) === 0) {
                            $result['trend'][] = ['type' => 'tempo', 'probability' => $result['trend_pending_prob']];
                            unset($result['trend_pending_prob']);
                        }
                        // Convert statute miles to meters for trends
                        $miles = $this->parseVisibilityMiles($token);
                        $result['trend'][count($result['trend']) - 1]['visibility'] = (int)($miles * 1609.34);
                    } elseif ($this->isCloud($token)) {
                        // If there's a pending probability but no trend created yet, create one
                        if (!empty($result['trend_pending_prob']) && count($result['trend']) === 0) {
                            $result['trend'][] = ['type' => 'tempo', 'probability' => $result['trend_pending_prob']];
                            unset($result['trend_pending_prob']);
                        }
                        $cloud = CloudGroupParser::parse($token);
                        if ($cloud) {
                            $result['trend'][count($result['trend']) - 1]['clouds'][] = $cloud;
                        }
                    } elseif ($this->isWeatherPhenomenon($token)) {
                        // If there's a pending probability but no trend created yet, create one
                        if (!empty($result['trend_pending_prob']) && count($result['trend']) === 0) {
                            $result['trend'][] = ['type' => 'tempo', 'probability' => $result['trend_pending_prob']];
                            unset($result['trend_pending_prob']);
                        }
                        // Handle weather phenomena in trends (SHRA, DZ, BCFG, etc.)
                        $weather = $this->parseWeatherPhenomenon($token);
                        if ($weather) {
                            $result['trend'][count($result['trend']) - 1]['weather'][] = $weather;
                        }
                    } elseif ($token === 'RMK') {
                        $state = 'remarks';
                    }
                    break;

                case 'remarks':
                    $result['remarks_raw'][] = $token;
                    break;
            }

            $i++;
        }

        // Clean up any pending probability that wasn't attached to a trend
        unset($result['trend_pending_prob']);

        // Parse remarks section into structured data
        if (!empty($result['remarks_raw'])) {
            $result['remarks'] = $this->parseRemarksSection($result['remarks_raw']);
        } else {
            $result['remarks'] = [
                'automated_station_type' => null,
                'sea_level_pressure' => null,
                'pressure_tendency' => null,
                'precise_temperature' => null,
                'precise_dewpoint' => null,
                'peak_wind' => null,
                'precipitation' => null,
                'ceiling' => null,
                'thunderstorm' => null,
                'runway_visibility_remarks' => [],
                'other' => []
            ];
        }

        // Calculate relative humidity if both temperature and dewpoint are available
        // Use precise temperature if available, otherwise use regular temperature
        $temp = $result['remarks']['precise_temperature'] ?? $result['temperature'];
        $dew = $result['remarks']['precise_dewpoint'] ?? $result['dewpoint'];

        if ($temp !== null && $dew !== null) {
            $tempObj = new Temperature($temp);
            $dewObj = new Temperature($dew);
            $result['relative_humidity'] = round(Temperature::relativeHumidity($tempObj, $dewObj), 1);
        }

        return $result;
    }

    /**
     * Detect report type (METAR, SPECI, or TAF)
     */
    private function detectReportType(string $report): string {
        if (preg_match('/^\s*TAF\s/', $report)) {
            return ReportType::TAF;
        }
        if (preg_match('/^\s*SPECI\s/', $report)) {
            return 'speci';
        }
        if (preg_match('/^\s*METAR\s/', $report)) {
            return ReportType::METAR;
        }
        return ReportType::UNKNOWN;
    }

    /**
     * Tokenize report string
     */
    private function tokenize(string $report): array {
        // Remove report type prefix if present
        $report = preg_replace('/^(METAR|SPECI|TAF)\s*/', '', $report);

        // Remove anything after = (end of report)
        $report = preg_replace('/=.*$/', '', $report);

        // Split by spaces but keep grouped tokens
        $tokens = preg_split('/\s+/', $report);

        // Post-process tokens to combine fractional visibility (e.g., "1 1/2SM" -> "1 1/2SM" stays separate but we handle it during parsing)
        // Actually, let's combine them: "1 1/2SM" -> "1 1/2SM" is two tokens
        // We need to detect and combine: digit followed by fraction-SM

        $result = [];
        $n = count($tokens);
        for ($i = 0; $i < $n; $i++) {
            $token = $tokens[$i];

            // Check if we have a fractional visibility pattern: "1" followed by "1/2SM" or similar
            if (preg_match('/^\d+$/', $token) && isset($tokens[$i + 1])) {
                $nextToken = $tokens[$i + 1];
                // Check if next token is a fractional visibility (e.g., "1/2SM", "1/4SM")
                if (preg_match('/^\d+\/\d+SM$/', $nextToken)) {
                    // Combine them: "1" + "1/2SM" -> "1 1/2SM"
                    $result[] = $token . ' ' . $nextToken;
                    $i++; // Skip the next token since we combined it
                    continue;
                }
            }

            $result[] = $token;
        }

        return array_filter($result);
    }

    /**
     * Check if token is a location (ICAO code)
     */
    private function isLocation(string $token): bool {
        return (bool)preg_match('/^[A-Z]{4}$/', $token) &&
               !in_array($token, ['METAR', 'SPECI', 'TAF', 'AUTO', 'COR', 'NIL', 'RMK', 'NOSIG', 'CAVOK']);
    }

    /**
     * Check if token is a time
     */
    private function isTime(string $token): bool {
        return (bool)preg_match('/^\d{6}Z$/', $token);
    }

    /**
     * Check if token is wind
     */
    private function isWind(string $token): bool {
        // Regular wind: dddddKT or dddddGggKT (e.g., "36010KT", "27015G25KT")
        // Variable wind: VRBddKT or VRBddGggKT (e.g., "VRB02KT", "VRB05G10KT")
        // Malformed variable wind (VBR instead of VRB): VBRddKT or VBRddGggKT - handle as probable issue
        // Calm wind: 00000KT
        return (bool)preg_match('/^\d{5}(G\d{2})?KT$/', $token) ||
               (bool)preg_match('/^VRB\d{2}(G\d{2})?KT$/', $token) ||
               (bool)preg_match('/^VBR\d{2}(G\d{2})?KT$/', $token) ||  // Handle malformed VBR as probable VRB
               (bool)preg_match('/^00000KT$/', $token);
    }

    /**
     * Check if token is variable wind direction (dddVddd)
     */
    private function isVariableWindDirection(string $token): bool {
        return (bool)preg_match('/^\d{3}V\d{3}$/', $token);
    }

    /**
     * Parse variable wind direction (e.g., "130V200")
     */
    private function parseVariableWindDirection(string $token): array {
        if (preg_match('/^(\d{3})V(\d{3})$/', $token, $matches)) {
            return [
                'variable_from' => (int)$matches[1],
                'variable_to' => (int)$matches[2],
                'direction_type' => 'variable_range'
            ];
        }
        return [];
    }

    /**
     * Parse wind group
     */
    private function parseWind(string $token): array {
        $wind = [
            'direction' => null,
            'direction_type' => 'degrees',
            'speed' => null,
            'gust' => null,
            'unit' => 'kt'
        ];

        // Calm wind (00000KT)
        if ($token === '00000KT') {
            $wind['direction'] = 0;
            $wind['speed'] = 0;
            return $wind;
        }

        // Variable wind: VRBddKT or VRBddGggKT (e.g., "VRB02KT", "VRB05G10KT")
        if (preg_match('/^VRB(\d{2})(G(\d{2}))?KT$/', $token, $matches)) {
            $wind['direction'] = 'VRB';
            $wind['direction_type'] = 'variable';
            $wind['speed'] = (int)$matches[1];
            if (isset($matches[3])) {
                $wind['gust'] = (int)$matches[3];
            }
            return $wind;
        }

        // Malformed variable wind: VBRddKT or VBRddGggKT (e.g., "VBR02KT") - treat as probable VRB
        if (preg_match('/^VBR(\d{2})(G(\d{2}))?KT$/', $token, $matches)) {
            $wind['direction'] = 'VRB';
            $wind['direction_type'] = 'variable';
            $wind['speed'] = (int)$matches[1];
            $wind['malformed'] = true;  // Flag as malformed token (VBR instead of VRB)
            $wind['original'] = $token;  // Keep original token for reference
            if (isset($matches[3])) {
                $wind['gust'] = (int)$matches[3];
            }
            return $wind;
        }

        // Regular wind: dddddKT or dddddGggKT
        if (preg_match('/^(\d{3})(\d{2})(G(\d{2}))?KT$/', $token, $matches)) {
            $wind['direction'] = (int)$matches[1];
            $wind['speed'] = (int)$matches[2];
            if (isset($matches[4])) {
                $wind['gust'] = (int)$matches[4];
            }
        }

        return $wind;
    }

    /**
     * Check if token is visibility in meters
     * Supports: 9999, 0600, 0600NDV, etc.
     */
    private function isVisibilityMeters(string $token): bool {
        // Standard 4-digit visibility: 0000-9999
        if (preg_match('/^(\d{4})$/', $token) && (int)$token >= 0) {
            return true;
        }
        // Visibility with NDV (No Directional Variation): 0600NDV
        if (preg_match('/^\d{4}NDV$/', $token)) {
            return true;
        }
        return false;
    }

    /**
     * Parse visibility in meters
     */
    private function parseVisibilityMeters(string $token): int {
        // Handle NDV (No Directional Variation)
        $token = str_replace('NDV', '', $token);
        return (int)$token;
    }

    /**
     * Check if token has NDV (No Directional Variation) modifier
     */
    private function hasNDV(string $token): bool {
        return strpos($token, 'NDV') !== false;
    }

    /**
     * Check if token is directional visibility (e.g., 4400SE, 1500NE)
     * Format: 4 digits + direction (N, NE, E, SE, S, SW, W, NW)
     */
    private function isDirectionalVisibility(string $token): bool {
        return (bool)preg_match('/^\d{4}[NSEW]{1,2}$/', $token);
    }

    /**
     * Parse directional visibility
     * Returns array with visibility value and direction
     */
    private function parseDirectionalVisibility(string $token): array {
        $result = [
            'visibility' => null,
            'direction' => null
        ];

        // Match 4 digits followed by 1-2 letter direction
        if (preg_match('/^(\d{4})([NSEW]{1,2})$/', $token, $matches)) {
            $result['visibility'] = (int)$matches[1];
            $result['direction'] = $matches[2];
        }

        return $result;
    }

    /**
     * Check if token is visibility in statute miles
     * Supports: 6SM, 10SM, 2SM, 1/2SM, P6SM (greater than 6SM), M1/4SM (less than 1/4SM)
     */
    private function isVisibilityMiles(string $token): bool {
        return (bool)preg_match('/^[PM]?\d+(\s+\d+\/\d+)?SM$/', $token) ||
               (bool)preg_match('/^[PM]?\d+\/\d+SM$/', $token);
    }

    /**
     * Parse visibility in statute miles
     */
    private function parseVisibilityMiles(string $token): float {
        // P6SM = greater than 6 SM, M1/4SM = less than 1/4 SM
        $isGreater = strpos($token, 'P') === 0;
        $isLess = strpos($token, 'M') === 0;
        $token = preg_replace('/^[PM]/', '', $token);

        if (preg_match('/^(\d+)\s+(\d+)\/(\d+)SM$/', $token, $matches)) {
            return (float)$matches[1] + (float)$matches[2] / (float)$matches[3];
        }
        if (preg_match('/^(\d+)\/(\d+)SM$/', $token, $matches)) {
            return (float)$matches[1] / (float)$matches[2];
        }
        return (float)preg_replace('/SM$/', '', $token);
    }

    /**
     * Check if token is RVR (Runway Visual Range)
     * Supports: R02/2000, R02/P2000U, R02/M1000N, R18L/1500D, etc.
     * Format: R[runway][LRC]?/[P|M]?[digits][trend]?
     */
    private function isRVR(string $token): bool {
        // Basic RVR: R02/2000
        if (preg_match('/^R\d{2}[LRC]?\/\d{4}$/', $token)) {
            return true;
        }
        // RVR with P (greater than) or M (less than): R02/P2000, R02/M1000
        if (preg_match('/^R\d{2}[LRC]?\/[PM]\d{4}$/', $token)) {
            return true;
        }
        // RVR with trend: R02/2000U, R02/2000D, R02/2000N
        if (preg_match('/^R\d{2}[LRC]?\/\d{4}[UDN]$/', $token)) {
            return true;
        }
        // RVR with P/M and trend: R02/P2000U, R02/M1000N
        if (preg_match('/^R\d{2}[LRC]?\/[PM]\d{4}[UDN]$/', $token)) {
            return true;
        }
        return false;
    }

    /**
     * Parse RVR
     * Format: R[runway][LRC]?/[P|M][visibility][trend]?
     * Examples: R02/2000, R02/P2000U, R02/M1000N, R18L/1500D
     */
    private function parseRVR(string $token): array {
        $rvr = [
            'runway' => null,
            'visual_range' => null,
            'trend' => null
        ];

        // Match: R(runway)(LRC?) / (P|M)? (visibility) (trend)?
        // Examples: R02/2000, R02/P2000U, R02/M1000N
        if (preg_match('/^R(\d{2})([LRC]?)\/([PM]?)(\d{4})([UDN]?)$/', $token, $matches)) {
            $rvr['runway'] = $matches[1] . ($matches[2] ?: '');

            $visFeet = (int)$matches[4];
            $prefix = $matches[3]; // P = greater than, M = less than

            // Convert feet to meters
            $visMeters = $visFeet * 0.3048;

            // If P (greater than), we store the actual value
            // If M (less than), we store the actual value
            $rvr['visual_range'] = $visMeters;

            // Store the prefix info
            if ($prefix === 'P') {
                $rvr['greater_than'] = $visFeet;
            } elseif ($prefix === 'M') {
                $rvr['less_than'] = $visFeet;
            }

            if (!empty($matches[5])) {
                $trends = ['D' => 'down', 'U' => 'up', 'N' => 'no_change'];
                $rvr['trend'] = $trends[$matches[5]] ?? null;
            }
        }

        return $rvr;
    }

    /**
     * Check if token is weather phenomena
     */
    private function isWeather(string $token): bool {
        $weatherPatterns = [
            '^[+\-]?DZ$', '^[+\-]?RA$', '^[+\-]?SN$', '^[+\-]?SG$', '^[+\-]?IC$',
            '^[+\-]?PL$', '^[+\-]?GR$', '^[+\-]?GS$', '^FZ(DZ|RA)$', '^UP$',
            '^[+\-]?BR$', '^[+\-]?FG$', '^FU$', '^VA$', '^[+\-]?DU$',
            '^[+\-]?SA$', '^[+\-]?HZ$', '^PO$', '^[+\-]?SQ$', '^FC$',
            '^[+\-]?SS$', '^[+\-]?DS$', '^[+\-]?SH\w+$', '^[+\-]?TS\w+$',
            '^VC\w+$', '^RE\w+$', '^BC\w+$'
        ];

        foreach ($weatherPatterns as $pattern) {
            if (preg_match('/' . $pattern . '/', $token)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if token is weather phenomena (including descriptors like SH, TS, BC, etc.)
     */
    private function isWeatherPhenomenon(string $token): bool {
        // Patterns for weather phenomena including:
        // - Basic: DZ, RA, SN, BR, FG, etc.
        // - With intensity: +RA, -DZ, etc.
        // - With descriptor: SHRA, SHRASN, TSB, etc.
        // - With intensity + descriptor: +SHRA, -TSRA, etc.
        // - BC (patches), MI (shallow), etc.
        // - Standalone: BR, FG, HZ, etc.
        // - Vicinity: VCTS, VCBR, VCFG, etc.
        $patterns = [
            '^(MI|PR|BC|CM|CG|SH|TS|FZ|VC)?(DZ|RA|SN|SG|IC|PL|GR|GS|UP|BR|FG|FU|DU|SA|HZ|PO|SQ|FC|SS|DS|VA|TS)$',
            '^\+?(DZ|RA|SN|SG|IC|PL|GR|GS|UP|BR|FG|FU|DU|SA|HZ|PO|SQ|FC|SS|DS|VA)$',
            '^\-?(DZ|RA|SN|SG|IC|PL|GR|GS|UP|BR|FG|FU|DU|SA|HZ|PO|SQ|FC|SS|DS|VA)$',
            '^(SH|TS)(RA|SN|DZ|SG|IC|PL|GR|GS)$',
            '^\+?(SH|TS)(RA|SN|DZ|SG|IC|PL|GR|GS)$',
            '^\-?(SH|TS)(RA|SN|DZ|SG|IC|PL|GR|GS)$',
            '^(TS|SH|FZ|MI|BC|PR|CM|CG|VC)(DZ|RA|SN|SG|IC|PL|GR|GS|BR|FG|FU|DU|SA|HZ|PO|SQ|FC|SS|DS|VA|TS)$',
            '^\+(TS|SH|FZ|MI|BC|PR|CM|CG|VC)(DZ|RA|SN|SG|IC|PL|GR|GS|BR|FG|FU|DU|SA|HZ|PO|SQ|FC|SS|DS|VA|TS)$',
            '^VC(DZ|RA|SN|SG|IC|PL|GR|GS|BR|FG|FU|DU|SA|HZ|PO|SQ|FC|SS|DS|VA|TS)$'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match('/' . $pattern . '/', $token)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parse weather phenomenon token
     */
    private function parseWeatherPhenomenon(string $token): ?array {
        if (!$this->isWeatherPhenomenon($token)) {
            return null;
        }

        $phenomenon = [
            'qualifier' => 'none',
            'descriptor' => 'none',
            'phenomenon' => 'unknown'
        ];

        // Check for intensity
        if (strpos($token, '+') === 0) {
            $phenomenon['qualifier'] = 'heavy';
            $token = substr($token, 1);
        } elseif (strpos($token, '-') === 0) {
            $phenomenon['qualifier'] = 'light';
            $token = substr($token, 1);
        }

        // Descriptor patterns
        $descriptors = ['SH', 'TS', 'FZ', 'MI', 'BC', 'PR', 'CM', 'CG'];
        foreach ($descriptors as $desc) {
            if (strpos($token, $desc) === 0) {
                $phenomenon['descriptor'] = strtolower($desc);
                $token = substr($token, strlen($desc));
                break;
            }
        }

        // Phenomenon
        $phenomena = [
            'DZ' => 'drizzle', 'RA' => 'rain', 'SN' => 'snow',
            'SG' => 'snow_grains', 'IC' => 'ice_crystals', 'PL' => 'ice_pellets',
            'GR' => 'hail', 'GS' => 'small_hail', 'UP' => 'unknown_precipitation',
            'BR' => 'mist', 'FG' => 'fog', 'FU' => 'smoke',
            'DU' => 'dust', 'SA' => 'sand', 'HZ' => 'haze',
            'PO' => 'dust_whirls', 'SQ' => 'squalls', 'FC' => 'funnel_cloud',
            'SS' => 'sandstorm', 'DS' => 'duststorm', 'VA' => 'volcanic_ash'
        ];

        if (isset($phenomena[$token])) {
            $phenomenon['phenomenon'] = $phenomena[$token];
        } else {
            $phenomenon['phenomenon'] = strtolower($token);
        }

        return $phenomenon;
    }

    /**
     * Check if token is a cloud group
     */
    private function isCloud(string $token): bool {
        // Supports: FEW040, SCT020, BKN050, OVC100
        // Also supports with cloud type: FEW040CB, SCT020TCU, BKN050CB, OVC100TCU
        // Also supports without cloud type: FEW038///, SCT048///, BKN110/// (/// = no type detected)
        $cloudPatterns = [
            '^NCD$',
            '^NSC$',
            '^SKC$',
            '^(FEW|SCT|BKN|OVC)\d{3}$',
            '^(FEW|SCT|BKN|OVC)\d{3}(CB|TCU|CU|CF|SC|NS|ST)$',
            '^(FEW|SCT|BKN|OVC)\d{3}\/\/\/$'  // Cloud with no type detected
        ];
        foreach ($cloudPatterns as $pattern) {
            if (preg_match('/' . $pattern . '/', $token)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if token is temperature
     */
    private function isTemperature(string $token): bool {
        return (bool)preg_match('/^M?\d{2}$/', $token);
    }

    /**
     * Check if token is dewpoint
     */
    private function isDewpoint(string $token): bool {
        return $this->isTemperature($token);
    }

    /**
     * Parse temperature
     */
    private function parseTemperature(string $token): float {
        if (preg_match('/^M(\d{2})$/', $token, $matches)) {
            return -(float)$matches[1];
        }
        return (float)$token;
    }

    /**
     * Check if token is pressure
     */
    private function isPressure(string $token): bool {
        return (bool)preg_match('/^A\d{4}$/', $token) ||
               (bool)preg_match('/^Q\d{4}$/', $token);
    }

    /**
     * Parse the entire remarks section into structured data
     */
    private function parseRemarksSection(array $tokens): array {
        $remarks = [
            'automated_station_type' => null,
            'sea_level_pressure' => null,
            'pressure_tendency' => null,
            'precise_temperature' => null,
            'precise_dewpoint' => null,
            'peak_wind' => null,
            'precipitation' => null,
            'ceiling' => null,
            'thunderstorm' => null,
            'runway_visibility_remarks' => [],
            'other' => []
        ];

        $n = count($tokens);
        $i = 0;

        while ($i < $n) {
            $token = $tokens[$i];

            // Automated station type: AO1, AO2, AO3, A01, A02
            if ($this->isAutomatedStationType($token)) {
                $remarks['automated_station_type'] = $token;
                $i++;
                continue;
            }

            // Sea Level Pressure: SLP###
            if ($this->isSeaLevelPressure($token)) {
                $remarks['sea_level_pressure'] = $this->parseSeaLevelPressure($token);
                $i++;
                continue;
            }

            // Pressure Tendency: 5####
            if ($this->isPressureTendency($token)) {
                $remarks['pressure_tendency'] = $this->parsePressureTendency($token);
                $i++;
                continue;
            }

            // Precise Temperature: T########
            if ($this->isPreciseTemperature($token)) {
                $temps = $this->parsePreciseTemperature($token);
                if ($temps !== null) {
                    $remarks['precise_temperature'] = $temps['temperature'];
                    $remarks['precise_dewpoint'] = $temps['dewpoint'];
                }
                $i++;
                continue;
            }

            // Peak Wind: PK WND
            if ($token === 'PK' && isset($tokens[$i + 1]) && $tokens[$i + 1] === 'WND') {
                // Collect all PK WND tokens
                $pkwnd_tokens = [];
                while ($i < $n && $tokens[$i] !== 'RMK') {
                    $pkwnd_tokens[] = $tokens[$i];
                    $i++;
                }
                $pkwnd_str = implode(' ', $pkwnd_tokens);
                if ($this->isPeakWind($pkwnd_str)) {
                    $remarks['peak_wind'] = $this->parsePeakWind($pkwnd_str);
                }
                $i--;
                $i++;
                continue;
            }

            // Precipitation: P####
            if ($this->isPrecipitation($token)) {
                $remarks['precipitation'] = $this->parsePrecipitation($token);
                $i++;
                continue;
            }

            // Ceiling: CIG
            if ($token === 'CIG' && isset($tokens[$i + 1])) {
                $remarks['ceiling'] = $this->parseCeiling($tokens[$i] . ' ' . $tokens[$i + 1]);
                $i += 2;
                continue;
            }

            // Thunderstorm: TSNO, TSB, TSD, TSE
            if ($this->isThunderstorm($token)) {
                $remarks['thunderstorm'] = $this->parseThunderstorm($token);
                $i++;
                continue;
            }

            // RVR in remarks (alternative format)
            if ($this->isRVR($token)) {
                $remarks['runway_visibility_remarks'][] = $this->parseRVR($token);
                $i++;
                continue;
            }

            // Max temperature (1#####): Max temp in past 6 hours
            if ($this->isMaxMinTemperature($token, 'max')) {
                $remarks['max_temperature_6h'] = $this->parseMaxMinTemperature($token, 'max');
                $i++;
                continue;
            }

            // Min temperature (2#####): Min temp in past 6 hours
            if ($this->isMaxMinTemperature($token, 'min')) {
                $remarks['min_temperature_6h'] = $this->parseMaxMinTemperature($token, 'min');
                $i++;
                continue;
            }

            // First station maintenance indicator
            if ($token === '$') {
                $remarks['other'][] = 'maintenance_required';
                $i++;
                continue;
            }

            // Other tokens we don't recognize
            $remarks['other'][] = $token;
            $i++;
        }

        return $remarks;
    }

    /**
     * Check if token is automated station type (AO1, AO2, AO3, A01, A02)
     */
    private function isAutomatedStationType(string $token): bool {
        return (bool)preg_match('/^A0[12]$/', $token) ||
               (bool)preg_match('/^AO[123]$/', $token);
    }

    /**
     * Check if token is sea level pressure (SLP###)
     */
    private function isSeaLevelPressure(string $token): bool {
        return (bool)preg_match('/^SLP\d{3}$/', $token);
    }

    /**
     * Parse sea level pressure
     * SLP###: If ### < 500, add 10; if ### >= 500, add 9
     * Example: SLP082 = 1008.2 mb, SLP995 = 999.5 mb
     */
    private function parseSeaLevelPressure(string $token): ?float {
        if (preg_match('/^SLP(\d{3})$/', $token, $matches)) {
            $value = (int)$matches[1];
            if ($value < 500) {
                return 1000 + $value / 10; // 1000.0 to 1049.9
            } else {
                return 900 + $value / 10; // 950.0 to 999.9
            }
        }
        return null;
    }

    /**
     * Check if token is pressure tendency (5####)
     */
    private function isPressureTendency(string $token): bool {
        return (bool)preg_match('/^5\d{4}$/', $token);
    }

    /**
     * Parse pressure tendency
     * 5 + characteristic code + 3-digit change
     * Example: 53002 = code 3, change 0.2 mb (pressure rose then fell)
     */
    private function parsePressureTendency(string $token): ?array {
        if (preg_match('/^5(\d)(\d{3})$/', $token, $matches)) {
            $characteristic_codes = [
                0 => 'continuing_decreasing',
                1 => 'continuing_increasing',
                2 => 'continuing_steady',
                3 => 'becoming_decreasing',
                4 => 'becoming_increasing',
                5 => 'becoming_steady',
                6 => 'increasing_then_decreasing',
                7 => 'decreasing_then_increasing',
                8 => 'fluctuating'
            ];
            return [
                'characteristic_code' => (int)$matches[1],
                'characteristic' => $characteristic_codes[(int)$matches[1]] ?? 'unknown',
                'change_mb' => (float)$matches[2] / 10
            ];
        }
        return null;
    }

    /**
     * Check if token is precise temperature (T########)
     * Format: T[sign][ttt][sign][ddd]
     * T01610150 = temp 16.1°C, dewpoint 15.0°C
     * T10221089 = temp -10.2°C, dewpoint -8.9°C
     * T01560032 = temp 15.6°C, dewpoint 3.2°C
     */
    private function isPreciseTemperature(string $token): bool {
        // Must start with T followed by exactly 8 digits
        return (bool)preg_match('/^T\d{8}$/', $token);
    }

    /**
     * Parse precise temperature and dewpoint
     * Format: T[sign][ttt][sign][ddd]
     * Example: T01560032 = temp 15.6°C (positive), dewpoint 3.2°C (positive)
     *          T10221089 = temp -10.2°C, dewpoint -8.9°C
     */
    private function parsePreciseTemperature(string $token): ?array {
        // T[1/0][ttt][1/0][ddd]
        // First digit: 0 = positive, 1 = negative
        // Next 3 digits: temperature * 10
        // Next digit: 0 = positive, 1 = negative
        // Last 3 digits: dewpoint * 10
        if (preg_match('/^T([01])(\d{3})([01])(\d{3})$/', $token, $matches)) {
            $temp_sign = $matches[1] === '1' ? -1 : 1;
            $dew_sign = $matches[3] === '1' ? -1 : 1;

            $temperature = $temp_sign * (float)$matches[2] / 10;
            $dewpoint = $dew_sign * (float)$matches[4] / 10;

            return [
                'temperature' => $temperature,
                'dewpoint' => $dewpoint
            ];
        }
        return null;
    }

    /**
     * Check if string contains peak wind information
     */
    private function isPeakWind(string $str): bool {
        return (bool)preg_match('/PK\s+WND\s+\d{3}\d{2,3}\/\d{4}$/', $str);
    }

    /**
     * Parse peak wind
     * PK WND dddff/hhmm
     * Example: PK WND 07045/1752 = direction 070°, speed 45kt, time 17:52
     */
    private function parsePeakWind(string $str): ?array {
        if (preg_match('/PK\s+WND\s+(\d{3})(\d{2,3})\/(\d{2})(\d{2})$/', $str, $matches)) {
            return [
                'direction' => (int)$matches[1],
                'speed' => (int)$matches[2],
                'time_hour' => (int)$matches[3],
                'time_minute' => (int)$matches[4]
            ];
        }
        return null;
    }

    /**
     * Check if token is precipitation accumulation (P####)
     */
    private function isPrecipitation(string $token): bool {
        return (bool)preg_match('/^P\d{4}$/', $token);
    }

    /**
     * Parse precipitation accumulation
     * P#### = precipitation in inches * 100
     * Example: P0124 = 0.124 inches
     */
    private function parsePrecipitation(string $token): ?float {
        if (preg_match('/^P(\d{4})$/', $token, $matches)) {
            return (float)$matches[1] / 10000; // Convert to inches
        }
        return null;
    }

    /**
     * Parse ceiling information (CIG)
     */
    private function parseCeiling(string $str): ?array {
        // CIG ###V### = ceiling range in hundreds of feet
        if (preg_match('/CIG\s+(\d{3})V(\d{3})$/', $str, $matches)) {
            return [
                'minimum_ft' => (int)$matches[1] * 100,
                'maximum_ft' => (int)$matches[2] * 100
            ];
        }
        // CIG ### = single ceiling height
        if (preg_match('/CIG\s+(\d{3})$/', $str, $matches)) {
            return [
                'minimum_ft' => (int)$matches[1] * 100,
                'maximum_ft' => null
            ];
        }
        return null;
    }

    /**
     * Check if token is thunderstorm information
     */
    private function isThunderstorm(string $token): bool {
        return (bool)preg_match('/^TS(N|O|B|D|E)\d*$/', $token);
    }

    /**
     * Parse thunderstorm information
     * TSNO = Thunderstorm not observed
     * TSB## = Thunderstorm began at ##
     * TSD## = Thunderstorm ended at ##
     * TSE## = Thunderstorm ended at ##
     */
    private function parseThunderstorm(string $token): ?array {
        if ($token === 'TSNO') {
            return ['type' => 'not_observed'];
        }
        if (preg_match('/^TSB(\d{2})$/', $token, $matches)) {
            return ['type' => 'began', 'time_hour' => (int)$matches[1]];
        }
        if (preg_match('/^TSD(\d{2})$/', $token, $matches)) {
            return ['type' => 'ended', 'time_hour' => (int)$matches[1]];
        }
        if (preg_match('/^TSE(\d{2})$/', $token, $matches)) {
            return ['type' => 'ended', 'time_hour' => (int)$matches[1]];
        }
        if ($token === 'TS') {
            return ['type' => 'observed'];
        }
        if (preg_match('/^TS(\w+)$/', $token, $matches)) {
            return ['type' => 'observed', 'descriptor' => $matches[1]];
        }
        return null;
    }

    /**
     * Check if token is max/min temperature (1##### or 2#####)
     * Format: 1 + 4 digits for max, 2 + 4 digits for min
     * Example: 10306 = max temp 30.6°C, 20172 = min temp 17.2°C
     */
    private function isMaxMinTemperature(string $token, string $type): bool {
        if ($type === 'max') {
            return (bool)preg_match('/^1\d{4}$/', $token);
        }
        return (bool)preg_match('/^2\d{4}$/', $token);
    }

    /**
     * Parse max/min temperature from 6-hour group
     */
    private function parseMaxMinTemperature(string $token, string $type): ?float {
        // Remove first digit (1 for max, 2 for min)
        $value = substr($token, 1);
        // Last 3 digits represent temperature * 10
        $temp = (float)$value / 10;
        return $temp;
    }

    /**
     * Format decoded data as human-readable string
     */
    public function format(array $data): string {
        $output = [];

        // Station and type
        $output[] = "Station: " . ($data['station'] ?? 'Unknown');
        $output[] = "Type: " . strtoupper($data['type'] ?? 'unknown');
        if (!empty($data['is_speci'])) $output[] = "SPECI Report";
        if (!empty($data['is_automated'])) $output[] = "Automated Station";

        // Observation time
        if (!empty($data['observation_time'])) {
            $t = $data['observation_time'];
            $output[] = "Time: Day {$t['day']} {$t['hour']}:{$t['minute']}Z";
        }

        // Wind
        if (!empty($data['wind']['speed']) || $data['wind']['speed'] === 0) {
            $dir = $data['wind']['direction'];
            $speed = $data['wind']['speed'];
            $gust = $data['wind']['gust'];
            $gustStr = $gust ? "G{$gust}" : "";
            $output[] = "Wind: {$dir}{$gustStr}kt";
        }

        // Visibility
        if ($data['visibility']['prevailing'] !== null) {
            $vis = round($data['visibility']['prevailing']);
            $output[] = "Visibility: {$vis}m";
        }

        // Weather
        if (!empty($data['weather'])) {
            $weatherStr = implode(' ', array_map(function($w) {
                $str = $w['qualifier'] !== 'none' ? $w['qualifier'] . ' ' : '';
                $str .= $w['descriptor'] !== 'none' ? $w['descriptor'] . ' ' : '';
                $str .= $w['phenomenon'];
                return $str;
            }, $data['weather']));
            $output[] = "Weather: {$weatherStr}";
        }

        // Clouds
        if (!empty($data['clouds'])) {
            foreach ($data['clouds'] as $cloud) {
                if (!empty($cloud['amount']) && $cloud['amount'] !== 'none_clr' && $cloud['amount'] !== 'none_skc') {
                    $height = !empty($cloud['height_ft']) ? round($cloud['height_ft'] / 100) * 100 : '///';
                    $output[] = "Clouds: {$cloud['amount']} at {$height}ft";
                }
            }
        }

        // Temperature and dewpoint
        if ($data['temperature'] !== null) {
            $output[] = "Temperature: {$data['temperature']}°C";
        }
        if ($data['dewpoint'] !== null) {
            $output[] = "Dewpoint: {$data['dewpoint']}°C";
        }
        if (!empty($data['relative_humidity'])) {
            $output[] = "Relative Humidity: {$data['relative_humidity']}%";
        }

        // Pressure
        if ($data['pressure'] !== null) {
            $unit = $data['pressure_unit'] === 'inhg' ? 'inHg' : 'hPa';
            $output[] = "Pressure: {$data['pressure']} {$unit}";
        }

        return implode("\n", $output);
    }

    /**
     * Check if token is temperature/dewpoint combined (format: "24/22" or "M02/M05")
     */
    private function isTemperatureDewpoint(string $token): bool {
        return (bool)preg_match('/^M?\d{2}\/M?\d{2}$/', $token);
    }

    /**
     * Parse temperature/dewpoint combined token
     */
    private function parseTemperatureDewpoint(string $token): array {
        $parts = explode('/', $token);
        $result = [
            'temperature' => null,
            'dewpoint' => null
        ];

        if (count($parts) === 2) {
            $result['temperature'] = $this->parseSingleTemperature($parts[0]);
            $result['dewpoint'] = $this->parseSingleTemperature($parts[1]);
        }

        return $result;
    }

    /**
     * Parse single temperature value (e.g., "24" or "M02")
     */
    private function parseSingleTemperature(string $str): float {
        if (preg_match('/^M(\d{2})$/', $str, $matches)) {
            return -(float)$matches[1];
        }
        return (float)$str;
    }

    /**
     * Get a simple array of decoded data
     */
    public function toArray(array $data): array {
        return $data;
    }

    /**
     * Parse a SIGMET message
     * @param string $report Raw SIGMET message
     * @return array|null Parsed SIGMET data or null if invalid
     */
    public function parseSigmet(string $report): ?array {
        return SigmetParser::parse($report);
    }
}

/**
 * SIGMET type enumeration
 */
class SigmetType {
    const UNKNOWN = 'unknown';
    const SIGMET = 'sigmet';
    const AIRMET = 'airmet';
    const GAMET = 'gamet';
    const VOLCANIC_ASH = 'volcanic_ash';
    const TROPICAL_CYCLONE = 'tropical_cyclone';
}

/**
 * SIGMET phenomenon enumeration
 */
class SigmetPhenomenon {
    const NOT_REPORTED = 'not_reported';
    const THUNDERSTORM = 'thunderstorm';
    const TURBULENCE = 'turbulence';
    const ICING = 'icing';
    const MOUNTAIN_WAVE = 'mountain_wave';
    const VOLCANIC_ASH = 'volcanic_ash';
    const DUSTSTORM = 'duststorm';
    const SANDSTORM = 'sandstorm';
    const LOW_LEVEL_WIND_SHEAR = 'low_level_wind_shear';
    const HAIL = 'hail';
    const FUNNEL_CLOUD = 'funnel_cloud';
    const TORNADO = 'tornado';
    const WATERSPOUT = 'waterspout';
    const HEAVY_RAIN = 'heavy_rain';
    const HEAVY_SNOW = 'heavy_snow';
}

/**
 * SIGMET severity enumeration
 */
class SigmetSeverity {
    const NOT_REPORTED = 'not_reported';
    const LIGHT = 'light';
    const MODERATE = 'moderate';
    const SEVERE = 'severe';
    const EXTREME = 'extreme';
}

/**
 * SIGMET Parser class
 * Parses SIGMET (Significant Meteorological Information) messages
 */
class SigmetParser {
    // Human-readable names for SIGMET phenomena
    public static $phenomenonNames = [
        'TS' => 'Thunderstorm',
        'SEV TURB' => 'Severe Turbulence',
        'MOD TURB' => 'Moderate Turbulence',
        'SEV ICE' => 'Severe Icing',
        'MOD ICE' => 'Moderate Icing',
        'SEV MTW' => 'Severe Mountain Wave Turbulence',
        'VA' => 'Volcanic Ash',
        'VA CLD' => 'Volcanic Ash Cloud',
        'DS' => 'Duststorm',
        'SS' => 'Sandstorm',
        'LLWS' => 'Low Level Wind Shear',
        'GR' => 'Hail',
        'FC' => 'Funnel Cloud',
        'TC' => 'Tropical Cyclone',
        'TSGR' => 'Thunderstorm with Hail',
        'HEAVY RA' => 'Heavy Rain',
        'HEAVY SN' => 'Heavy Snow',
    ];

    /**
     * Parse a SIGMET message
     * @param string $report Raw SIGMET message
     * @return array|null Parsed SIGMET data or null if invalid
     */
    public static function parse(string $report): ?array {
        $report = strtoupper(trim($report));

        if (empty($report)) {
            return null;
        }

        // Initialize result
        $result = [
            'type' => SigmetType::SIGMET,
            'raw' => $report,
            'header' => null,
            'issue_time' => null,
            'valid_from' => null,
            'valid_until' => null,
            'station' => null,
            'fir' => null,
            'phenomenon' => null,
            'phenomenon_raw' => null,
            'severity' => SigmetSeverity::NOT_REPORTED,
            'altitude' => null,
            'location' => null,
            'movement' => null,
            'intensity' => null,
            'remarks' => null,
        ];

        // Parse WMO Header (e.g., WSUS31, WSAY31, WVUS01)
        if (preg_match('/^([A-Z]{4}\d{2})\s+(.+)$/', $report, $matches)) {
            $result['header'] = $matches[1];
            $report = $matches[2];

            // Determine SIGMET type from header
            $headerType = substr($result['header'], 0, 2);
            if ($headerType === 'WS') {
                $result['type'] = SigmetType::SIGMET;
            } elseif ($headerType === 'WA') {
                $result['type'] = SigmetType::AIRMET;
            } elseif ($headerType === 'WV') {
                $result['type'] = SigmetType::VOLCANIC_ASH;
            }
        }

        // Parse originating station (e.g., KKCI, EGRR)
        if (preg_match('/^([A-Z]{4})\s+/', $report, $matches)) {
            $result['station'] = $matches[1];
            $report = substr($report, strlen($matches[0]));
        }

        // Parse issue time (DDHHMM)
        if (preg_match('/^(\d{6})Z?\s+/', $report, $matches)) {
            $time = $matches[1];
            $result['issue_time'] = [
                'day' => (int)substr($time, 0, 2),
                'hour' => (int)substr($time, 2, 2),
                'minute' => (int)substr($time, 4, 2),
            ];
            $report = substr($report, strlen($matches[0]));
        }

        // Parse SIGMET identifier (e.g., SIGMET PAPA 1, SIGMET 3)
        if (preg_match('/^(SIGMET|AIRMET)\s+([A-Z]{4})\s*(\d+)?/', $report, $matches)) {
            $result['sigmet_id'] = $matches[2] . ($matches[3] ?? '');
            $report = substr($report, strlen($matches[0]));
        }

        // Parse validity period (VALID DDHHMM/DDHHMM)
        if (preg_match('/VALID\s+(\d{6})\/(\d{6})/', $report, $matches)) {
            $validFrom = $matches[1];
            $validUntil = $matches[2];

            $result['valid_from'] = [
                'day' => (int)substr($validFrom, 0, 2),
                'hour' => (int)substr($validFrom, 2, 2),
                'minute' => (int)substr($validFrom, 4, 2),
            ];
            $result['valid_until'] = [
                'day' => (int)substr($validUntil, 0, 2),
                'hour' => (int)substr($validUntil, 2, 2),
                'minute' => (int)substr($validUntil, 4, 2),
            ];
            $report = preg_replace('/VALID\s+\d{6}\/\d{6}\s*/', '', $report);
        }

        // Parse FIR/UZ name (e.g., KANSAS CITY FIR, LFFF FIR)
        if (preg_match('/^([A-Z]+\s*FIR|UZ)\s*/', $report, $matches)) {
            $result['fir'] = trim($matches[1]);
            $report = substr($report, strlen($matches[0]));
        }

        // Parse phenomenon
        $phenomenonData = self::parsePhenomenon($report);
        if ($phenomenonData) {
            $result['phenomenon'] = $phenomenonData['phenomenon'];
            $result['phenomenon_raw'] = $phenomenonData['raw'];
            $result['severity'] = $phenomenonData['severity'];
            $report = $phenomenonData['remaining'];
        }

        // Parse altitude (FL### or ###/###)
        if (preg_match('/(FL\d+|\d+\/\d+)\s*/', $report, $matches)) {
            $result['altitude'] = $matches[1];
            $report = str_replace($matches[0], '', $report);
        }

        // Parse location description (FROM/N OF/E OF etc.)
        if (preg_match('/(FROM|N OF|E OF|W OF|S OF|NE OF|NW OF|SE OF|SW OF)\s*([A-Z0-9\-\s]+?)(?=\s+(MOV|NC|INTSF|WKN|WILL|SITU|\z))/i', $report, $matches)) {
            $result['location'] = trim($matches[0]);
            $report = str_replace($matches[0], '', $report);
        }

        // Parse movement (MOV E 20KT, MOV NW 15KT)
        if (preg_match('/MOV\s+([NSEW]+)\s*(\d+)?\s*KT?/', $report, $matches)) {
            $result['movement'] = [
                'direction' => $matches[1],
                'speed' => isset($matches[2]) ? (int)$matches[2] : null,
            ];
            $report = preg_replace('/MOV\s+[NSEW]+\s*\d*\s*KT?\s*/', '', $report);
        }

        // Parse intensity (NC = No Change, INTSF = Intensifying, WKN = Weakening)
        if (preg_match('/\b(NC|INTSF|WKN|WILL|SITU)\b/', $report, $matches)) {
            $intensityMap = [
                'NC' => 'No Change',
                'INTSF' => 'Intensifying',
                'WKN' => 'Weakening',
                'WILL' => 'Will Weaken',
                'SITU' => 'Situation',
            ];
            $result['intensity'] = $intensityMap[$matches[1]] ?? $matches[1];
        }

        // Remaining text as remarks
        $report = trim($report);
        if (!empty($report)) {
            $result['remarks'] = $report;
        }

        return $result;
    }

    /**
     * Parse SIGMET phenomenon from message
     */
    private static function parsePhenomenon(string $report): ?array {
        $phenomena = [
            'SEV TURB' => SigmetPhenomenon::TURBULENCE,
            'MOD TURB' => SigmetPhenomenon::TURBULENCE,
            'SEV ICE' => SigmetPhenomenon::ICING,
            'MOD ICE' => SigmetPhenomenon::ICING,
            'SEV MTW' => SigmetPhenomenon::MOUNTAIN_WAVE,
            'VA CLD' => SigmetPhenomenon::VOLCANIC_ASH,
            'VA' => SigmetPhenomenon::VOLCANIC_ASH,
            'DS' => SigmetPhenomenon::DUSTSTORM,
            'SS' => SigmetPhenomenon::SANDSTORM,
            'LLWS' => SigmetPhenomenon::LOW_LEVEL_WIND_SHEAR,
            'TS' => SigmetPhenomenon::THUNDERSTORM,
            'TSGR' => SigmetPhenomenon::HAIL,
            'GR' => SigmetPhenomenon::HAIL,
            'FC' => SigmetPhenomenon::FUNNEL_CLOUD,
            'TC' => SigmetPhenomenon::TROPICAL_CYCLONE,
            'HEAVY RA' => SigmetPhenomenon::HEAVY_RAIN,
            'HEAVY SN' => SigmetPhenomenon::HEAVY_SNOW,
        ];

        // Check for severity first
        $severity = SigmetSeverity::NOT_REPORTED;
        if (strpos($report, 'SEV') !== false) {
            $severity = SigmetSeverity::SEVERE;
        } elseif (strpos($report, 'MOD') !== false) {
            $severity = SigmetSeverity::MODERATE;
        } elseif (strpos($report, 'LIGHT') !== false) {
            $severity = SigmetSeverity::LIGHT;
        }

        // Match phenomenon
        foreach ($phenomena as $pattern => $phenom) {
            if (preg_match('/' . $pattern . '/i', $report)) {
                return [
                    'phenomenon' => $phenom,
                    'raw' => $pattern,
                    'severity' => $severity,
                    'remaining' => preg_replace('/' . $pattern . '/i', '', $report, 1),
                ];
            }
        }

        return null;
    }

    /**
     * Get human-readable name for SIGMET phenomenon
     */
    public static function getPhenomenonName(string $phenomenon): string {
        // Reverse lookup
        foreach (self::$phenomenonNames as $code => $name) {
            if (strtolower($code) === strtolower($phenomenon)) {
                return $name;
            }
        }
        return $phenomenon;
    }

    /**
     * Format SIGMET data as human-readable string
     */
    public static function format(array $sigmet): string {
        $output = [];

        $output[] = "Type: " . strtoupper($sigmet['type'] ?? 'Unknown');

        if (!empty($sigmet['station'])) {
            $output[] = "Station: " . $sigmet['station'];
        }

        if (!empty($sigmet['issue_time'])) {
            $t = $sigmet['issue_time'];
            $output[] = "Issued: Day {$t['day']} {$t['hour']}:{$t['minute']}Z";
        }

        if (!empty($sigmet['valid_from']) && !empty($sigmet['valid_until'])) {
            $from = $sigmet['valid_from'];
            $until = $sigmet['valid_until'];
            $output[] = "Valid: Day {$from['day']} {$from['hour']}:{$from['minute']}Z - Day {$until['day']} {$until['hour']}:{$until['minute']}Z";
        }

        if (!empty($sigmet['fir'])) {
            $output[] = "FIR: " . $sigmet['fir'];
        }

        if (!empty($sigmet['phenomenon'])) {
            $severity = !empty($sigmet['severity']) ? strtoupper($sigmet['severity']) . ' ' : '';
            $output[] = "Phenomenon: " . $severity . self::getPhenomenonName($sigmet['phenomenon']);
        }

        if (!empty($sigmet['altitude'])) {
            $output[] = "Altitude: " . $sigmet['altitude'];
        }

        if (!empty($sigmet['location'])) {
            $output[] = "Location: " . $sigmet['location'];
        }

        if (!empty($sigmet['movement'])) {
            $m = $sigmet['movement'];
            $output[] = "Movement: " . $m['direction'] . ($m['speed'] ? ' ' . $m['speed'] . 'kt' : '');
        }

        if (!empty($sigmet['intensity'])) {
            $output[] = "Intensity: " . $sigmet['intensity'];
        }

        if (!empty($sigmet['remarks'])) {
            $output[] = "Remarks: " . $sigmet['remarks'];
        }

        return implode("\n", $output);
    }
}

} // namespace Metaf
