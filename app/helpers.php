<?php

if (! function_exists('currency_symbol')) {
    function currency_symbol(): string {
        return config('app.currency_symbol', '₹');
    }
}

if (! function_exists('currency_code')) {
    function currency_code(): string {
        return config('app.currency', 'INR');
    }
}

if (! function_exists('format_currency')) {
    function format_currency($amount, int $decimals = 2): string {
        return currency_symbol() . number_format((float) ($amount ?? 0), $decimals);
    }
}
