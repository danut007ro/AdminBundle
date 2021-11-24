<?php

declare(strict_types=1);

namespace DG\AdminBundle\UIAction;

interface UIActionInterface
{
    public const DATA_NAME = 'data-dg-admin-uiaction';
    public const DATA_PARAMETERS = 'data-dg-admin-uiaction-parameters';
    public const DATA_DISABLE_AUTO = 'data-dg-admin-uiaction-disable-auto';
    public const KEY = '_dg_admin.uiaction';

    /**
     * Retrieves name of ui action.
     */
    public function getName(): string;

    /**
     * @return mixed[]
     */
    public function getParameters(): array;
}
