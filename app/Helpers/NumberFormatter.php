<?php

if (!function_exists('indian_format')) {
    /**
     * Format a number into Indian numbering format.
     *
     * @param float|int $num
     * @return string
     */
    function indian_format($num) {
    // Check if the number is negative
    $isNegative = $num < 0;
    
    // Convert the number to string and remove the negative sign for formatting
    $num = abs($num);
    $num = (string) $num;
    $decimals = '';

    // Split the number into integer and decimal parts
    if (strpos($num, '.') !== false) {
        list($num, $decimals) = explode('.', $num);
        $decimals = '.' . str_pad($decimals, 2, '0', STR_PAD_RIGHT);  // Ensure two decimal places
    } else {
        $decimals = '.00'; // No decimals present
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

    // Add the negative sign back if necessary
    if ($isNegative) {
        $formatted = '-' . $formatted;
    }

    return $formatted . $decimals;
}

}
