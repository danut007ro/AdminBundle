<?php

declare(strict_types=1);

namespace DG\AdminBundle\DateFormat;

use DG\AdminBundle\Exception\InvalidArgumentException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * http://userguide.icu-project.org/formatparse/datetime
 * https://momentjs.com/docs/#/displaying/
 * https://unicode-org.github.io/icu-docs/apidoc/released/icu4c/classSimpleDateFormat.html#details.
 */
class DateFormats
{
    /**
     * @param array<string, array{start: string, end: string}> $ranges
     * @param string[]                                         $formats
     */
    public function __construct(
        private array $ranges,
        private array $formats,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param mixed[] $parameters
     */
    public function getDateFormat(string $name, array $parameters = [], ?string $locale = null): DateFormat
    {
        if (!\in_array($name, $this->formats, true)) {
            throw new InvalidArgumentException(sprintf('Unknown date format "%s". It should be one of %s.', $name, implode(', ', $this->formats)));
        }

        $time = $this->translator->trans("date_format.{$name}.time", $parameters, 'dg_admin', $locale);

        return new DateFormat(
            $this->translator->trans("date_format.{$name}", $parameters, 'dg_admin', $locale),
            $this->translator->trans("date_format.{$name}.moment", $parameters, 'dg_admin', $locale),
            \in_array($time, ['12', '24'], true),
            '24' === $time,
        );
    }

    /**
     * @return array<string, array{start: string, end: string}>
     */
    public function getDateRanges(): array
    {
        return $this->ranges;
    }

    /**
     * @return string[]
     */
    public function getDateFormatTranslations(): array
    {
        $formats = [];
        foreach ($this->formats as $format) {
            $formats[] = "date_format.{$format}";
            $formats[] = "date_format.{$format}.time";
            $formats[] = "date_format.{$format}.moment";
        }

        return $formats;
    }
}
