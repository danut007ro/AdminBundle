<?php

declare(strict_types=1);

namespace DG\AdminBundle\Column\ValueExtractor;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

class PropertyPathValueExtractor implements ValueExtractorInterface
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

    public function extractValue(mixed $row): mixed
    {
        return $this->propertyAccessor->getValue($row, $this->propertyPath);
    }
}
