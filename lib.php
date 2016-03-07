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

defined('INTERNAL') || die();
safe_require('artefact', 'file');

class PluginArtefactSkillshare extends Plugin {
    
    public static function get_artefact_types() {
        return array('skillshare',
                     'skillshareimage');
    }
    
    public static function get_block_types() {
        return array(); 
    }

    public static function get_plugin_name() {
        return 'skillshare';
    }
    
    public static function postinst($oldversion) {
    }

    public static function menu_items() {
        return array(
            'content/skillshare' => array(
                'path' => 'content/skillshare',
                'title' => get_string('skillshare', 'artefact.skillshare'),
                'url' => 'artefact/skillshare/',
                'weight' => 55,
            ),
        );
    }

    public static function get_artefact_type_content_types() {
        return array();
    }
}

class ArtefactTypeSkillshare extends ArtefactType {
    
    protected $published = false;
    protected $offered = 0;
    protected $wanted = 0;
    protected $statement = '';
    protected $statementtitle = '';
    protected $tags = '';
    protected $externalwebsite = '';
    protected $externalwebsiterole = '';
    protected $publishskills = 0;

    public static function get_icon($options=null) {}

    public function __construct($id=0, $data=null) {
        if (empty($id)) {
            $data->title = get_string($this->get_artefact_type(), 'artefact.skillshare');
        }
        parent::__construct($id, $data);
    }
    
    public static function is_singular() {
        return true;
    }
    
    public static function get_links($id) {
    }

    /**
     * Default render method for fields - show their description
     */
    public function render_self($options) {
        return array('html' => clean_html($this->description));
    }
    
    public function delete() {
        if (empty($this->id)) {
            return;
        }
    
        db_begin();
        $owner = get_field('artefact', 'owner', 'id', $this->id);
        delete_records('artefact_skillshare', 'artefact', $this->id);
               
        if ($owner){
            $images = get_records_sql_array('SELECT a.id
                FROM {artefact} a
                WHERE artefacttype = \'skillshareimage\'
                AND a.owner = ?
                ORDER BY a.id', array($owner)
                );
        
            if ($images){
                $size = get_imagesize_parameters();
                foreach ($images as $image){
                    if ($path = get_dataroot_image_path('artefact/file/skillshareimages', get_field('artefact_file_files', 'fileid', 'artefact', $image->id), $size)) {
                        if (is_readable($path)){
                            unlink($path);
                        }     
                    }
                }
            }
            delete_records('artefact', 'artefacttype', 'skillshareimage', 'owner', $owner);
        }
        parent::delete();
        db_commit();
    }
    
    /**
    * This function updates or inserts the artefact.  This involves putting
    * some data in the artefact table (handled by parent::commit()), and then
    * some data in the artefact_skillshare table.
    */
    public function commit() {
        $this->dirty = true;
        
        db_begin();
        $new = empty($this->id);
        
        // TODO: not sure why I'm having to set these manually
        // If I don't, parent::commit resets ctime and mtime to null vals
        // I'm missing a trick somewhere in extending ArtefactType
        if (!$new){
            $artefact = artefact_instance_from_id($this->id);
            $this->ctime = $artefact->get('ctime');
            $this->set('mtime', time());
        }
        
        parent::commit();
        
        $data = (object)array(
                    'artefact' => $this->id,
                    'offered'=> $this->get('offered'),
                    'wanted'=> $this->get('wanted'),
                    'statement'=> $this->get('statement'),
                    'statementtitle'=> $this->get('statementtitle'),
                    'tags'=> $this->get('tags'), 
                    'externalwebsite'=> $this->get('externalwebsite'),
                    'externalwebsiterole'=> $this->get('externalwebsiterole'),
                    'publishskills'=> $this->get('publishskills'),
        );
        
        if ($new) {
            insert_record('artefact_skillshare', $data);
        } else {
            update_record('artefact_skillshare', $data, 'artefact');
        }
        
        // We want to get all blockinstances that contain this skillshare artefact.
        // With these, we tell them to rebuild what artefacts they have in them,
        // since the content could have changed and now have links to
        // different artefacts in it
        $blockinstanceids = (array)get_column_sql('SELECT block
                                                    FROM {view_artefact}
                                                    WHERE artefact = ?', array($this->get('id')));
        if ($blockinstanceids) {
            require_once(get_config('docroot') . 'blocktype/lib.php');
            foreach ($blockinstanceids as $id) {
                $instance = new BlockInstance($id);
                $instance->rebuild_artefact_list();
            }
        }

        db_commit();
        $this->dirty = false;
    }
}

class ArtefactTypeSkillshareImage extends ArtefactTypeImage {

    public static function get_links($id) {
        return array();
    }

    public static function get_icon($options=null) {
        $url = get_config('wwwroot') . 'thumb.php?type=profileiconbyid&id=' . hsc($options['id']);

        if (isset($options['size'])) {
            $url .= '&size=' . $options['size'];
        }
        else {
            $url .= '&size=60x60';
        }

        return $url;
    }

