<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Option;

class CookieConsentController extends Controller
{
    public function check(Request $request)
    {
        $ip = $request->header('CF-Connecting-IP') ?? $request->ip();
        $countryCode = $this->getCountryCodeFromIp($ip);

        $allowedCountries = json_decode(Option::get('cookie_countries', '[]'), true);
        $showBanner = in_array($countryCode, $allowedCountries);

        return response()->json([
            'show_cookie_banner' => $showBanner,
            'country_code' => $countryCode,
        ]);
    }

    protected function getCountryCodeFromIp(string $ip): ?string
    {
        if (class_exists(\GeoIp2\Database\Reader::class)) {
            try {
                $reader = new \GeoIp2\Database\Reader(storage_path('app/geoip/GeoLite2-Country.mmdb'));
                $record = $reader->country($ip);
                return $record->country->isoCode;
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }
}
