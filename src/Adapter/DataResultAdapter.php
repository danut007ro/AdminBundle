<?php

declare(strict_types=1);

namespace DG\AdminBundle\Adapter;

use DG\AdminBundle\Result\Data\DataResultInterface;
use DG\AdminBundle\Table\TableRequest;
use Symfony\Component\Form\FormInterface;

class DataResultAdapter implements AdapterInterface
{
    public function __construct(private DataResultInterface $result)
    {
    }

    public function list(TableRequest $request, ?FormInterface $filter = null): DataResultInterface
    {
        return $this->result;
    }
}
