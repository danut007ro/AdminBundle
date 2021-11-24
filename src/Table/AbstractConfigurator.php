<?php

declare(strict_types=1);

namespace DG\AdminBundle\Table;

use DG\AdminBundle\AbstractConfigurableClass;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractConfigurator extends AbstractConfigurableClass implements ConfiguratorInterface
{
    public function configureOptions(OptionsResolver $resolver): void
    {
    }
}
