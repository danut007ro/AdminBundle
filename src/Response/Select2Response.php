<?php

declare(strict_types=1);

namespace DG\AdminBundle\Response;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * JSON response for select2.
 */
final class Select2Response extends JsonResponse
{
    /**
     * @param array{id:string, text:string}[] $results
     * @param array<string, mixed> $headers
     */
    public function __construct(
        private array $results = [],
        private ?int $perPage = null,
        int $status = 200,
        array $headers = [],
    ) {
        $more = false;

        if (null !== $this->perPage && $this->perPage > 0) {
            $more = \count($results) > $perPage;
            $results = \array_slice($results, 0, $perPage);
        } else {
            $this->perPage = null;
        }

        parent::__construct(
            [
                'results' => $results,
                'pagination' => ['more' => $more],
            ],
            $status,
            $headers,
        );
    }

    /**
     * @return array{id:string, text:string}[]
     */
    public function getResults(): array
    {
        return $this->results;
    }

    public function getPerPage(): ?int
    {
        return $this->perPage;
    }
}
