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
use Port\Spreadsheet\SpreadsheetWriter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExportXlsxBatchAction extends AbstractExportBatchAction
{
    public const NAME = '_dg_admin.export_xlsx';

    public function __construct(
        TableHelper $tableHelper,
        TranslatorInterface $translator,
    ) {
        parent::__construct($tableHelper, $translator);

        if (!class_exists(SpreadsheetWriter::class)) {
            throw new MissingDependencyException('Install "portphp/spreadsheet" to export as XLSX.');
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver
            ->setDefaults([
                'label' => $this->translator->trans('Export Excel', [], 'dg_admin'),
                'filename' => 'export.xlsx',
                'sheet' => null,
            ])
            ->setAllowedTypes('filename', 'string')
            ->setAllowedTypes('sheet', ['null', 'string'])
            ->setInfo('filename', 'Filename for the downloaded file.')
            ->setInfo('sheet', 'Sheet name inside xlsx file.')
        ;
    }

    public function handleRequest(Request $request, TableRequest $tableRequest, Table $table, DataResultInterface $result, FormatterInterface $formatter): Response
    {
        if (null !== $response = $this->validateSelectionNotEmpty($result)) {
            return $response;
        }

        if (false === $name = tempnam('', 'export_xlsx')) {
            throw new RuntimeException('Unable to generate temporary filename.');
        }

        $file = new \SplFileObject($name, 'w');
        $writer = new SpreadsheetWriter($file, $this->options['sheet']);
        $writer->prepare();

        $response = $this->processResult($table, $result, $writer, $name, $this->options['filename']);
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        return $response;
    }
}
