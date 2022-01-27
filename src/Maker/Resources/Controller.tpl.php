<?= "<?php\n" ?>

declare(strict_types=1);

namespace <?= $namespace; ?>;

<?= $uses_sort(array_merge(
    [
        $configurator_full_class_name,
        'DG\AdminBundle\Table\TableRequest',
        'DG\AdminBundle\TableHelper',
        "Symfony\Bundle\FrameworkBundle\Controller\\$parent_class_name",
        'Symfony\Component\HttpFoundation\Request',
        'Symfony\Component\HttpFoundation\Response',
        'Symfony\Component\Routing\Annotation\Route',
    ],
    $is_crud ? [
        $adapter_full_class_name,
        $entity_full_class_name,
        $form_full_class_name,
        'DG\AdminBundle\ControllerHelper',
        'DG\AdminBundle\UIAction\AjaxDialogUIAction',
        'DG\AdminBundle\Response\ResponseUpdater',
        'DG\AdminBundle\Response\SwalNotificationResponse',
    ] : [],
)); ?>

<?php if ($use_attributes) { ?>
#[Route('<?= $route_path; ?>')]
<?php } else { ?>
/**
 * @Route("<?= $route_path; ?>")
 */
<?php } ?>
class <?= $class_name; ?> extends <?= $parent_class_name; ?><?= "\n"; ?>
{
    public function __construct(
        private TableHelper $tableHelper,
<?php if ($is_crud) { ?>
        private ControllerHelper $controllerHelper,
        private Adapter $adapter,
<?php } ?>
    ) {
    }

    /**
     * @return mixed[]|Response
     */
<?php if ($use_attributes) { ?>
    #[Route('/', name: '<?= $list_route; ?>', methods: ['GET', 'POST'])]
<?php } else { ?>
    /**
     * @Route("/", name="<?= $list_route; ?>", methods={"GET", "POST"})
     */
<?php } ?>
    public function list(Request $request): array|Response
    {
        $result = $this->tableHelper->handleRequest($request, [
            $this->tableHelper->createDefaultFormatter(
                $this->tableHelper->getTableFactory()->createTableConfigurator(Configurator::class),
                [
                    'url' => $this->generateUrl('<?= $list_route; ?>'),
                    'table_request' => (new TableRequest())->addOrderBy('id', TableRequest::ORDER_DESC),
                ],
            ),
        ]);

        if ($result->hasResponse()) {
            return $result->getResponse();
        }

        return $this->render('admin_table.html.twig');
    }
<?php if ($is_crud) { ?>

<?php if ($use_attributes) { ?>
    #[Route('/create', name: '<?= $create_route; ?>', methods: ['GET', 'POST'])]
<?php } else { ?>
    /**
     * @Route("/create", name="<?= $create_route; ?>", methods={"GET", "POST"})
     */
<?php } ?>
    public function create(Request $request): Response
    {
        return $this->controllerHelper->default($request, __CLASS__.'::list', new AjaxDialogUIAction(['restore_url' => $this->generateUrl('<?= $list_route; ?>')]))
            ?? $this->controllerHelper->crudCreate($request, $this->createForm(Form::class), $this->adapter)
            ?? ResponseUpdater::closeDialog(new SwalNotificationResponse(['title' => '<?= $entity_class_name; ?> created']));
    }

<?php if ($use_attributes) { ?>
    #[Route('/{id}/update', name: '<?= $update_route; ?>', methods: ['GET', 'POST'])]
<?php } else { ?>
    /**
     * @Route("/{id}/update", name="<?= $update_route; ?>", methods={"GET", "POST"})
     */
<?php } ?>
    public function update(Request $request, <?= $entity_class_name; ?> $entity): Response
    {
        return $this->controllerHelper->default($request, __CLASS__.'::list', new AjaxDialogUIAction(['restore_url' => $this->generateUrl('<?= $list_route; ?>')]))
            ?? $this->controllerHelper->crudUpdate($request, $this->createForm(Form::class, $entity), $this->adapter)
            ?? ResponseUpdater::closeDialog(new SwalNotificationResponse(['title' => '<?= $entity_class_name; ?> updated']));
    }

<?php if ($use_attributes) { ?>
    #[Route('/{id}/delete', name: '<?= $delete_route; ?>', methods: ['GET', 'DELETE'])]
<?php } else { ?>
    /**
     * @Route("/{id}/delete", name="<?= $delete_route; ?>", methods={"GET", "DELETE"})
     */
<?php } ?>
    public function delete(Request $request, <?= $entity_class_name; ?> $entity): Response
    {
        return $this->controllerHelper->crudDelete($request, $entity, $this->adapter)
            ?? ResponseUpdater::closeDialog(new SwalNotificationResponse(['title' => '<?= $entity_class_name; ?> deleted']));
    }
<?php } ?>
}
