<?php

declare(strict_types=1);

namespace DG\AdminBundle\Adapter;

use DG\AdminBundle\Result\Data\DataResultInterface;
use DG\AdminBundle\Table\TableRequest;
use Symfony\Component\Form\FormInterface;

interface AdapterInterface
{
    /**
     * List data for this request using filter.
     */
    public function list(TableRequest $request, ?FormInterface $filter = null): DataResultInterface;
}
