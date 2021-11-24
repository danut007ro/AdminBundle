<?php

declare(strict_types=1);

namespace DG\AdminBundle\Response;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * JSON response that generates sweetalert2 notification.
 */
final class SwalNotificationResponse extends JsonResponse
{
    public const KEY = '_swal';

    /**
     * @param mixed[]              $data
     * @param array<string, mixed> $headers
     */
    public function __construct(array $data = [], int $status = 200, array $headers = [])
    {
        parent::__construct([self::KEY => $data], $status, $headers);
    }
}
