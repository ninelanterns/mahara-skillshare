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
define('JSON', 1);
require(dirname(dirname(dirname(__FILE__))) . '/init.php');
require_once('file.php');

$action = param_alpha('action');

if ($action == 'getimages') {

    $result = get_records_sql_array('SELECT a.id, a.title, a.description, a.note
        FROM {artefact} a
        WHERE artefacttype = \'skillshareimage\'
        AND a.owner = ?
        ORDER BY a.note, a.id', array($USER->get('id')));
    
    if(!$result) {
        $result = array();
    }
    
    json_headers();
    $data['error'] = false;
    $data['data'] = $result;
    $data['count'] = ($result) ? count($result) : 0;
    echo json_encode($data);

} else if ($action == 'updateorder') {

    $global = $_POST;
     
    for ($i=0; $i<4; $i++){
        if (isset($global['order' . $i])){
                $dataobj = new StdClass;
                $dataobj->id = $global['order' . $i];
                $dataobj->note = 'order' . $i;
                $whereobj = new StdClass;
                $whereobj->id = param_alphanum('order' . $i);
                $whereobj->owner = $USER->get('id');
                $whereobj->artefacttype = 'skillshareimage';
                update_record('artefact', $dataobj, $whereobj);
            }
        }
} else {
    // TODO
    json_reply('local', get_string('badjsonrequest','artefact.skillshare'));
}
