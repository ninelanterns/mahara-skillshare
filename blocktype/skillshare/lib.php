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
 * @subpackage blocktype-skillshare
 * @author     Mike Kelly
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 *
 */

defined('INTERNAL') || die();

class PluginBlocktypeSkillshare extends PluginBlocktype {

    public static function get_title() {
        return get_string('title', 'blocktype.skillshare/skillshare');
    }

    public static function get_description() {
        return get_string('description', 'blocktype.skillshare/skillshare');
    }

    public static function get_categories() {
        return array('internal');
    }

    public static function render_instance(BlockInstance $instance, $editing=false) {
        $owner = $instance->get_view()->get_owner_object();
        $ownerid = $owner->id;    
        $wwwroot = get_config('wwwroot');
        $images = get_records_sql_array('SELECT a.id, a.title, a.description, a.note
                                        FROM {artefact} a
                                        WHERE artefacttype = \'skillshareimage\'
                                        AND a.owner = ?
                                        ORDER BY a.note, a.id', array($ownerid));

        if ($images){
            foreach ($images as $image){
                $exampleimages[] = array(
                            'link' => $wwwroot . 'artefact/skillshare/image.php?type=skillshareimage&id=' . $image->id, 
                            'source' => $wwwroot . 'artefact/skillshare/image.php?type=skillshareimage&maxsize=100&id=' . $image->id,
                            'title' => $image->description);
            }
        } else {
            $exampleimages = null;
        }
        
        $skillshare = get_record_sql('
            SELECT *
            FROM {artefact_skillshare} s 
            INNER JOIN {artefact} a 
            ON s.artefact = a.id
            WHERE a.owner = ?', array($ownerid)
        );

        if (!isset($skillshare->statement)) {
            return '';
        }
        if (isset($skillshare->externalwebsite)){
            $externalwebsite = '<a href="' . hsc($skillshare->externalwebsite) . '">' . hsc($skillshare->externalwebsite) . '</a>';
        }

        $smarty = smarty_core();
        $smarty->assign('statementtitle', $skillshare->statementtitle);
        $smarty->assign('statement', $skillshare->statement);
        $smarty->assign('images', $exampleimages);
        $smarty->assign('wanted', $skillshare->wanted);
        $smarty->assign('offered', $skillshare->offered);
        $smarty->assign('externalwebsite', isset($externalwebsite)? $externalwebsite : '');
        $smarty->assign('externalwebsiterole', isset($skillshare->externalwebsiterole)? $skillshare->externalwebsiterole : '');
        return $smarty->fetch('blocktype:skillshare:content.tpl');
    }

    // Yes, we do have instance config. People are allowed to specify the title 
    // of the block, nothing else at this time. So in the next two methods we 
    // say yes and return no fields, so the title will be configurable.
    public static function has_instance_config() {
        return true;
    }

    public static function instance_config_form() {
        return array();
    }

    public static function default_copy_type() {
        return 'shallow';
    }

    public static function artefactchooser_element($default=null) {
        return array();
    }

    /**
     * Skillshare blocktype is only allowed in personal views, because 
     * there's no such thing as group/site resumes
     */
    public static function allowed_in_view(View $view) {
        return $view->get('owner') != null;
    }

}
