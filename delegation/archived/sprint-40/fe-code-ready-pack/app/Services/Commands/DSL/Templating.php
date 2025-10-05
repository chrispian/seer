<?php

namespace App\Services\Commands\DSL;

class Templating
{
    public static function render(string $tpl, array $scope): string
    {
        // Minimal mustache-like interpolation: {{ path.to.value }}
        return preg_replace_callback('/\{\{\s*([^}]+)\s*\}\}/', function ($m) use ($scope) {
            $path = trim($m[1]);
            $val = data_get($scope, str_replace(':', '.', $path));
            if (is_array($val) || is_object($val)) {
                return json_encode($val);
            }

            return (string) ($val ?? '');
        }, $tpl);
    }
}
