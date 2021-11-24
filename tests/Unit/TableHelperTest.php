<?php

declare(strict_types=1);

namespace Tests\Unit;

use DG\AdminBundle\DependencyInjection\Instantiator;
use DG\AdminBundle\Exception\InvalidArgumentException;
use DG\AdminBundle\Formatter\AjaxFormatterInterface;
use DG\AdminBundle\Formatter\FormatterInterface;
use DG\AdminBundle\Table\TableFactory;
use DG\AdminBundle\TableHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;
use Twig\Environment;

/**
 * @coversDefaultClass \DG\AdminBundle\TableHelper
 *
 * @internal
 */
final class TableHelperTest extends TestCase
{
    public function testNonUniqueTableNamesThrowsException(): void
    {
        $tableHelper = new TableHelper(
            '', // @phpstan-ignore-line
            $this->createMock(Instantiator::class),
            $this->createMock(TableFactory::class),
            $this->createMock(CsrfTokenManagerInterface::class),
            $this->createMock(TokenStorageInterface::class),
            $this->createMock(Environment::class),
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Table name "table" should be used only once or it\'s invalid. It should start with a letter, digit or underscore and only contain letters, digits, numbers, underscores ("_"), hyphens ("-") and colons (":").');

        $formatter = $this->createConfiguredMock(FormatterInterface::class, ['getTableName' => 'table']);
        $tableHelper->handleRequest($this->createMock(Request::class), [
            $formatter,
            $formatter,
        ]);
    }

    public function testNonUniqueTableNameUrlsThrowsException(): void
    {
        $tableHelper = new TableHelper(
            '', // @phpstan-ignore-line
            $this->createMock(Instantiator::class),
            $this->createMock(TableFactory::class),
            $this->createMock(CsrfTokenManagerInterface::class),
            $this->createMock(TokenStorageInterface::class),
            $this->createMock(Environment::class),
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Table name url "url1" should be used only once or it\'s invalid. It should start with a letter, digit or underscore and only contain letters, digits, numbers, underscores ("_"), hyphens ("-") and colons (":").');

        $formatter1 = $this->createConfiguredMock(AjaxFormatterInterface::class, ['getTableName' => 'table1', 'getTableNameUrl' => 'url1']);
        $formatter2 = $this->createConfiguredMock(AjaxFormatterInterface::class, ['getTableName' => 'table2', 'getTableNameUrl' => 'url1']);
        $tableHelper->handleRequest($this->createMock(Request::class), [
            $formatter1,
            $formatter2,
        ]);
    }

    public function testInvalidTableNameThrowsException(): void
    {
        $tableHelper = new TableHelper(
            '', // @phpstan-ignore-line
            $this->createMock(Instantiator::class),
            $this->createMock(TableFactory::class),
            $this->createMock(CsrfTokenManagerInterface::class),
            $this->createMock(TokenStorageInterface::class),
            $this->createMock(Environment::class),
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Table name ".table" should be used only once or it\'s invalid. It should start with a letter, digit or underscore and only contain letters, digits, numbers, underscores ("_"), hyphens ("-") and colons (":").');

        $formatter = $this->createConfiguredMock(FormatterInterface::class, ['getTableName' => '.table']);
        $tableHelper->handleRequest($this->createMock(Request::class), [$formatter]);
    }
}
