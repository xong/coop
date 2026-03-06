<?php

namespace App\Service;

class CalendarService
{
    public function buildMonthGrid(int $year, int $month): array
    {
        $firstDay = new \DateTimeImmutable("{$year}-{$month}-01");
        $daysInMonth = (int) $firstDay->format('t');
        $startWeekday = (int) $firstDay->format('N'); // 1=Mon, 7=Sun

        $grid = [];
        // Leading empty days
        for ($i = 1; $i < $startWeekday; $i++) {
            $grid[] = null;
        }
        // Days of month
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $grid[] = new \DateTimeImmutable("{$year}-{$month}-{$d}");
        }
        // Trailing empty days to complete last week
        while (count($grid) % 7 !== 0) {
            $grid[] = null;
        }

        return $grid;
    }

    public function groupByDate(array $items, callable $getDate): array
    {
        $grouped = [];
        foreach ($items as $item) {
            $date = $getDate($item);
            if ($date) {
                $key = $date->format('Y-m-d');
                $grouped[$key][] = $item;
            }
        }
        return $grouped;
    }
}
