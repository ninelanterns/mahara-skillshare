<?php
/**
 * Mahara: Electronic portfolio, weblog, resume builder and social networking
 * Copyright (C) 2006-2009 Catalyst IT Ltd and others; see:
 *                         http://wiki.mahara.org/Contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    mahara
 * @subpackage artefact-skillshare
 * @author     Mike Kelly UAL m.f.kelly@arts.ac.uk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 *
 */

define('INTERNAL', 1);
define('PUBLIC', 1);
define('NOCHECKREQUIREDFIELDS', 1);
require_once(dirname(dirname(dirname(__FILE__))) . '/init.php');
require_once(get_config('libroot') . 'file.php');

$type = param_alpha('type');

switch ($type) {
    case 'skillshareimage':
        $id = param_integer('id', 0);
        $size = get_imagesize_parameters();
        $earlyexpiry = param_boolean('earlyexpiry');

        if ($id) {
        	$mimetype = get_field('artefact_file_files', 'filetype', 'artefact', $id);
            if ($path = get_dataroot_image_path('artefact/file/skillshareimages', get_field('artefact_file_files', 'fileid', 'artefact', $id), $size)) {
                if ($mimetype) {
                    header('Content-type: ' . $mimetype);
                    $maxage = 600; // 10 minutes
                    header('Expires: '. gmdate('D, d M Y H:i:s', time() + $maxage) .' GMT');
                    header('Cache-Control: max-age=' . $maxage);
                    header('Pragma: public');
                    readfile_exit($path);
                }
            }
        }
}

function readfile_exit($path) {
    readfile($path);
    perf_to_log();
    exit;
}