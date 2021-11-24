<?php

declare(strict_types=1);

namespace DG\AdminBundle\UIAction;

use Symfony\Component\OptionsResolver\OptionsResolver;

class AjaxDialogUIAction extends AjaxUIAction
{
    public const NAME = '_dg_admin.uiaction.ajaxDialog';

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver
            ->setDefaults([
                'restore_url' => null,
                'form_selector' => 'form',
                'append_body_data' => false,
            ])
            ->setAllowedTypes('restore_url', ['null', 'string'])
            ->setAllowedTypes('form_selector', 'string')
            ->setAllowedTypes('append_body_data', 'bool')
            ->setInfo(
                'restore_url',
                <<<'INFO'
                Specify if url should be set to the one specified in `href`.
                Use `NULL` to not change url.
                Empty string will change back to last url.
                INFO
            )
            ->setInfo('form_selector', 'Specify form selector to process from dialog.')
            ->setInfo('append_body_data', 'Specify if should append `url_parameters > body` data to form.')
        ;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getParameters(): array
    {
        return array_merge(
            parent::getParameters(),
            [
                'restore_url' => $this->options['restore_url'],
                'form_selector' => $this->options['form_selector'],
                'append_body_data' => $this->options['append_body_data'],
            ],
        );
    }
}
