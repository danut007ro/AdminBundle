<?php

declare(strict_types=1);

namespace DG\AdminBundle\Adapter;

use DG\AdminBundle\Exception\UnexpectedTypeException;
use DG\AdminBundle\Result\Data\DataResultInterface;
use DG\AdminBundle\Table\TableRequest;
use Symfony\Component\Form\FormInterface;

class CallbackAdapter implements AdapterInterface
{
    /**
     * @var callable(TableRequest,?FormInterface):DataResultInterface
     */
    private $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function list(TableRequest $request, ?FormInterface $filter = null): DataResultInterface
    {
        $result = ($this->callback)($request, $filter);
        if (!$result instanceof DataResultInterface) {
            throw new UnexpectedTypeException($result, DataResultInterface::class);
        }

        return $result;
    }
}