    public function get_path($data=array()) {
        require_once('file.php');
        $result = get_dataroot_image_path('artefact/file/skillshareimages/', $this->fileid, $data);
        return $result;
    }

    public function in_view_list() {
        return true;
    }

    public function default_parent_for_copy(&$view, &$template, $artefactstoignore) {
        return null;
    }

}

class ImageManipulator {

    private $image;
    private $width;
    private $height;
    private $imageresized;

    function __construct($filename, $mimetype){
        $this->image = $this->open_image($filename, $mimetype);
        if (!empty($this->image)) {
            $this->width  = imagesx($this->image);
            $this->height = imagesy($this->image);
        }
    }

    public function get_image(){
        return $this->image;
    }
    
    private function open_image($file, $mimetype){
        if (!$this->set_memory_for_image($file)) {
            return false;
        }
        switch ($mimetype) {
            case 'image/jpg':
            case 'image/jpeg':
                $img = imagecreatefromjpeg($file);
                break;
            case 'image/gif':
                $img = imagecreatefromgif($file);
                break;
            case 'image/png':
                $img = imagecreatefrompng($file);
                break;
            default:
                $img = false;
            break;
        }
        return $img;
    }

    public function resize_image($newwidth, $newheight, $mimetype, $option="auto"){
        // Get optimal width and height based on $size
        $optionarray = $this->get_dimensions($newwidth, $newheight, $option);
        $optimalwidth  = $optionarray['optimalwidth'];
        $optimalheight = $optionarray['optimalheight'];
        
        $this->imageresized = imagecreatetruecolor($optimalwidth, $optimalheight);
        if ($mimetype == 'image/png' || $mimetype == 'image/gif') {
            // Create a new destination image which is completely
            // transparent and turn off alpha blending for it, so that when
            // the PNG source file is copied, the alpha channel is retained
            // Thanks to http://alexle.net/archives/131
            $background = imagecolorallocate($this->imageresized, 0, 0, 0);
            imagecolortransparent($this->imageresized, $background);
            imagealphablending($this->imageresized, false);
            imagecopyresampled($this->imageresized, $this->image, 0, 0, 0, 0, $optimalwidth, $optimalheight, $this->width, $this->height);
            imagesavealpha($this->imageresized, true);
        }
        else {
            imagecopyresampled($this->imageresized, $this->image, 0, 0, 0, 0, $optimalwidth, $optimalheight, $this->width, $this->height);
        }
        
        if ($option == 'crop') {
            $this->crop($optimalwidth, $optimalheight, $newwidth, $newheight);
        }
        imagedestroy($this->image);
    }

    private function get_dimensions($newwidth, $newheight, $option){

        switch ($option){
            case 'exact':
                $optimalwidth = $newwidth;
                $optimalheight= $newheight;
                break;
            case 'portrait':
                $optimalwidth = $this->get_size_by_fixed_height($newheight);
                $optimalheight= $newheight;
                break;
            case 'landscape':
                $optimalwidth = $newwidth;
                $optimalheight= $this->get_size_by_fixed_width($newwidth);
                break;
            case 'auto':
                $optionarray = $this->get_size_by_auto($newwidth, $newheight);
                $optimalwidth = $optionarray['optimalwidth'];
                $optimalheight = $optionarray['optimalheight'];
                break;
            case 'crop':
                $optionarray = $this->get_optimal_crop($newwidth, $newheight);
                $optimalwidth = $optionarray['optimalwidth'];
                $optimalheight = $optionarray['optimalheight'];
                break;
        }
        
            
        if ($option == "auto" && ($optimalheight > $newheight || $optimalwidth > $newwidth)){
            // Auto resize will not resize both dimensions within requested limits. Find errant one and resize by that dimension.
            if ($optimalheight > $newheight){
                $optionarray = $this->get_dimensions($newwidth, $newheight, 'portrait');
            } else {
                $optionarray = $this->get_dimensions($newwidth, $newheight, 'landscape');
            }
            $optimalwidth  = $optionarray['optimalwidth'];
            $optimalheight = $optionarray['optimalheight'];
        }
        
        return array('optimalwidth' => $optimalwidth, 'optimalheight' => $optimalheight);
    }

    private function get_size_by_fixed_height($newheight){
        $ratio = $this->width / $this->height;
        $newwidth = $newheight * $ratio;
        return $newwidth;
    }

    private function get_size_by_fixed_width($newwidth){
        $ratio = $this->height / $this->width;
        $newheight = $newwidth * $ratio;
        return $newheight;
    }

