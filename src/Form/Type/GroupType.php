<?php

declare(strict_types=1);

namespace DG\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

final class GroupType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['class'] = $options['class'];
        $view->vars['icon'] = $options['icon'];
        $view->vars['title'] = $options['title'];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'mapped' => false,
                'class' => '',
                'icon' => '',
                'title' => '',
            ])
            ->setAllowedTypes('class', 'string')
            ->setAllowedTypes('icon', 'string')
            ->setAllowedTypes('title', ['string', TranslatableMessage::class])
        ;
    }
}
