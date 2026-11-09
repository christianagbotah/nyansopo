<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Finance Helper Functions
 * Advanced utility functions for finance module
 */

if (!function_exists('number_to_words')) {
    function number_to_words($number) {
        $hyphen = '-';
        $conjunction = ' and ';
        $separator = ', ';
        $negative = 'negative ';
        $decimal = ' point ';
        $dictionary = array(
            0 => 'zero', 1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four',
            5 => 'five', 6 => 'six', 7 => 'seven', 8 => 'eight', 9 => 'nine',
            10 => 'ten', 11 => 'eleven', 12 => 'twelve', 13 => 'thirteen',
            14 => 'fourteen', 15 => 'fifteen', 16 => 'sixteen', 17 => 'seventeen',
            18 => 'eighteen', 19 => 'nineteen', 20 => 'twenty', 30 => 'thirty',
            40 => 'forty', 50 => 'fifty', 60 => 'sixty', 70 => 'seventy',
            80 => 'eighty', 90 => 'ninety', 100 => 'hundred', 1000 => 'thousand',
            1000000 => 'million', 1000000000 => 'billion', 1000000000000 => 'trillion'
        );

        if (!is_numeric($number)) return false;
        if ($number < 0) return $negative . number_to_words(abs($number));

        $string = $fraction = null;

        if (strpos($number, '.') !== false) {
            list($number, $fraction) = explode('.', $number);
        }

        switch (true) {
            case $number < 21:
                $string = $dictionary[$number];
                break;
            case $number < 100:
                $tens = ((int) ($number / 10)) * 10;
                $units = $number % 10;
                $string = $dictionary[$tens];
                if ($units) $string .= $hyphen . $dictionary[$units];
                break;
            case $number < 1000:
                $hundreds = $number / 100;
                $remainder = $number % 100;
                $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
                if ($remainder) $string .= $conjunction . number_to_words($remainder);
                break;
            default:
                $baseUnit = pow(1000, floor(log($number, 1000)));
                $numBaseUnits = (int) ($number / $baseUnit);
                $remainder = $number % $baseUnit;
                $string = number_to_words($numBaseUnits) . ' ' . $dictionary[$baseUnit];
                if ($remainder) $string .= $remainder < 100 ? $conjunction : $separator;
                if ($remainder) $string .= number_to_words($remainder);
                break;
        }

        if (null !== $fraction && is_numeric($fraction)) {
            $string .= $decimal;
            $words = array();
            foreach (str_split((string) $fraction) as $digit) {
                $words[] = $dictionary[$digit];
            }
            $string .= implode(' ', $words);
        }

        return $string;
    }
}

if (!function_exists('format_currency')) {
    function format_currency($amount, $decimals = 2) {
        $currency = get_settings('currency');
        return $currency . ' ' . number_format($amount, $decimals);
    }
}

if (!function_exists('calculate_late_fee')) {
    function calculate_late_fee($amount, $days_overdue) {
        $CI =& get_instance();
        $settings = $CI->db->get('late_payment_settings')->row_array();
        
        if ($days_overdue <= $settings['grace_period_days']) {
            return 0;
        }
        
        if ($settings['late_fee_type'] == 'fixed') {
            return $settings['late_fee_value'];
        } else {
            return ($amount * $settings['late_fee_value']) / 100;
        }
    }
}

if (!function_exists('get_payment_status_badge')) {
    function get_payment_status_badge($status) {
        $badges = [
            'paid' => '<span class="badge badge-success">Paid</span>',
            'due' => '<span class="badge badge-warning">Due</span>',
            'overdue' => '<span class="badge badge-danger">Overdue</span>',
            'partial' => '<span class="badge badge-info">Partial</span>'
        ];
        return $badges[$status] ?? '<span class="badge badge-secondary">Unknown</span>';
    }
}

if (!function_exists('generate_invoice_code')) {
    function generate_invoice_code() {
        $CI =& get_instance();
        $year = get_settings('running_year');
        $term = get_settings('running_term');
        $count = $CI->db->where('year', $year)->where('term', $term)->count_all_results('invoice');
        return 'INV-' . str_replace('-', '', $year) . '-T' . $term . '-' . str_pad($count + 1, 5, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('check_payment_restrictions')) {
    function check_payment_restrictions($student_id) {
        $CI =& get_instance();
        $settings = $CI->db->get('late_payment_settings')->row_array();
        
        $outstanding = $CI->db->select_sum('due')
            ->where('student_id', $student_id)
            ->where('status', 'due')
            ->get('invoice')->row()->due;
        
        return [
            'has_outstanding' => $outstanding > 0,
            'restrict_exams' => $settings['restrict_exams'] == 1 && $outstanding > 0,
            'restrict_reports' => $settings['restrict_reports'] == 1 && $outstanding > 0,
            'outstanding_amount' => $outstanding
        ];
    }
}
