<?php

declare(strict_types=1);

namespace DG\AdminBundle\Column;

use DG\AdminBundle\UIAction\UIActionInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class ActionColumn extends TwigColumn
{
    protected OptionsResolver $resolver;

    public function __construct(
        protected TranslatorInterface $translator,
        protected Environment $twig,
    ) {
        parent::__construct($twig);

        $this->resolver = new OptionsResolver();
        $this->configureActionOptions($this->resolver);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver
            ->remove([
                'value_extractor', // There is no need for `value_extractor` from TwigColumn.
                'sortable',
            ])
            ->setDefaults([
                'actions' => [],
                'template' => '@DGAdmin/column/action.html.twig',
            ])
            ->setAllowedTypes('actions', ['array', 'callable'])
            ->setInfo('actions', 'Array with actions or callback that returns actions.')
        ;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getValue(mixed $row): array
    {
        $actions = \is_callable($this->options['actions']) ? $this->options['actions']($row) : $this->options['actions'];
        if (!\is_array($actions)) {
            throw new InvalidOptionsException(sprintf('Invalid type for "actions" option, expected array, but got "%s".', get_debug_type($actions)));
        }

        return array_map(
            fn (array $action): array => $this->resolver->resolve($action),
            $actions,
        );
    }

    public function isSortable(): bool
    {
        return false;
    }

    protected function configureActionOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'url' => '#',
                'attr' => [],
                'icon' => '',
                'text' => '',
                'ui_action' => null,
                'is_granted' => '',
            ])
            ->setAllowedTypes('url', 'string')
            ->setAllowedTypes('attr', 'array')
            ->setAllowedTypes('icon', 'string')
            ->setAllowedTypes('text', 'string')
            ->setAllowedTypes('ui_action', ['null', UIActionInterface::class])
            ->setAllowedTypes('is_granted', 'string')
            ->setInfo('url', 'The `href` html attribute for button.')
            ->setInfo('attr', 'The attributes for button. If no `class` is specified then it defaults to `btn btn-sm btn-outline-primary`.')
            ->setInfo('icon', 'Icon class for `<i>` element inside button.')
            ->setInfo('text', 'Text to display on button.')
            ->setInfo('ui_action', 'UI action to be passed to frontend.')
            ->setInfo('is_granted', 'This permission needs to be granted to show this action.')
        ;
    }
}
