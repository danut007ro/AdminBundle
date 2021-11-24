<?php

declare(strict_types=1);

namespace DG\AdminBundle\Adapter;

use DG\AdminBundle\AbstractConfigurableClass;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractAdapter extends AbstractConfigurableClass implements AdapterInterface
{
    public function configureOptions(OptionsResolver $resolver): void
    {
    }
}
