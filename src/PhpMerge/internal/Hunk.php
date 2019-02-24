<?php
/**
 * This file is part of the php-merge package.
 *
 * (c) Fabian Bircher <opensource@fabianbircher.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpMerge\internal;

use SebastianBergmann\Diff\Line as DiffLine;

/**
 * Class Hunk.
 *
 * This represents a collection of changed lines.
 *
 * @author     Fabian Bircher <opensource@fabianbircher.com>
 * @copyright  Fabian Bircher <opensource@fabianbircher.com>
 * @license    https://opensource.org/licenses/MIT
 *
 * @version    Release: @package_version@
 *
 * @link       http://github.com/bircher/php-merge
 *
 * @internal   This class is not part of the public api.
 */
final class Hunk
{
    const ADDED = 1;
    const REMOVED = 2;
    const REPLACED = 3;

    /**
     * @var int
     */
    protected $start = 0;
    /**
     * @var int
     */
    protected $end = 0;
    /**
     * @var Line[]
     */
    protected $lines = [];
    /**
     * @var int
     */
    protected $type = 0;

    /**
     * The Hunk constructor.
     *
     * @param Line|Line[] $lines The lines belonging to the hunk.
     * @param int         $type  The type of the hunk: Hunk::ADDED Hunk::REMOVED Hunk::REPLACED
     * @param int         $start The line index where the hunk starts.
     * @param int         $end   The line index where the hunk stops.
     */
    public function __construct($lines, int $type, int $start, ?int $end = null)
    {
        $this->start = $start;
        if ($end === null) {
            $end = $start;
        }
        $this->end = $end;
        if (!is_array($lines)) {
            $lines = [$lines];
        }
        $this->lines = $lines;
        $this->type = $type;
    }

    /**
     * Add a new line to the hunk.
     *
     * @param \PhpMerge\Line $line The line to add.
     */
    public function addLine(Line $line):void
    {
        $this->lines[] = $line;
        $this->end = $line->getIndex();
    }

    /**
     * Create an array of hunks out of an array of lines.
     *
     * @param Line[] $lines The lines of the diff.
     *
     * @return Hunk[] The hunks in the lines.
     */
    public static function createArray(array $lines):array
    {
        $op = DiffLine::UNCHANGED;
        $hunks = [];
        /** @var Hunk $current */
        $current = null;
        foreach ($lines as $line) {
            switch ($line->getType()) {
                case DiffLine::REMOVED:
                    if ($op != DiffLine::REMOVED) {
                        // The last line was not removed so we start a new hunk.
                        $current = new self($line, self::REMOVED, $line->getIndex());
                    } else {
                        // continue adding the line to the hunk.
                        $current->addLine($line);
                    }
                    break;
                case DiffLine::ADDED:
                    switch ($op) {
                        case DiffLine::REMOVED:
                            // The hunk is a replacement.
                            $current->setType(self::REPLACED);
                            $current->addLine($line);
                            break;
                        case DiffLine::ADDED:
                            $current->addLine($line);
                            break;
                        case DiffLine::UNCHANGED:
                            // Add a new hunk with the added type.
                            $current = new self($line, self::ADDED, $line->getIndex());
                            break;
                    }
                    break;
                case DiffLine::UNCHANGED:
                    if ($current) {
                        // The hunk exists so add it to the array.
                        $hunks[] = $current;
                        $current = null;
                    }
                    break;
            }
            $op = $line->getType();
        }
        if ($current) {
            // The last line was part of a hunk, so add it.
            $hunks[] = $current;
        }

        return $hunks;
    }

    /**
     * Set the type of the hunk.
     *
     * @param int $type
     */
    protected function setType(int $type):void
    {
        $this->type = $type;
    }

    /**
     * Get the line index where the hunk starts.
     *
     * @return int
     */
    public function getStart():int
    {
        return $this->start;
    }

    /**
     * Get the line index where the hunk ends.
     *
     * @return int
     */
    public function getEnd():int
    {
        return $this->end;
    }

    /**
     * Get the type of the hunk.
     *
     * @return int
     */
    public function getType():int
    {
        return $this->type;
    }

    /**
     * Get the lines of the hunk.
     *
     * @return Line[]
     */
    public function getLines():array
    {
        return $this->lines;
    }

    /**
     * Get the removed lines.
     *
     * @return Line[]
     */
    public function getRemovedLines():array
    {
        return array_values(array_filter(
            $this->lines,
            function (Line $line) {
                return $line->getType() == DiffLine::REMOVED;
            }
        ));
    }

    /**
     * Get the added lines.
     *
     * @return Line[]
     */
    public function getAddedLines():array
    {
        return array_values(array_filter(
            $this->lines,
            function (Line $line) {
                return $line->getType() == DiffLine::ADDED;
            }
        ));
    }

    /**
     * Get the lines content.
     *
     * @return string[]
     */
    public function getLinesContent():array
    {
        return array_map(
            function (Line $line) {
                return $line->getContent();
            },
            $this->getAddedLines()
        );
    }

    /**
     * Test whether the hunk is to be considered for a conflict resolution.
     *
     * @param int $line The line number in the original text to test.
     *
     * @return bool Whether the line is affected by the hunk.
     */
    public function isLineNumberAffected(int $line):bool
    {
        // Added lines also affect the ones afterwards in conflict resolution,
        // because they are added in between.
        $bleed = ($this->type == self::ADDED ? 1 : 0);

        return $line >= $this->start && $line <= $this->end + $bleed;
    }

    /**
     * @param \PhpMerge\Hunk|null $hunk
     *
     * @return bool
     */
    public function hasIntersection(?self $hunk = null):bool
    {
        if (!$hunk) {
            return false;
        }
        if ($this->type == self::ADDED && $hunk->type == self::ADDED) {
            return $this->start == $hunk->start;
        }

        return $this->isLineNumberAffected($hunk->start) || $this->isLineNumberAffected($hunk->end)
          || $hunk->isLineNumberAffected($this->start) || $hunk->isLineNumberAffected($this->end);
    }
}
