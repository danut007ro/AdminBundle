<?php

declare(strict_types=1);

namespace DG\AdminBundle\Column;

use Symfony\Component\Translation\TranslatableMessage;

interface ColumnInterface
{
    /**
     * Get label for column.
     */
    public function getLabel(): string|TranslatableMessage;

    /**
     * Get value to be used for rendering.
     */
    public function getValue(mixed $row): mixed;

    /**
     * Render value.
     */
    public function render(mixed $value, mixed $row, mixed $originalRow): mixed;

    /**
     * Get priority for column.
     */
    public function getPriority(): int;

    public function isSortable(): bool;

    public function isVisible(): bool;
}
