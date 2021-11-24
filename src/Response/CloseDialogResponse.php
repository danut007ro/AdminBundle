<?php

declare(strict_types=1);

namespace DG\AdminBundle\Response;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * JSON response that also adds header to close dialog.
 */
final class CloseDialogResponse extends JsonResponse
{
    protected function update(): self
    {
        ResponseUpdater::closeDialog($this);
        parent::update();

        return $this;
    }
}
