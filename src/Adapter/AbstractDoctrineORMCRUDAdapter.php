<?php

declare(strict_types=1);

namespace DG\AdminBundle\Adapter;

use DG\AdminBundle\Exception\RuntimeException;
use DG\AdminBundle\Exception\UnexpectedTypeException;
use DG\AdminBundle\Result\Data\DataResultInterface;
use DG\AdminBundle\Table\TableRequest;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\FormInterface;

/**
 * @template T of object
 * @implements CRUDAdapterInterface<T>
 */
abstract class AbstractDoctrineORMCRUDAdapter implements CRUDAdapterInterface
{
    /**
     * @param class-string<T> $entity
     */
    public function __construct(
        protected string $entity,
        protected ManagerRegistry $managerRegistry,
        protected AdapterInterface $adapter,
    ) {
    }

    public function list(TableRequest $request, ?FormInterface $filter = null): DataResultInterface
    {
        return $this->adapter->list($request, $filter);
    }

    public function create($data): void
    {
        $this->validateEntity($data);

        $manager = $this->getManager();
        $manager->persist($data);
        $manager->flush();
    }

    public function read(mixed $id): mixed
    {
        return $this->managerRegistry->getRepository($this->entity)->find($id);
    }

    public function update(mixed $data): void
    {
        $this->validateEntity($data);

        $this->getManager()->flush();
    }

    public function delete(mixed $data): void
    {
        $this->validateEntity($data);

        $manager = $this->getManager();
        $manager->remove($data);
        $manager->flush();
    }

    /**
     * @param T $data
     */
    protected function validateEntity(mixed $data): void
    {
        if (!is_a($data, $this->entity, true)) {
            throw new UnexpectedTypeException($data, $this->entity);
        }
    }

    protected function getManager(): EntityManagerInterface
    {
        $manager = $this->managerRegistry->getManagerForClass($this->entity);
        if (!$manager instanceof EntityManagerInterface) {
            throw new RuntimeException(sprintf('Cannot get manager for entity "%s".', $this->entity));
        }

        return $manager;
    }
}
