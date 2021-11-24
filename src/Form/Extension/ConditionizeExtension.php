<?php

declare(strict_types=1);

namespace DG\AdminBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConditionizeExtension extends AbstractTypeExtension
{
    /**
     * @return iterable<class-string<FormTypeInterface>>
     */
    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('dg_admin_condition', null)
            ->setDefault('dg_admin_conditionize', [])
            ->setAllowedTypes('dg_admin_condition', ['string', 'null'])
            ->setAllowedTypes('dg_admin_conditionize', ['array', 'null'])
            ->setInfo(
                'dg_admin_condition',
                <<<'INFO'
                Specify the data-condition to be set for element.
                The `${parent.children[anotherElement]}` string will be replaced with `anotherElement` name.
                The `${#parent.children[anotherElement]}` string will be replaced with `anotherElement` id.
                This is needed because the name and id will also contain form name and you don't want to hardcode that.
                INFO
            )
            ->setInfo('dg_admin_conditionize', 'Specify the parameters to `conditionize()` method. Use NULL to not call the method.')
        ;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        /** @var mixed[] $view->vars */
        $vars = &$view->vars;

        if (null !== $condition = $options['dg_admin_condition']) {
            // This will be handled in AdminExtension.
            $vars['attr']['data-dg-admin-condition'] = $condition;
            if (null !== $conditionize = $options['dg_admin_conditionize']) {
                $vars['attr']['data-dg-admin-conditionize'] = json_encode($conditionize);
            }
        }
    }
}
