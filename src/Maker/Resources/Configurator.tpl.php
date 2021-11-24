<?= "<?php\n" ?>

declare(strict_types=1);

namespace <?= $namespace; ?>;

<?= $uses_sort(array_merge(
    [
        'DG\AdminBundle\Column\TextColumn',
        'DG\AdminBundle\Formatter\FormatterInterface',
        'DG\AdminBundle\Table\AbstractConfigurator',
        'DG\AdminBundle\Table\Table',
        'DG\AdminBundle\Table\TableRequest',
    ],
    $is_crud ? [
        $entity_full_class_name,
        'DG\AdminBundle\Column\ActionColumn',
        'DG\AdminBundle\UIAction\AjaxDialogUIAction',
        'Symfony\Component\Routing\RouterInterface',
    ] : [],
)); ?>

class Configurator extends AbstractConfigurator
{
    public function __construct(private Adapter $adapter<?php if ($is_crud) { ?>, private RouterInterface $router<?php } ?>)
    {
    }

    public function configureTable(Table $table, TableRequest $request, array $options, FormatterInterface $formatter): void
    {
        $table
            // Columns to show in table.
            // ->addColumn('name', TextColumn::class)
<?php if ($is_crud) { ?>
            ->addColumn('actions', ActionColumn::class, [
                'actions' => fn (<?= $entity_class_name; ?> $entity): array => [
                    [
                        'url' => $this->router->generate('<?= $update_route; ?>', ['id' => $entity->getId()]),
                        'icon' => 'fas fa-edit',
                        'attr' => ['class' => 'btn btn-sm btn-outline-primary', 'title' => 'edit'],
                        'ui_action' => new AjaxDialogUIAction(['restore_url' => '']),
                    ],
                    [
                        'url' => $this->router->generate('<?= $delete_route; ?>', ['id' => $entity->getId()]),
                        'icon' => 'fas fa-trash',
                        'attr' => ['class' => 'btn btn-sm btn-outline-danger', 'title' => 'delete'],
                        'ui_action' => new AjaxDialogUIAction(),
                    ],
                ],
            ])
<?php } if ($has_filter) { ?>
            ->setFilter(Filter::class)
<?php } ?>
            ->setAdapter($this->adapter)
        ;
    }
}
