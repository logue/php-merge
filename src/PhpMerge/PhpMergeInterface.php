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
 * Interface PhpMergeInterface.
 *
 * @author     Fabian Bircher <opensource@fabianbircher.com>
 * @copyright  Fabian Bircher <opensource@fabianbircher.com>
 * @license    https://opensource.org/licenses/MIT
 *
 * @version    Release: @package_version@
 *
 * @link       http://github.com/bircher/php-merge
 */
interface PhpMergeInterface
{
    /**
     * Merge texts.
     *
     * @param string $base   The original text.
     * @param string $remote The first variant text.
     * @param string $local  The second variant text.
     *
     * @throws MergeException Thrown when there is a merge conflict.
     *
     * @return string The merge result.
     */
    public function merge(string $base, string $remote, string $local) : string;
}
