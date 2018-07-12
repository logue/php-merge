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

use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Line as DiffLine;

/**
 * Class Line
 *
 * @package    PhpMerge
 * @author     Fabian Bircher <opensource@fabianbircher.com>
 * @copyright  Fabian Bircher <opensource@fabianbircher.com>
 * @license    https://opensource.org/licenses/MIT
 * @version    Release: @package_version@
 * @link       http://github.com/bircher/php-merge
 * @internal   This class is not part of the public api.
 */
final class Line
{

    /**
     * @var int
     */
    protected $index;
    /**
     * @var DiffLine
     */
    private $line;

    /**
     * Line constructor.
     *
     * @param DiffLine $line
     * @param int      $index
     */
    public function __construct(DiffLine $line, $index = null)
    {
        $this->index = $index;
        $this->line = $line;
    }

    /**
     * @return int
     */
    public function getIndex()
    {
        return $this->index;
    }

    public function getContent(): string
    {
        return $this->line->getContent();
    }

    public function getType(): int
    {
        return $this->line->getType();
    }

    /**
     * @param array $diff
     * @return Line[]
     */
    public static function createArray($diff)
    {
        $index = -1;
        $lines = [];
        foreach ($diff as $value) {
            switch ($value[1]) {
                case Differ::OLD:
                    $index++;
                    $line = new Line(new DiffLine(DiffLine::UNCHANGED, $value[0]), $index);
                    break;

                case Differ::ADDED:
                    $line = new Line(new DiffLine(DiffLine::ADDED, $value[0]), $index);
                    break;

                case Differ::REMOVED:
                    $index++;
                    $line = new Line(new DiffLine(DiffLine::REMOVED, $value[0]), $index);
                    break;

                default:
                    throw new \RuntimeException('Unsupported diff line type.');
            }
            $lines[] = $line;
        }
        return $lines;
    }
}
