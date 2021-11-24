<?php

declare(strict_types=1);

namespace DG\AdminBundle\Form\Extension;

use DG\AdminBundle\Form\Type\Select2EntityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

class Select2Extension extends AbstractTypeExtension
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    /**
     * @return iterable<class-string<FormTypeInterface>>
     */
    public static function getExtendedTypes(): iterable
    {
        return [Select2EntityType::class, ChoiceType::class];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'dg_admin_select2' => static fn (Options $options) => null !== $options['dg_admin_select2_placeholder'],
                'dg_admin_select2_placeholder' => null,
            ])
            ->setAllowedTypes('dg_admin_select2', ['bool', 'string'])
            ->setAllowedTypes('dg_admin_select2_placeholder', ['null', 'string', TranslatableMessage::class, 'array'])
            ->setNormalizer('dg_admin_select2_placeholder', static function (Options $options, $value): ?array {
                if (null !== $value) {
                    if (\is_string($value) || $value instanceof TranslatableMessage) {
                        $value = [$value, $value];
                    } elseif (0 === \count($value)) {
                        $value = null;
                    } elseif (1 === \count($value)) {
                        $value[] = reset($value);
                    }
                }

                return $value;
            })

            ->setInfo('dg_admin_select2_placeholder', 'Specify the placeholders to show.')
        ;
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if (false === $options['dg_admin_select2']) {
            return;
        }

        /** @var array<string, mixed> $view->vars */
        $vars = &$view->vars;
        $vars['attr']['class'] = ($vars['attr']['class'] ?? '').' dg-admin-select2';

        // Set placeholder.
        if (null !== $options['dg_admin_select2_placeholder']) {
            $placeholder = ($options['multiple'] ?? false)
                ? $options['dg_admin_select2_placeholder'][1]
                : $options['dg_admin_select2_placeholder'][0]
            ;

            if ($placeholder instanceof TranslatableMessage) {
                $placeholder = $placeholder->trans($this->translator);
            }

            $vars['attr']['data-placeholder'] = $placeholder;
        }
    }
}
