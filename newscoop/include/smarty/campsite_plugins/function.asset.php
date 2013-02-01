<?php
/**
 * @package Newscoop
 * @copyright 2013 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

use Assetic\Asset\FileAsset;
use Assetic\Asset\GlobAsset;
use Assetic\AssetCollection;
use Assetic\AssetWriter;

/**
 * Get static url for given asset
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_function_asset($params, $smarty)
{
    define('STATIC_DIR', APPLICATION_PATH . '/../static');
    define('STATIC_URL', '/static');

    if (!array_key_exists($params['root'])) {
        $resource = $smarty->template_resource;
        $filename = $smarty->smarty->_current_file;
        $params['root'] = substr($filename, 0, -1 * strlen($resource));
    }

    switch (true) {
        case array_key_exists('file', $params):
            $asset = new FileAsset($params['root'] . $params['file']);
            $params['extension'] = pathinfo($params['file'], PATHINFO_EXTENSION);
            break;

        case array_key_exists('glob', $params):
            $asset = new GlobAsset($params['root'] . $params['glob']);
            $params['extension'] = pathinfo($params['glob'], PATHINFO_EXTENSION);
            break;

        default:
            return;
    }

    if (array_key_exists('target', $params)) {
        $asset->setTargetPath($params['target']);
    } else {
        $params['mtime'] = $asset->getLastModified();
        $asset->setTargetPath(sha1(json_encode($params)) . '.' . $params['extension']);
    }

    if (!file_exists(STATIC_DIR . '/' . $asset->getTargetPath())) {
        $writer = new AssetWriter(STATIC_DIR);
        $writer->writeAsset($asset);
    }

    return $smarty->getTemplateVars('view')->baseUrl(STATIC_URL . '/' . $asset->getTargetPath());
}
