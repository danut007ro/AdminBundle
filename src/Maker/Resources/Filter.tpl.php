<?= "<?php\n" ?>

declare(strict_types=1);

namespace <?= $namespace; ?>;

<?= $uses_sort([
    'DG\AdminBundle\Form\Type\RowType',
    'Lexik\Bundle\FormFilterBundle\Filter\Form\Type\TextFilterType',
    'Symfony\Component\Form\AbstractType',
    'Symfony\Component\Form\FormBuilderInterface',
]); ?>

class Filter extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /*
        $builder
            ->add('row1_start', RowType::class, ['start' => true])
            ->add('name', TextFilterType::class, [
                'row_attr' => ['class' => 'col'],
            ])
            ->add('row1_end', RowType::class)
        ;
        */
    }
}
