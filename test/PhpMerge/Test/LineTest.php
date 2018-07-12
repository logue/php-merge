<?php
/**
 * This file is part of the php-merge package.
 *
 * (c) Fabian Bircher <opensource@fabianbircher.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpMerge\Test;

use PhpMerge\internal\Line;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Line as DiffLine;

/**
 * Class LineTest
 *
 * @package PhpMerge\Test
 */
class LineTest extends TestCase
{

    public function testCreate()
    {

        $before = <<<'EOD'
unchanged
replaced
unchanged
removed
EOD;
        $after  = <<<'EOD'
added
unchanged
replacement
unchanged

EOD;

        $diff = [
            ["added\n", Differ::ADDED],
            ["unchanged\n", Differ::OLD],
            ["replaced\n", Differ::REMOVED],
            ["replacement\n", Differ::ADDED],
            ["unchanged\n", Differ::OLD],
            ["removed", Differ::REMOVED],
        ];

        $lines = [
            new Line(new DiffLine(DiffLine::ADDED, "added\n"), -1),
            new Line(new DiffLine(DiffLine::UNCHANGED, "unchanged\n"), 0),
            new Line(new DiffLine(DiffLine::REMOVED, "replaced\n"), 1),
            new Line(new DiffLine(DiffLine::ADDED, "replacement\n"), 1),
            new Line(new DiffLine(DiffLine::UNCHANGED, "unchanged\n"), 2),
            new Line(new DiffLine(DiffLine::REMOVED, "removed"), 3),
        ];

        $differ     = new Differ();
        $array_diff = $differ->diffToArray($before, $after);
        $this->assertEquals($diff, $array_diff);

        $result = Line::createArray($diff);
        $this->assertEquals($lines, $result);

        try {
            $diff[] = ['invalid', 3];
            Line::createArray($diff);
            $this->assertTrue(false, 'An exception was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertEquals('Unsupported diff line type.', $e->getMessage());
        }
    }
}
