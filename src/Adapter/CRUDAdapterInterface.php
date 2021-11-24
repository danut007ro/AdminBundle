<?php

declare(strict_types=1);

namespace DG\AdminBundle\Adapter;

/**
 * @template T
 */
interface CRUDAdapterInterface extends AdapterInterface
{
    /**
     * @param T $data
     */
    public function create(mixed $data): void;

    /**
     * @return ?T
     */
    public function read(mixed $id): mixed;

    /**
     * @param T $data
     */
    public function update(mixed $data): void;

    /**
     * @param T $data
     */
    public function delete(mixed $data): void;
}
