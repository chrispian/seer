<?php

namespace App\Support;

class MarkdownTable
{
    public static function create(array $data): string
    {
        if (empty($data)) {
            return '';
        }

        $headers = array_keys($data[0]);
        
        // Calculate column widths
        $widths = [];
        foreach ($headers as $header) {
            $widths[$header] = strlen($header);
        }
        
        foreach ($data as $row) {
            foreach ($row as $key => $value) {
                $widths[$key] = max($widths[$key], strlen((string)$value));
            }
        }

        // Build table
        $table = '';
        
        // Header row
        $headerRow = '|';
        foreach ($headers as $header) {
            $headerRow .= ' ' . str_pad($header, $widths[$header]) . ' |';
        }
        $table .= $headerRow . "\n";
        
        // Separator row
        $separatorRow = '|';
        foreach ($headers as $header) {
            $separatorRow .= str_repeat('-', $widths[$header] + 2) . '|';
        }
        $table .= $separatorRow . "\n";
        
        // Data rows
        foreach ($data as $row) {
            $dataRow = '|';
            foreach ($headers as $header) {
                $value = isset($row[$header]) ? (string)$row[$header] : '';
                $dataRow .= ' ' . str_pad($value, $widths[$header]) . ' |';
            }
            $table .= $dataRow . "\n";
        }
        
        return $table;
    }
}