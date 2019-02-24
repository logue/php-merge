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

use PhpMerge\internal\Hunk;
use PhpMerge\internal\Line;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\Diff\Line as DiffLine;

/**
 * Class HunkTest.
 *
 * @group hunk
 */
class HunkTest extends TestCase
{
    public function testCreate():void
    {
        $lines = [
            new Line(new DiffLine(DiffLine::ADDED, 'added'), -1),
            new Line(new DiffLine(DiffLine::UNCHANGED, 'unchanged'), 0),
            new Line(new DiffLine(DiffLine::REMOVED, 'replaced'), 1),
            new Line(new DiffLine(DiffLine::ADDED, 'replacement'), 1),
            new Line(new DiffLine(DiffLine::UNCHANGED, 'unchanged'), 2),
            new Line(new DiffLine(DiffLine::REMOVED, 'removed'), 3),
        ];

        $expected = [
            new Hunk($lines[0], Hunk::ADDED, -1, -1),
            new Hunk([$lines[2], $lines[3]], Hunk::REPLACED, 1, 1),
            new Hunk($lines[5], Hunk::REMOVED, 3, 3),
        ];
        $result = Hunk::createArray($lines);

        $this->assertEquals($expected, $result);
        $this->assertEquals([$lines[2], $lines[3]], $result[1]->getLines());
        $this->assertEquals([$lines[2]], $result[1]->getRemovedLines());
        $this->assertEquals([$lines[3]], $result[1]->getAddedLines());
        $this->assertEquals(['replacement'], $result[1]->getLinesContent());
    }
}
