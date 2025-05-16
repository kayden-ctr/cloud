<?php
// Set base URL
$base_url = '/awsomecloudtrack';

// Define url() safely
if (!function_exists('url')) {
    function url($path = '') {
        global $base_url;
        return $base_url . '/' . ltrim($path, '/');
    }
}
