<?php

declare(strict_types=1);

namespace DG\AdminBundle\DateFormat;

use Symfony\Component\Translation\Extractor\ExtractorInterface;
use Symfony\Component\Translation\MessageCatalogue;

final class TranslationExtractor implements ExtractorInterface
{
    private string $prefix = '';

    public function __construct(private DateFormats $dateFormats)
    {
    }

    /**
     * @param string|string[] $resource
     */
    public function extract($resource, MessageCatalogue $catalogue): void
    {
        foreach (array_keys($this->dateFormats->getDateRanges()) as $range) {
            $catalogue->set("date_range.{$range}", $this->prefix.$range, 'dg_admin');
        }

        foreach ($this->dateFormats->getDateFormatTranslations() as $format) {
            $catalogue->set($format, $this->prefix.$format, 'dg_admin');
        }
    }

    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }
}
