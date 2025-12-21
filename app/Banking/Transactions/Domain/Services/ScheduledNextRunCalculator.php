<?php

namespace App\Banking\Transactions\Domain\Services;

use Carbon\CarbonImmutable;

final class ScheduledNextRunCalculator
{
    /**
     * base: الوقت الذي نبني عليه الدورة (عادة next_run_at الحالية)
     * نرجّع next_run_at القادمة > base
     */
    public function next(
        CarbonImmutable $base,
        string $frequency,
        int $interval,
        ?int $dayOfWeek,
        ?int $dayOfMonth,
        string $runTime // HH:MM:SS
    ): CarbonImmutable {
        [$h, $m, $s] = array_map('intval', explode(':', $runTime));

        if ($frequency === 'daily') {
            return $base->addDays($interval)->setTime($h, $m, $s);
        }

        if ($frequency === 'weekly') {
            // لو محدد يوم أسبوع، نثبت عليه
            $candidate = $base->addWeeks($interval)->setTime($h, $m, $s);

            if ($dayOfWeek !== null) {
                // Carbon: 0 Sunday .. 6 Saturday
                return $candidate->setDayOfWeek($dayOfWeek);
            }

            return $candidate;
        }

        // monthly
        $candidate = $base->addMonths($interval)->setTime($h, $m, $s);

        if ($dayOfMonth !== null) {
            $dom = max(1, min(28, $dayOfMonth));
            return $candidate->setDay($dom);
        }

        // fallback: keep same day
        return $candidate->setDay(min(28, (int) $base->day));
    }

    /**
     * initial next_run_at عند الإنشاء: بناءً على startAt أو الآن
     */
    public function initial(
        CarbonImmutable $now,
        ?CarbonImmutable $startAt,
        string $frequency,
        int $interval,
        ?int $dayOfWeek,
        ?int $dayOfMonth,
        string $runTime
    ): CarbonImmutable {
        $base = $startAt ?? $now;

        [$h, $m, $s] = array_map('intval', explode(':', $runTime));
        $base = $base->setTime($h, $m, $s);

        // لو base في الماضي، نلف لحد ما نوصل للـfuture
        $next = $base;
        while ($next->lessThanOrEqualTo($now)) {
            $next = $this->next($next, $frequency, $interval, $dayOfWeek, $dayOfMonth, $runTime);
        }

        return $next;
    }
}
