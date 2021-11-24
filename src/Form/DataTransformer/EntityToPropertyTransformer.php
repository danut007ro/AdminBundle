<?php

declare(strict_types=1);

namespace DG\AdminBundle\Form\DataTransformer;

use DG\AdminBundle\Column\ValueExtractor\ValueExtractorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnexpectedResultException;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class EntityToPropertyTransformer implements DataTransformerInterface
{
    protected PropertyAccessorInterface $propertyAccessor;

    /**
     * @param ?callable $entityValidator
     * @param ?callable $queryBuilderCallback
     * @param ?callable $queryCallback
     */
    public function __construct(
        protected EntityManagerInterface $em,
        protected string $class,
        protected string $idProperty,
        protected ?ValueExtractorInterface $textValue = null,
        protected mixed $entityValidator = null,
        protected mixed $queryBuilderCallback = null,
        protected mixed $queryCallback = null,
    ) {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * @param ?object $value
     *
     * @return array<string, mixed>
     */
    public function transform($value): array
    {
        if (null === $value) {
            return [];
        }

        $data[(string) $this->propertyAccessor->getValue($value, $this->idProperty)] = null === $this->textValue
            ? (string) $value
            : $this->textValue->extractValue($value)
        ;

        return $data;
    }

    /**
     * @param string $value
     */
    public function reverseTransform($value): ?object
    {
        if (!\is_string($value) || '' === $value) {
            return null;
        }

        $qb = $this->em->createQueryBuilder()
            ->select('entity')
            ->from($this->class, 'entity')
            ->where("entity.{$this->idProperty}=:id")
            ->setParameter('id', $value)
        ;

        if (\is_callable($this->queryBuilderCallback)) {
            ($this->queryBuilderCallback)($qb);
        }

        $query = $qb->getQuery();
        if (\is_callable($this->queryCallback)) {
            ($this->queryCallback)($qb);
        }

        $valid = true;

        try {
            $entity = $query->getSingleResult();
            if (\is_callable($this->entityValidator)) {
                $valid = (bool) ($this->entityValidator)($entity);
            }
        } catch (UnexpectedResultException) {
            $entity = null;
            $valid = false;
        }

        if (!$valid) {
            throw new TransformationFailedException(sprintf('The choice "%s" does not exist or is not unique.', $value));
        }

        return $entity;
    }
}
