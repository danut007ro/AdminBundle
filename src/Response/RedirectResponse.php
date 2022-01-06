<?php

declare(strict_types=1);

namespace DG\AdminBundle\Response;

use Symfony\Component\HttpFoundation\RedirectResponse as BaseRedirectResponse;

/**
 * Response for redirecting a request.
 */
final class RedirectResponse extends BaseRedirectResponse
{
    /**
     * @param mixed[] $headers
     */
    public function __construct(string $url, int $status = 302, array $headers = [])
    {
        parent::__construct($url, $status, $headers);
    }

    public function setStatusCode(int $code, string $text = null): static
    {
        // Always set 200 status code, since the redirect will be handled by our javascript.
        return parent::setStatusCode(200);
    }

    public function setTargetUrl(string $url): static
    {
        ResponseUpdater::redirect($this);

        return parent::setTargetUrl($url);
    }
}
