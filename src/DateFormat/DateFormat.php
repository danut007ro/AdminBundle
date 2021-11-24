<?php

declare(strict_types=1);

namespace DG\AdminBundle\DateFormat;

class DateFormat
{
    public function __construct(
        private string $format,
        private string $formatMoment,
        private bool $hasTime,
        private bool $useTime24h,
    ) {
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function getFormatMoment(): string
    {
        return $this->formatMoment;
    }

    public function hasTime(): bool
    {
        return $this->hasTime;
    }

    public function useTime24h(): bool
    {
        return $this->useTime24h;
    }
}
