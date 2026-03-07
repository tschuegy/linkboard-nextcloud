<?php

declare(strict_types=1);

namespace OCA\LinkBoard\Service;

class ResourceService {

    public function getResources(array $config): array {
        $result = [];

        if (!empty($config['showCpu'])) {
            $result['cpu'] = $this->getCpuUsage();
        }
        if (!empty($config['showMemory'])) {
            $result['memory'] = $this->getMemoryUsage();
        }
        if (!empty($config['showUptime'])) {
            $result['uptime'] = $this->getUptime();
        }
        if (!empty($config['showCpuTemp'])) {
            $unit = $config['tempUnit'] ?? 'C';
            $result['cpuTemp'] = $this->getCpuTemperature($unit);
        }

        $diskPaths = $config['diskPaths'] ?? ['/'];
        if (!empty($diskPaths)) {
            $result['disks'] = $this->getDiskUsage($diskPaths);
        }

        return $result;
    }

    public function getCpuUsage(): ?array {
        $stat1 = $this->readProcStat();
        if ($stat1 === null) {
            return null;
        }

        usleep(100000); // 100ms

        $stat2 = $this->readProcStat();
        if ($stat2 === null) {
            return null;
        }

        $idle1 = $stat1['idle'];
        $idle2 = $stat2['idle'];
        $total1 = $stat1['total'];
        $total2 = $stat2['total'];

        $totalDiff = $total2 - $total1;
        if ($totalDiff === 0) {
            return ['percent' => 0.0];
        }

        $idleDiff = $idle2 - $idle1;
        $percent = round((1 - $idleDiff / $totalDiff) * 100, 1);

        return ['percent' => $percent];
    }

    public function getMemoryUsage(): ?array {
        if (!is_readable('/proc/meminfo')) {
            return null;
        }

        $content = @file_get_contents('/proc/meminfo');
        if ($content === false) {
            return null;
        }

        $total = 0;
        $available = 0;

        foreach (explode("\n", $content) as $line) {
            if (preg_match('/^MemTotal:\s+(\d+)\s+kB/', $line, $m)) {
                $total = (int)$m[1] * 1024;
            } elseif (preg_match('/^MemAvailable:\s+(\d+)\s+kB/', $line, $m)) {
                $available = (int)$m[1] * 1024;
            }
        }

        if ($total === 0) {
            return null;
        }

        $used = $total - $available;
        $percent = round($used / $total * 100, 1);

        return [
            'used' => $used,
            'total' => $total,
            'percent' => $percent,
        ];
    }

    /** @return array<array{path: string, used: int, total: int, percent: float}> */
    public function getDiskUsage(array $paths): array {
        $disks = [];
        foreach ($paths as $path) {
            $path = trim($path);
            if ($path === '') {
                continue;
            }
            $total = @disk_total_space($path);
            $free = @disk_free_space($path);
            if ($total === false || $free === false || $total === 0) {
                continue;
            }
            $used = $total - $free;
            $disks[] = [
                'path' => $path,
                'used' => (int)$used,
                'total' => (int)$total,
                'percent' => round($used / $total * 100, 1),
            ];
        }
        return $disks;
    }

    public function getUptime(): ?string {
        if (!is_readable('/proc/uptime')) {
            return null;
        }

        $content = @file_get_contents('/proc/uptime');
        if ($content === false) {
            return null;
        }

        $seconds = (int)floatval(trim(explode(' ', $content)[0]));
        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        $parts = [];
        if ($days > 0) {
            $parts[] = $days . 'd';
        }
        if ($hours > 0) {
            $parts[] = $hours . 'h';
        }
        $parts[] = $minutes . 'm';

        return implode(' ', $parts);
    }

    public function getCpuTemperature(string $unit = 'C'): ?array {
        $temp = null;

        // Try thermal zones
        $zones = @glob('/sys/class/thermal/thermal_zone*/temp');
        if ($zones) {
            foreach ($zones as $zone) {
                $val = @file_get_contents($zone);
                if ($val !== false) {
                    $millidegrees = (int)trim($val);
                    if ($millidegrees > 0) {
                        $temp = $millidegrees / 1000.0;
                        break;
                    }
                }
            }
        }

        if ($temp === null) {
            return null;
        }

        if (strtoupper($unit) === 'F') {
            $temp = round($temp * 9 / 5 + 32, 1);
        } else {
            $temp = round($temp, 1);
        }

        return [
            'value' => $temp,
            'unit' => strtoupper($unit) === 'F' ? 'F' : 'C',
        ];
    }

    private function readProcStat(): ?array {
        if (!is_readable('/proc/stat')) {
            return null;
        }

        $content = @file_get_contents('/proc/stat');
        if ($content === false) {
            return null;
        }

        $firstLine = explode("\n", $content)[0];
        // cpu  user nice system idle iowait irq softirq steal
        $parts = preg_split('/\s+/', $firstLine);
        if (count($parts) < 5) {
            return null;
        }

        $user = (int)$parts[1];
        $nice = (int)$parts[2];
        $system = (int)$parts[3];
        $idle = (int)$parts[4];
        $iowait = isset($parts[5]) ? (int)$parts[5] : 0;
        $irq = isset($parts[6]) ? (int)$parts[6] : 0;
        $softirq = isset($parts[7]) ? (int)$parts[7] : 0;
        $steal = isset($parts[8]) ? (int)$parts[8] : 0;

        $idleTotal = $idle + $iowait;
        $total = $user + $nice + $system + $idle + $iowait + $irq + $softirq + $steal;

        return ['idle' => $idleTotal, 'total' => $total];
    }
}
