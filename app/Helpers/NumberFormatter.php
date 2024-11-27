<?php

if (!function_exists('indian_format')) {
    /**
     * Format a number into Indian numbering format with two decimal places.
     *
     * @param float|int $num
     * @return string
     */
    function indian_format($num) {
        // Check if the number is negative
        $isNegative = $num < 0;

        // Convert the number to absolute and format it
        $num = abs($num);
        
        // Round the number to 2 decimal places before processing
        $num = number_format($num, 2, '.', '');

        // Split the number into integer and decimal parts
        list($integer, $decimal) = explode('.', $num);

        // Apply Indian formatting to the integer part
        $lastThree = substr($integer, -3);
        $remaining = substr($integer, 0, -3);

        if ($remaining != '') {
            $remaining = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $remaining);
            $formatted = $remaining . ',' . $lastThree;
        } else {
            $formatted = $lastThree;
        }

        // Add the negative sign back if necessary
        if ($isNegative) {
            $formatted = '-' . $formatted;
        }

        // Combine the integer part with the rounded decimal part
        return $formatted . '.' . $decimal;
    }
}


