<?php

function shortenPropertyName($propertyName, $maxLength = 50, $suffix = '...')
{
    if (strlen($propertyName) <= $maxLength) {
        return $propertyName;
    }

    return substr($propertyName, 0, $maxLength - strlen($suffix)).$suffix;
}

if (! function_exists('maskEmail')) {
    function maskEmail($email)
    {
        $emailParts = explode('@', $email);
        $username = $emailParts[0];
        $domain = $emailParts[1];
        if (strlen($username) > 6) {
            $maskedUsername = substr($username, 0, 5).str_repeat('*', strlen($username) - 4).substr($username, -2);
        } else {
            $maskedUsername = $username;
        }
        if (strlen($maskedUsername) > 10) {
            $maskedUsername = substr($maskedUsername, 0, 8).'...';
        }
        $maskedDomain = strlen($domain) > 15 ? '...'.substr($domain, -13) : $domain;

        return $maskedUsername.'@'.$maskedDomain;
    }
}

if (! function_exists('maskPhone')) {
    function maskPhone($phone)
    {
        return $phone ? substr($phone, 0, -6).str_repeat('*', 6) : '';
    }
}

function formatCurrency($number, $decimals = 2, $decimal_separator = '.', $thousands_separator = ',', $forDb = false)
{
    $number = $number ?? 0;
    if ($number == 0) {
        return '0.00';
    }

    if ($forDb) {
        // Return raw numeric value for DB usage, ensuring it's still rounded consistently
        return number_format((float) $number, $decimals);
    }

    $locale = Config::get('general.default_locale_currency') ?? 'en-US';

    if (class_exists('NumberFormatter')) {
        $formatter = new NumberFormatter($locale, NumberFormatter::DECIMAL);
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $decimals);

        return $formatter->format($number);
    }

    return number_format($number, $decimals, $decimal_separator, $thousands_separator);
}
function formatCurrencyForDb($number, $forDb = false)
{
    return formatCurrency($number, 2, '.', ',', $forDb);
}

function installerExists()
{
    return file_exists(app_path('Http/Controllers/InstallerController.php'));
}
