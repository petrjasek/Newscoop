<?php
/**
 * @package Newscoop
 * @copyright 2013 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

/**
 * Filesystem utils
 */
class Filesystem
{
    /**
     * Recursive copy function
     *
     * @param string $source
     * @param string $dest
     */
    public static function copyr($source, $dest)
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            if (realpath($dest . '/' . $iterator->getSubPathName())) {
                continue;
            }

            if ($item->isDir()) {
                mkdir($dest . '/' . $iterator->getSubPathName());
            } else {
                copy($item, $dest . '/' . $iterator->getSubPathName());
            }
        }
    }

    /**
     * Recursive unlink function
     *
     * @param string $filename
     */
    public static function unlinkr($filename)
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($filename, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item);
            } else {
                unlink($item);
            }
        }
    }
}
