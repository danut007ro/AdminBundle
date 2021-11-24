<?php

declare(strict_types=1);

namespace DG\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class RowType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['start'] = $options['start'];
        $view->vars['class'] = $options['class'];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'start' => false,
                'class' => 'form-row',
                'mapped' => false,
            ])
            ->setAllowedTypes('start', 'bool')
            ->setAllowedTypes('class', 'string')
            ->setInfo('start', 'Specify if should start or end this row.')
            ->setInfo('class', 'Specify the class name that should be used on row (form-row or row).')
        ;
    }
}
