<?php

namespace helper;

class Benchmark {
    private float $startTime;
    private float $startMemory;

    public function start() : void {
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage();
    }

    public function end() : array {
        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        $executionTime = $endTime - $this->startTime;
        $memoryUsage = $endMemory - $this->startMemory;

        return [
            'execution_time' => round($executionTime, 5) . ' seconds',
            'memory_usage' => $this->formatBytes($memoryUsage), 
            'memory peak' =>  $this->formatBytes(memory_get_peak_usage(true))
        ];
    }

    private function formatBytes(float $bytes, int $precision = 2) : string {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}