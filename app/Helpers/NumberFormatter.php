<?php

if (!function_exists('indian_format')) {
    /**
     * Format a number into Indian numbering format.
     *
     * @param float|int $num
     * @return string
     */
    function indian_format($num) {
        // Convert the number to string
        $num = (string) $num;
        $decimals = '';

        // Split the number into integer and decimal parts
        if (strpos($num, '.') !== false) {
            list($num, $decimals) = explode('.', $num);
            $decimals = '.' . $decimals;  // Append the decimals back after formatting
        } else {
            $num = $num; // No decimals present
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

        // Format the decimal part
        if (empty($decimals)) {
            // If no decimals, add '.00'
            $decimals = '.00';
        } else {
            // Ensure the decimal part is always two digits
            $decimals = number_format((float)$decimals, 2, '.', '');
        }

        return $formatted . $decimals;
    }
}
