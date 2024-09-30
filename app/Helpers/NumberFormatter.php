<?php

if (!function_exists('indian_format')) {
    /**
     * Format a number into Indian numbering format.
     *
     * @param float|int $num
     * @return string
     */
    function indian_format($num) {
        // Convert the number to string and remove any decimals
        $num = (string) $num;
        $decimals = '';

        // Split the number into integer and decimal parts
        if (strpos($num, '.') !== false) {
            list($num, $decimals) = explode('.', $num);
            $decimals = '.' . $decimals;  // Append the decimals back after formatting
        }

        // Apply formatting for the last three digits and then two digits for the rest
        $lastThree = substr($num, -3);
        $remaining = substr($num, 0, -3);
        if ($remaining != '') {
            $remaining = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $remaining);
            $formatted = $remaining . ',' . $lastThree;
        } else {
            $formatted = $lastThree;
        }

        return $formatted . $decimals;
    }
}
