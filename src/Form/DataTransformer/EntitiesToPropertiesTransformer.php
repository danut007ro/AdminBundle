<?php

declare(strict_types=1);

namespace DG\AdminBundle\Form\DataTransformer;

use DG\AdminBundle\Column\ValueExtractor\ValueExtractorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class EntitiesToPropertiesTransformer implements DataTransformerInterface
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
     * @param object[] $value
     *
     * @return array<string, mixed>
     */
    public function transform($value): array
    {
        if (!is_countable($value) || 0 === \count($value)) {
            return [];
        }

        $data = [];
        foreach ($value as $entity) {
            $data[(string) $this->propertyAccessor->getValue($entity, $this->idProperty)] = null === $this->textValue
                ? (string) $entity
                : $this->textValue->extractValue($entity)
            ;
        }

        return $data;
    }

    /**
     * @param array<string> $value
     *
     * @return array<object>
     */
    public function reverseTransform($value): array
    {
        if (!\is_array($value) || 0 === \count($value)) {
            return [];
        }

        $qb = $this->em->createQueryBuilder()
            ->select('entity')
            ->from($this->class, 'entity')
            ->where("entity.{$this->idProperty} IN (:ids)")
            ->setParameter('ids', $value)
        ;

        if (\is_callable($this->queryBuilderCallback)) {
            ($this->queryBuilderCallback)($qb);
        }

        $query = $qb->getQuery();
        if (\is_callable($this->queryCallback)) {
            ($this->queryCallback)($qb);
        }

        $entities = $query->getResult();

        // This will happen if the form submits invalid data.
        $valid = \count($entities) === \count($value);
        if ($valid && \is_callable($this->entityValidator)) {
            // Check if all entities are valid.
            foreach ($entities as $entity) {
                if (!(bool) ($this->entityValidator)($entity)) {
                    $valid = false;

                    break;
                }
            }
        }

        if (!$valid) {
            throw new TransformationFailedException('One or more id values are invalid.');
        }

        return $entities;
    }
}
