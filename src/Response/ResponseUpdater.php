<?php

declare(strict_types=1);

namespace DG\AdminBundle\Response;

use Symfony\Component\HttpFoundation\RedirectResponse as BaseRedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Update response with different options.
 */
final class ResponseUpdater
{
    public const HEADER_DIALOG_CLOSE = 'X-Dialog-Close';
    public const HEADER_REDIRECT = 'X-Redirect';

    public static function closeDialog(Response $response): Response
    {
        $response->headers->set(self::HEADER_DIALOG_CLOSE, '1');

        return $response;
    }

    public static function redirect(BaseRedirectResponse $response): BaseRedirectResponse
    {
        $response->setStatusCode(Response::HTTP_OK);
        $response->headers->set(self::HEADER_REDIRECT, $response->getTargetUrl());

        return $response;
    }
}
