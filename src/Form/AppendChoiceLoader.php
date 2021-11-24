<?php

declare(strict_types=1);

namespace DG\AdminBundle\Form;

use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\ChoiceList\ChoiceListInterface;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;

class AppendChoiceLoader implements ChoiceLoaderInterface
{
    public function loadChoiceList(callable $value = null): ChoiceListInterface
    {
        return new ArrayChoiceList([], $value);
    }

    /**
     * @return string[]
     */
    public function loadChoicesForValues(array $values, callable $value = null): array
    {
        return $values;
    }

    /**
     * @return string[]
     */
    public function loadValuesForChoices(array $choices, callable $value = null): array
    {
        $values = [];
        foreach ($choices as $key => $choice) {
            $values[$key] = (string) $choice;
        }

        return $values;
    }
}
