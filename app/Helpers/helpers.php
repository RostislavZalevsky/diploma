<?php

use Carbon\Carbon;

if (!function_exists('convertFormatDate')) {
    function convertFormatDate($date, $strtotime = true) {
        $timestamp = ($strtotime ? strtotime($date) : $date);

        return date(config('app.date_format'), $timestamp);
    }
}

if (!function_exists('convertFormatDateTime')) {
    function convertFormatDateTime($date, $strtotime = true) {
        $timestamp = ($strtotime ? strtotime($date) : $date);

        return date(config('app.datetime_format'), $timestamp);
    }
}

if (!function_exists('formatPaymentStatus')) {
    function formatPaymentStatus($payment_status) {
        return ucwords(strtolower(str_replace('_', ' ', $payment_status)));
    }
}

if (!function_exists('htmlTrimTags')) {
    function htmlTrimTags($string) {
        return htmlspecialchars(trim(strip_tags($string)));
    }
}
