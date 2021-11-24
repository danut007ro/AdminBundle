<?php

declare(strict_types=1);

namespace DG\AdminBundle\BatchAction;

use DG\AdminBundle\Exception\MissingDependencyException;
use DG\AdminBundle\Exception\RuntimeException;
use DG\AdminBundle\Formatter\FormatterInterface;
use DG\AdminBundle\Result\Data\DataResultInterface;
use DG\AdminBundle\Table\Table;
use DG\AdminBundle\Table\TableRequest;
use DG\AdminBundle\TableHelper;
use Port\Csv\CsvWriter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExportCsvBatchAction extends AbstractExportBatchAction
{
    public const NAME = '_dg_admin.export_csv';

    public function __construct(
        TableHelper $tableHelper,
        TranslatorInterface $translator,
    ) {
        parent::__construct($tableHelper, $translator);

        if (!class_exists(CsvWriter::class)) {
            throw new MissingDependencyException('Install "portphp/csv" to export as CSV.');
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver
            ->setDefaults([
                'label' => $this->translator->trans('Export CSV', [], 'dg_admin'),
                'filename' => 'export.csv',
            ])
            ->setAllowedTypes('filename', 'string')
            ->setInfo('filename', 'Filename for the download.')
        ;
    }

    public function handleRequest(Request $request, TableRequest $tableRequest, Table $table, DataResultInterface $result, FormatterInterface $formatter): Response
    {
        if (null !== $response = $this->validateSelectionNotEmpty($result)) {
            return $response;
        }

        if (false === $name = tempnam('', 'export_csv')) {
            throw new RuntimeException('Unable to generate temporary filename.');
        }

        if (false === $file = fopen($name, 'w')) {
            throw new RuntimeException(sprintf('Unable to open temporary file "%s".', $name));
        }

        $writer = (new CsvWriter())->setStream($file);
        $writer->prepare();

        $response = $this->processResult($table, $result, $writer, $name, $this->options['filename']);
        $response->headers->set('Content-Type', 'text/csv');

        return $response;
    }
}
