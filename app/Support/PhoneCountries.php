<?php

namespace App\Support;

use libphonenumber\PhoneNumberUtil;
use Locale;

class PhoneCountries
{
    /**
     * All calling countries as [['region' => 'GH', 'name' => 'Ghana', 'code' => '+233'], ...],
     * sorted by country name.
     */
    public static function all(): array
    {
        static $countries = null;

        if ($countries !== null) {
            return $countries;
        }

        $util = PhoneNumberUtil::getInstance();
        $countries = [];

        foreach ($util->getSupportedRegions() as $region) {
            $name = Locale::getDisplayRegion('-' . $region, 'en');

            if ($name === '' || $name === $region) {
                continue;
            }

            $countries[] = [
                'region' => $region,
                'name' => $name,
                'code' => '+' . $util->getCountryCodeForRegion($region),
            ];
        }

        usort($countries, fn ($a, $b) => strcmp($a['name'], $b['name']));

        return $countries;
    }

    /**
     * Unique dial codes (e.g. ['+1', '+233', ...]) for validation.
     */
    public static function dialCodes(): array
    {
        return array_values(array_unique(array_column(self::all(), 'code')));
    }
}
