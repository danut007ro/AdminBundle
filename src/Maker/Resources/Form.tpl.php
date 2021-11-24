<?= "<?php\n" ?>

declare(strict_types=1);

namespace <?= $namespace; ?>;

<?= $uses_sort([
    $entity_full_class_name,
    'Symfony\Component\Form\AbstractType',
    'Symfony\Component\Form\FormBuilderInterface',
    'Symfony\Component\OptionsResolver\OptionsResolver',
]); ?>

class Form extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Fields to show in form.
        // $builder->add('name');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => <?= $entity_class_name; ?>::class,
        ]);
    }
}
