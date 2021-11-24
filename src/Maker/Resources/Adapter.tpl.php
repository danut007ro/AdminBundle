<?= "<?php\n" ?>

declare(strict_types=1);

namespace <?= $namespace; ?>;

<?= $uses_sort([
    $entity_full_class_name,
    'DG\AdminBundle\Adapter\AbstractDoctrineORMCRUDAdapter',
    'DG\AdminBundle\Adapter\ORMAdapter',
    'DG\AdminBundle\DependencyInjection\Instantiator',
    'Doctrine\Persistence\ManagerRegistry',
]); ?>

/**
 * @template-extends AbstractDoctrineORMCRUDAdapter<<?= $entity_class_name; ?>>
 */
class Adapter extends AbstractDoctrineORMCRUDAdapter
{
    public function __construct(ManagerRegistry $managerRegistry, Instantiator $instantiator)
    {
        parent::__construct(
            <?= $entity_class_name; ?>::class,
            $managerRegistry,
            $instantiator->getAdapter(ORMAdapter::class, [
                'entity' => <?= $entity_class_name; ?>::class,
                'order_by' => [
                    'id' => 'entity.id',
                ],
            ]),
        );
    }
}
