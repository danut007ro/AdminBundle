<?php

declare(strict_types=1);

namespace DG\AdminBundle\Form\DataTransformer;

use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\ChoiceList\ChoiceListInterface;

final class TagToValueTransformer extends AbstractChoiceToValueTransformer
{
    protected function buildChoiceList(): ChoiceListInterface
    {
        return new ArrayChoiceList(array_values($this->choices), fn ($tag) => $tag);
    }
}
