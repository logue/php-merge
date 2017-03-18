<?php
/**
 * This file is part of the php-merge package.
 *
 * (c) Fabian Bircher <opensource@fabianbircher.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpMerge;

/**
 * Class Hunk
 *
 * This represents a collection of changed lines.
 *
 * @package    PhpMerge
 * @author     Fabian Bircher <opensource@fabianbircher.com>
 * @copyright  Fabian Bircher <opensource@fabianbircher.com>
 * @license    https://opensource.org/licenses/MIT
 * @version    Release: @package_version@
 * @link       http://github.com/bircher/php-merge
 */
class Hunk
{

    const ADDED = 1;
    const REMOVED = 2;
    const REPLACED = 3;

    /**
     * @var int
     */
    protected $start;
    /**
     * @var int
     */
    protected $end;
    /**
     * @var Line[]
     */
    protected $lines;
    /**
     * @var int
     */
    protected $type;

    /**
     * The Hunk constructor.
     *
     * @param Line|Line[] $lines
     *   The lines belonging to the hunk.
     * @param int $type
     *   The type of the hunk: Hunk::ADDED Hunk::REMOVED Hunk::REPLACED
     * @param int $start
     *   The line index where the hunk starts.
     * @param int $end
     *   The line index where the hunk stops.
     */
    public function __construct($lines, $type, $start, $end = null)
    {
        $this->start = $start;
        if ($end === null) {
            $end= $start;
        }
        $this->end = $end;
        if (!is_array($lines)) {
            $lines = array($lines);
        }
        $this->lines = $lines;
        $this->type = $type;
    }

    /**
     * Add a new line to the hunk.
     *
     * @param \PhpMerge\Line $line
     *   The line to add.
     */
    public function addLine(Line $line)
    {
        $this->lines[] = $line;
        $this->end = $line->getIndex();
    }

    /**
     * Create an array of hunks out of an array of lines.
     *
     * @param Line[] $lines
     *   The lines of the diff.
     * @return Hunk[]
     *   The hunks in the lines.
     */
    public static function createArray($lines)
    {
        $op = Line::UNCHANGED;
        $hunks = [];
        /** @var Hunk $current */
        $current = null;
        foreach ($lines as $line) {
            switch ($line->getType()) {
                case Line::REMOVED:
                    if ($op != Line::REMOVED) {
                        // The last line was not removed so we start a new hunk.
                        $current = new Hunk($line, Hunk::REMOVED, $line->getIndex());
                    } else {
                        // continue adding the line to the hunk.
                        $current->addLine($line);
                    }
                    break;
                case Line::ADDED:
                    switch ($op) {
                        case Line::REMOVED:
                            // The hunk is a replacement.
                            $current->setType(Hunk::REPLACED);
                            $current->addLine($line);
                            break;
                        case Line::ADDED:
                            $current->addLine($line);
                            break;
                        case Line::UNCHANGED:
                            // Add a new hunk with the added type.
                            $current = new Hunk($line, Hunk::ADDED, $line->getIndex());
                            break;
                    }
                    break;
                case Line::UNCHANGED:
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
    protected function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Get the line index where the hunk starts.
     *
     * @return int
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Get the line index where the hunk ends.
     *
     * @return int
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * Get the type of the hunk.
     *
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get the lines of the hunk.
     *
     * @return Line[]
     */
    public function getLines()
    {
        return $this->lines;
    }

    /**
     * Get the removed lines.
     *
     * @return Line[]
     */
    public function getRemovedLines()
    {
        return array_values(array_filter(
            $this->lines,
            function (Line $line) {
                return $line->getType() == Line::REMOVED;
            }
        ));
    }

    /**
     * Get the added lines.
     *
     * @return Line[]
     */
    public function getAddedLines()
    {
        return array_values(array_filter(
            $this->lines,
            function (Line $line) {
                return $line->getType() == Line::ADDED;
            }
        ));
    }

    /**
     * Get the lines content.
     *
     * @return string[]
     */
    public function getLinesContent()
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
     * @param int $line
     *   The line number in the original text to test.
     *
     * @return bool
     *   Whether the line is affected by the hunk.
     */
    public function isLineNumberAffected($line)
    {
        // Added lines also affect the ones afterwards in conflict resolution,
        // because they are added in between.
        $bleed = ($this->type == self::ADDED ? 1 : 0);
        return ($line >= $this->start && $line <= $this->end + $bleed);
    }

    /**
     * @param \PhpMerge\Hunk|null $hunk
     * @return bool
     */
    public function hasIntersection(Hunk $hunk = null)
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
