<?php

declare(strict_types=1);

namespace DG\AdminBundle\Table\IdExtractor;

use DG\AdminBundle\Table\Table;
use DG\AdminBundle\Table\TableRequest;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

class PropertyPathIdExtractor implements IdExtractorInterface
{
    protected PropertyAccessorInterface $propertyAccessor;
    /** @var PropertyPath<mixed> */
    protected PropertyPath $propertyPath;

    /**
     * @param PropertyPath<mixed>|string $propertyPath
     */
    public function __construct(PropertyPath|string $propertyPath)
    {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->propertyPath = new PropertyPath($propertyPath);
    }

    public function extractId(mixed $row, Table $table, TableRequest $request): string
    {
        return (string) $this->propertyAccessor->getValue($row, $this->propertyPath);
    }
}
