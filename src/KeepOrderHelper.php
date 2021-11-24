<?php

declare(strict_types=1);

namespace DG\AdminBundle;

use DG\AdminBundle\Exception\InvalidArgumentException;

class KeepOrderHelper
{
    /**
     * @var mixed[]
     */
    private array $results;

    /**
     * @var string[]
     */
    private array $index = [];

    /**
     * @var mixed[]
     */
    private array $rows = [];

    /**
     * @param array<array<int|string>|int|string> $ids
     * @param null|callable|string[]              $keys
     */
    public function __construct(
        private array $ids,
        private mixed $keys = null,
        mixed $default = null,
    ) {
        // Build indexed array to have faster access to combinations.
        foreach ($ids as $k => $id) {
            if (!\is_array($id)) {
                $key = $id;
            } elseif (null === ($key = $this->key($id))) {
                throw new InvalidArgumentException('Invalid keys given in array.');
            }

            $this->index[$key] = $k;
        }

        // Reset resulting array to default values.
        $this->results = (array) array_combine(array_keys($ids), array_fill(0, \count($ids), $default));
    }

    /**
     * @param array<array<int|string>|mixed>|callable $rows
     */
    public function setRows(callable|array $rows): self
    {
        $this->rows = \is_callable($rows) ? $rows($this->ids) : $rows;

        return $this;
    }

    /**
     * @param callable|mixed $processValue
     *
     * @return mixed[]
     */
    public function processRow(mixed $processValue): array
    {
        foreach ($this->rows as $row) {
            // Build key for row and check if it is indexed.
            if (null === ($key = $this->key($row)) || !\array_key_exists($key, $this->index)) {
                continue;
            }

            $this->results[$this->index[$key]] = \is_callable($processValue) ? $processValue($row) : $processValue;
        }

        return $this->results;
    }

    /**
     * @return mixed[]
     */
    public function process(): array
    {
        foreach ($this->rows as $row) {
            // Build key for row and check if it is indexed.
            if (null === ($key = $this->key($row)) || !\array_key_exists($key, $this->index)) {
                continue;
            }

            $this->results[$this->index[$key]] = $row;
        }

        return $this->results;
    }

    private function key(mixed $row): ?string
    {
        $key = [];
        if (\is_callable($this->keys)) {
            $key = ($this->keys)($row);

            if (null === $key) {
                return null;
            }

            if (!\is_array($key)) {
                // Fix key to array.
                $key = [$key];
            }
        } elseif (null !== $this->keys) {
            foreach ($this->keys as $k) {
                if (!\array_key_exists($k, $row)) {
                    // Key not found.
                    return null;
                }

                $key[] = $row[$k];
            }
        }

        return implode('-', $key);
    }
}
