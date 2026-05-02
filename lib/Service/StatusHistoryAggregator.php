<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Service;

use DateTimeImmutable;
use DateTimeZone;
use OCA\LinkBoard\Db\StatusHistory;

class StatusHistoryAggregator {

    private const RANGE_SECONDS = [
        '1h'  => 3600,
        '3h'  => 10800,
        '24h' => 86400,
        '7d'  => 604800,
    ];

    /** Bucket count per period. null → pass-through, no bucketing. */
    private const BUCKETS = [
        '1h'  => null,
        '3h'  => null,
        '24h' => 288,
        '7d'  => 336,
    ];

    /**
     * @param StatusHistory[] $entries oldest→newest
     * @return array{history: array<int, array{status: string, responseMs: ?int, responseMsMax: ?int, checkedAt: string, failures: int, total: int}>, onlineCount: int, total: int}
     */
    public function aggregate(array $entries, string $period): array {
        $bucketCount = self::BUCKETS[$period] ?? null;
        $rangeSeconds = self::RANGE_SECONDS[$period] ?? self::RANGE_SECONDS['24h'];

        $onlineCount = 0;
        foreach ($entries as $entry) {
            if ($entry->getStatus() === 'online') {
                $onlineCount++;
            }
        }
        $total = count($entries);

        if ($bucketCount === null) {
            $history = [];
            foreach ($entries as $entry) {
                $history[] = [
                    'status' => $entry->getStatus(),
                    'responseMs' => $entry->getResponseMs(),
                    'responseMsMax' => $entry->getResponseMs(),
                    'checkedAt' => $entry->getCheckedAt(),
                    'failures' => $entry->getStatus() !== 'online' ? 1 : 0,
                    'total' => 1,
                ];
            }
            return ['history' => $history, 'onlineCount' => $onlineCount, 'total' => $total];
        }

        $bucketSecs = $rangeSeconds / $bucketCount;
        $nowTs = time();
        $windowStart = $nowTs - $rangeSeconds;
        $tz = new DateTimeZone('UTC');

        // buckets[i] = [sumMs, countMs, maxMs|null, total, failures, worstStatus]
        $buckets = [];
        for ($i = 0; $i < $bucketCount; $i++) {
            $buckets[$i] = [0, 0, null, 0, 0, null];
        }

        foreach ($entries as $entry) {
            $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $entry->getCheckedAt(), $tz);
            if ($dt === false) {
                continue;
            }
            $ts = $dt->getTimestamp();
            $idx = (int) floor(($ts - $windowStart) / $bucketSecs);
            if ($idx < 0 || $idx >= $bucketCount) {
                continue;
            }

            $ms = $entry->getResponseMs();
            $status = $entry->getStatus();

            if ($ms !== null) {
                $buckets[$idx][0] += $ms;
                $buckets[$idx][1]++;
                if ($buckets[$idx][2] === null || $ms > $buckets[$idx][2]) {
                    $buckets[$idx][2] = $ms;
                }
            }
            $buckets[$idx][3]++;
            if ($status !== 'online') {
                $buckets[$idx][4]++;
            }
            $buckets[$idx][5] = $this->worseStatus($buckets[$idx][5], $status);
        }

        $history = [];
        for ($i = 0; $i < $bucketCount; $i++) {
            [$sumMs, $countMs, $maxMs, $bucketTotal, $bucketFailures, $worstStatus] = $buckets[$i];
            $bucketCenter = $windowStart + (int) round(($i + 0.5) * $bucketSecs);
            $checkedAt = (new DateTimeImmutable('@' . $bucketCenter))->setTimezone($tz)->format('Y-m-d H:i:s');

            if ($bucketTotal === 0) {
                $history[] = [
                    'status' => 'unknown',
                    'responseMs' => null,
                    'responseMsMax' => null,
                    'checkedAt' => $checkedAt,
                    'failures' => 0,
                    'total' => 0,
                ];
                continue;
            }

            $avgMs = $countMs > 0 ? (int) round($sumMs / $countMs) : null;
            $history[] = [
                'status' => $worstStatus ?? 'unknown',
                'responseMs' => $avgMs,
                'responseMsMax' => $maxMs,
                'checkedAt' => $checkedAt,
                'failures' => $bucketFailures,
                'total' => $bucketTotal,
            ];
        }

        return ['history' => $history, 'onlineCount' => $onlineCount, 'total' => $total];
    }

    private function worseStatus(?string $current, string $candidate): string {
        $rank = ['online' => 0, 'unknown' => 1, 'offline' => 2];
        if ($current === null) {
            return $candidate;
        }
        $a = $rank[$current] ?? 1;
        $b = $rank[$candidate] ?? 1;
        return $b > $a ? $candidate : $current;
    }
}
