<?php

declare(strict_types=1);

namespace Tests\Unit;

use DG\AdminBundle\Exception\InvalidArgumentException;
use DG\AdminBundle\KeepOrderHelper;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \DG\AdminBundle\KeepOrderHelper
 *
 * @internal
 */
final class KeepOrderHelperTest extends TestCase
{
    public function testThrowsExceptionOnInvalidKeys(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new KeepOrderHelper([['k1' => 'v1'], ['k2' => 'v2']], ['k']);
    }

    public function testUseValues(): void
    {
        $result = (new KeepOrderHelper([['v' => 1, 'k' => 1], ['k' => 2, 'v' => 2]], ['k', 'v'], false))
            ->setRows([['k' => 1, 'v' => 1], []])
            ->processRow(true)
        ;

        static::assertEquals([true, false], $result);
    }

    public function testUseCallbacks(): void
    {
        $result = (new KeepOrderHelper(
            [
                ['v' => 1, 'k' => 1],
                ['x' => 2, 'k' => 2, 'v' => 2],
                ['k' => 3, 'v' => 3, 'x' => 3],
            ],
            ['k', 'v'],
            false,
        ))
            ->setRows(static function (): array {
                return [['k' => 1, 'v' => 1], [], ['k' => 3, 'v' => 3]];
            })
            ->processRow(static function (array $row): ?bool {
                if ($row === ['k' => 1, 'v' => 1]) {
                    return true;
                }

                if ($row === ['k' => 3, 'v' => 3]) {
                    return null;
                }

                return true;
            })
        ;

        static::assertEquals([true, false, null], $result);
    }

    public function testUseKeysFunction(): void
    {
        $result = (new KeepOrderHelper(
            ['a', 'b', 'c'],
            static fn (array $row): string => $row['x'],
            false,
        ))
            ->setRows(static function (): array {
                return [['x' => 'b'], ['x' => 'c'], ['x' => 'a']];
            })
            ->process()
        ;

        static::assertEquals([['x' => 'a'], ['x' => 'b'], ['x' => 'c']], $result);
    }
}
