<?php

declare(strict_types=1);

namespace DG\AdminBundle;

use DG\AdminBundle\DependencyInjection\AdminExtension;
use DG\AdminBundle\DependencyInjection\Compiler\LocatorRegistrationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class AdminBundle extends Bundle
{
    /**
     * @var string
     */
    protected $name = 'DGAdminBundle';

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new LocatorRegistrationPass());
    }

    public function getContainerExtension(): ExtensionInterface
    {
        return new AdminExtension();
    }
}
