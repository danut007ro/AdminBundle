<?php

declare(strict_types=1);

namespace DG\AdminBundle\Column\ValueExtractor;

interface ValueExtractorInterface
{
    public function extractValue(mixed $row): mixed;
}