    private function get_size_by_auto($newwidth, $newheight){
        if ($this->height < $this->width){
            // Image to be resized is wider (landscape)
            $optimalwidth = $newwidth;
            $optimalheight= $this->get_size_by_fixed_width($newwidth);
        }
        elseif ($this->height > $this->width){
            // Image to be resized is taller (portrait)
            $optimalwidth = $this->get_size_by_fixed_height($newheight);
            $optimalheight= $newheight;
        } else {
            // Image to be resized is a square
            if ($newheight < $newwidth) {
                $optimalwidth = $newwidth;
                $optimalheight= $this->get_size_by_fixed_width($newwidth);
            } else if ($newheight > $newwidth) {
                $optimalwidth = $this->get_size_by_fixed_height($newheight);
                $optimalheight= $newheight;
            } else {
                // Square being resized to a square
                $optimalwidth = $newwidth;
                $optimalheight= $newheight;
            }
        }

        return array('optimalwidth' => $optimalwidth, 'optimalheight' => $optimalheight);
    }

    private function get_optimal_crop($newwidth, $newheight){

        $heightratio = $this->height / $newheight;
        $widthRatio  = $this->width /  $newwidth;

        if ($heightratio < $widthRatio) {
            $optimalratio = $heightratio;
        } else {
            $optimalratio = $widthRatio;
        }

        $optimalheight = $this->height / $optimalratio;
        $optimalwidth  = $this->width  / $optimalratio;

        return array('optimalwidth' => $optimalwidth, 'optimalheight' => $optimalheight);
    }

    private function crop($optimalwidth, $optimalheight, $newwidth, $newheight){
        // Find center - this will be used for the crop
        $cropstartx = ( $optimalwidth / 2) - ( $newwidth /2 );
        $cropstarty = ( $optimalheight/ 2) - ( $newheight/2 );

        $crop = $this->imageresized;
        // imagedestroy($this->imageresized);

        // Now crop from center to exact requested size
        $this->imageresized = imagecreatetruecolor($newwidth , $newheight);
        imagecopyresampled($this->imageresized, $crop , 0, 0, $cropstartx, $cropstarty, $newwidth, $newheight , $newwidth, $newheight);
    }

    public function save_image($savepath, $mimetype, $imagequality="100") {
        $saved = false;
        if ($this->imageresized){
            switch ($mimetype) {
                case 'image/jpg':
                case 'image/jpeg':
                    if (imagetypes() & IMG_JPG) {
                        $saved = imagejpeg($this->imageresized, $savepath, $imagequality);
                    }
                break;
                case 'image/gif':
                    if (imagetypes() & IMG_GIF) {
                        $saved = imagegif($this->imageresized, $savepath);
                    }
                break;
                case 'image/png':
                    // Scale quality from 0-100 to 0-9
                    $scalequality = round(($imagequality/100) * 9);    
                    // Invert quality setting as 0 is best, not 9
                    $invertscalequality = 9 - $scalequality; 
                    if (imagetypes() & IMG_PNG) {
                        $saved = imagepng($this->imageresized, $savepath, $invertscalequality);
                    }
                break;
                default:
                // No extension - No save.
                break;
            }
            imagedestroy($this->imageresized);
        }
        return $saved;
    }

    private function set_memory_for_image($filename){
        // See http://uk3.php.net/manual/en/function.imagecreatefromjpeg.php#64155
        $imageinfo = getimagesize($filename);
        $mimetype = $imageinfo['mime'];
        if (isset($imageinfo['bits'])) {
            $bits = $imageinfo['bits'];
        }
        else if ($mimetype == 'image/gif') {
            $bits = 8;
        }
        if (isset($imageinfo['channels'])) {
            $channels = $imageinfo['channels'];
        }
        else {
            // possible vals are 3 or 4
            $channels = 4;
        }

        if (isset($imageinfo[0]) && isset($imageinfo[1]) && !empty($bits)) {
            $MB = 1048576;  // number of bytes in 1M
            $K64 = 65536;   // number of bytes in 64K
            $TWEAKFACTOR = 1.8;
            $memoryneeded = round(( $imageinfo[0] * $imageinfo[1]
                                                  * $bits
                                                  * $channels / 8
                                    + $K64
                                  ) * $TWEAKFACTOR
                                 );

            if ($memoryneeded > get_config('maximageresizememory')) {
                log_debug("Refusing to set memory for resize of large image $filename $mimetype "
                . $imageinfo[0] . 'x' .  $imageinfo[1] . ' ' . $imageinfo['bits'] . '-bit');
                return false;
            }
        }

        if (function_exists('memory_get_usage') && memory_get_usage() && !empty($memoryneeded)) {
            $newlimit = memory_get_usage() + $memoryneeded;
            if ($newlimit > get_config('maximageresizememory')) {
                log_debug("Refusing to set memory for resize of large image $filename $mimetype "
                . $imageinfo[0] . 'x' .  $imageinfo[1] . ' ' . $imageinfo['bits'] . '-bit');
                return false;
            }
            $newlimitMB = ceil((memory_get_usage() + $memoryneeded) / $MB);
            raise_memory_limit($newlimitMB . 'M');
            return true;
        }
        else {
            return false;
        }
    }

}
