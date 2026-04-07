<?php

if (!function_exists('bool_to_string')) {
    /**
     * Convert boolean-like values to 'true' or 'false' string
     */
    function bool_to_string($value): string
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
    }
}
