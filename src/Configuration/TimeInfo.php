<?php

namespace Klaviyo\Integration\Configuration;

class TimeInfo
{
    private int $hour;
    private int $minute;

    public function __construct(int $hours, int $minutes)
    {
        $this->hour = $hours;
        $this->minute = $minutes;
    }

    public function getHour(): int
    {
        return $this->hour;
    }

    public function getMinute(): int
    {
        return $this->minute;
    }
}