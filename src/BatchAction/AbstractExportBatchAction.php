<?php

declare(strict_types=1);

namespace DG\AdminBundle\BatchAction;

use DG\AdminBundle\Result\Data\DataResultInterface;
use DG\AdminBundle\Table\Table;
use Port\Reader\IteratorReader;
use Port\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

abstract class AbstractExportBatchAction extends AbstractBatchAction
{
    protected function processResult(Table $table, DataResultInterface $result, Writer $writer, string $generatedFilename, string $downloadFilename): Response
    {
        $header = [];
        foreach ($table->getColumns() as $name => $column) {
            if (!$column->isVisible()) {
                continue;
            }

            $header[$name] = $column->getLabel();
        }

        $writer->writeItem($header);

        foreach (new IteratorReader($result->getData()) as $row) {
            $item = [];
            foreach ($row as $name => $value) {
                if (\array_key_exists($name, $header)) {
                    $item[$name] = $value;
                }
            }

            $writer->writeItem($item);
        }

        $writer->finish();

        return (new BinaryFileResponse($generatedFilename))
            ->deleteFileAfterSend()
            ->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $downloadFilename,
            )
        ;
    }
}
