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
 * @author     Mike Kelly UAL m.f.kelly@arts.ac.uk, Catalyst IT Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2006-2009 Catalyst IT Ltd http://catalyst.net.nz
 *
 */

define('INTERNAL', true);
define('MENUITEM', 'content/skillshare');
define('SECTION_PLUGINTYPE', 'artefact');
define('SECTION_PLUGINNAME', 'skillshare');
define('SECTION_PAGE', 'index');

require_once(dirname(dirname(dirname(__FILE__))) . '/init.php');
define('TITLE', get_string('skillshare', 'artefact.skillshare'));
require_once('pieforms/pieform.php');
safe_require('artefact', 'skillshare');

$filesize = 0;
$imagemaxwidth  = 600; // TODO set this as a plugin config option
$imagemaxheight = 400;

// borrowed from Profile Pictures
$imagesettingsform = new Pieform(array(
    'name'      => 'imagesettings',
    'renderer'  => 'oneline',
    'autofocus' => false,
    'presubmitcallback' => '',
    'elements' => array(
        'default' => array(
            'type'  => 'submit',
            'value' => get_string('default', 'artefact.skillshare'),
        ),
        'delete' => array(
            'type'  => 'submit', 
            'value' => get_string('delete', 'artefact.skillshare'),
        ),
        'unsetdefault' => array(
            'type' => 'submit',
            'value' => get_string('usenodefault', 'artefact.skillshare'),
        ),
    )
));

// borrowed from Profile Pictures
$imageuploadform = pieform(array(
    'name'   => 'imageupload',
    'jsform' => true,
    'presubmitcallback'  => 'preSubmit',
    'postsubmitcallback' => 'postSubmit',
    'plugintype' => 'artefact',
    'pluginname' => 'file',
    'elements' => array(
        'file' => array(
            'type' => 'file',
            'title' => get_string('skillshareexampleimage', 'artefact.skillshare'),
            'rules' => array('required' => true),
            'maxfilesize'  => get_max_upload_size(false),
        ),
        'title' => array(
            'type' => 'text',
            'title' => get_string('exampleimageyourrole', 'artefact.skillshare'),
            'size' => 80,
            'rules' => array('maxlength' => 100, 'required' => true),
        ),
        'submit' => array(
            'type' => 'submit',
            'value' => get_string('upload')
        )
    )
));

$strnoimagesfound = json_encode(get_string('noimagesfound', 'artefact.skillshare'));
$struploadingfile = json_encode(get_string('uploadingfile', 'artefact.skillshare'));
$wwwroot = get_config('wwwroot');
$moveicon = $THEME->get_url('images/move_vert_icon.png', null, 'artefact/skillshare');
$IJS = <<<EOF
var divrows = new DivRenderer(
    'skillshareimages', //target
    'skillshareimages.json.php', // source 
    [
        function(rowdata) {
            return DIV({'class': 'divrow divrowimg'}, null, IMG({'src': '{$wwwroot}artefact/skillshare/image.php?type=skillshareimage&maxsize=100&id=' + rowdata.id, 'alt': rowdata.note}));
        },
        function(rowdata) {
            return DIV({'class': 'divrow divrowrole'}, rowdata.description);
        },
        function(rowdata) {
            return DIV({'class': 'divrow divrowdelete'}, INPUT({'type': 'checkbox', 'name': 'imgs[' + rowdata.id + ']'}));
        },
        function(rowdata) {
            return DIV({'class': 'divrow divrowmove'}, null, IMG({'src': '{$moveicon}'}));
        }
    ] // columns
);
divrows.updateOnLoad();
divrows.emptycontent = {$strnoimagesfound};
divrows.paginate = false;
divrows.updatecallback = function(response) {
};

var uploadingMessage = TR(null, TD(null, {$struploadingfile}));

function preSubmit(form, data) {
    formStartProcessing(form, data);
    insertSiblingNodesAfter($('imageupload_submit_container'), uploadingMessage);
}

function postSubmit(form, data) {
    removeElement(uploadingMessage);
    divrows.doupdate();
    formStopProcessing(form, data);
    quotaUpdate();
    $(form).reset();
    $('imageupload_title').value = '';
}

EOF;

function imageupload_validate(Pieform $form, $values) {
    global $USER, $filesize, $imagemaxwidth, $imagemaxheight;
    require_once('file.php');
    require_once('uploadmanager.php');

    $um = new upload_manager('file');
    if ($error = $um->preprocess_file()) {
        $form->set_error('file', $error);
        return false;
    }

    $imageinfo = getimagesize($values['file']['tmp_name']);
    if (!$imageinfo || !is_image_type($imageinfo[2])) {
        $form->set_error('file', get_string('filenotimage'));
        return false;
    }

    if (get_field('artefact', 'COUNT(*)', 'artefacttype', 'skillshareimage', 'owner', $USER->get('id')) >= 4) {
        $form->set_error('file', get_string('onlyfourskillshareimages', 'artefact.skillshare'));
        return false;
    }

    // resize image if necessary
    $width    = $imageinfo[0];
    $height    = $imageinfo[1];
    $saveresize = false;

    if ($width > $imagemaxwidth || $height > $imagemaxheight) {
        $imageinfo = getimagesize($values['file']['tmp_name']);
        $mimetype = $imageinfo['mime'];
        $imgman = new ImageManipulator($values['file']['tmp_name'], $mimetype);
        if ($imgman->get_image()) {
            $imgman->resize_image($imagemaxwidth, $imagemaxheight, $mimetype); //auto
            $saveresize = $imgman->save_image($values['file']['tmp_name'], $mimetype, 85);
        }
        if (!$saveresize){
            $form->set_error('file', get_string('problemresizing', 'artefact.skillshare'));
            return false;
        }
        
        $resizedimageinfo = getimagesize($values['file']['tmp_name']);
        $width = $resizedimageinfo[0];
        $height = $resizedimageinfo[1];
        $um->file['size'] = filesize($values['file']['tmp_name']);
    }

    $filesize = $um->file['size'];
    if (!$USER->quota_allowed($filesize)) {
        $form->set_error('file', get_string('skillshareimageuploadexceedsquota', 'artefact.skillshare', get_config('wwwroot')));
        return false;
    }

    // Check the file isn't greater than the max allowable size
    // Fallback in case file resize didn't work
    if ($width > $imagemaxwidth || $height > $imagemaxheight) {
        $form->set_error('file', get_string('skillshareimagetoobig', 'artefact.skillshare', $width, $height, $imagemaxwidth, $imagemaxheight));
    }
}

function imageupload_submit(Pieform $form, $values) {
    global $USER, $filesize;
    safe_require('artefact', 'skillshare');

    try {
        $USER->quota_add($filesize);
    }
    catch (QuotaException $qe) {
        $form->json_reply(PIEFORM_ERR, array(
            'message' => get_string('skillshareimageuploadexceedsquota', 'artefact.skillshare', get_config('wwwroot'))
        ));
    }

    $data = (object) array(
        'owner'    => $USER->get('id'),
        'title'    => $values['file']['name'],
        'description'  => $values['title'],
        'size'     => $filesize,
    );
    $imageinfo = getimagesize($values['file']['tmp_name']);
    $data->width    = $imageinfo[0];
    $data->height   = $imageinfo[1];
    $data->filetype = $imageinfo['mime'];
    $artefact = new ArtefactTypeSkillshareImage(0, $data);
    if (preg_match("/\.([^\.]+)$/", $values['file']['name'], $saved)) {
        $artefact->set('oldextension', $saved[1]);
    }
    $artefact->commit();

    $id = $artefact->get('id');

    // Move the file into the correct place.
    $directory = get_config('dataroot') . 'artefact/file/skillshareimages/originals/' . ($id % 256) . '/';
    check_dir_exists($directory);
    move_uploaded_file($values['file']['tmp_name'], $directory . $id);

    $form->json_reply(PIEFORM_OK, get_string('uploadedexampleimagesuccessfully', 'artefact.skillshare'));
}

function imagesettings_submit_delete(Pieform $form, $values) {
    require_once('file.php');
    global $USER, $SESSION;

    $imgs = param_variable('imgs', array());
    $imgs = array_keys($imgs);

    if ($imgs) {
        db_begin();
        
        foreach ($imgs as $img) {
            $imgartefact = artefact_instance_from_id($img);
            // Just to be sure
            if ($imgartefact->get('artefacttype') == 'skillshareimage' && $imgartefact->get('owner') == $USER->get('id')) {
                $imgartefact->delete();
            }
            else {
                throw new AccessDeniedException();
            }
        }
        
        db_commit();

        $SESSION->add_ok_msg(
            get_string('filethingdeleted', 'artefact.skillshare', get_string('nskillshareimages', 'artefact.skillshareimage', count($imgs)))
        );
    }
    else {
        $SESSION->add_info_msg(get_string('skillshareimagesnoneselected', 'artefact.skillshare'));
    }

    redirect('/artefact/skillshare/index.php');
}

$maxchars = 200;
global $USER;
$skillshareinformation = get_record_sql('
     SELECT *
    FROM {artefact_skillshare} s 
    INNER JOIN {artefact} a 
    ON s.artefact = a.id
    WHERE a.owner = ?', array( $USER->get('id'))
);

$tags = array();
if ($skillshareinformation){
    $where = 'artefact = ?';
    $tagsarray = get_records_select_array('artefact_tag', $where, array($skillshareinformation->artefact));
    if ($tagsarray) {
        foreach ($tagsarray as $t) {
            $tags[] = $t->tag;
        }
    }
}
$skillshareinformationform = pieform(array(
    'name'        => 'skillshareinformation',
    'jsform'      => true,
    'plugintype'  => 'artefact',
    'pluginname'  => 'skillshare',
    'jsform'      => true,
    'successcallback'   => 'skillshareinformation_submit',
    'jssuccesscallback' => 'skillshare_success',
    'jserrorcallback'   => 'skillshare_error',
    'method'      => 'post',
    /*'renderer'     => 'div',*/
    'elements'    => array(
                    'statementtitle' => array(
                        'type'       => 'text',
                        'title' => get_string('statementtitle', 'artefact.skillshare'),
                        'description' => get_string('statementtitledescription', 'artefact.skillshare'),
                        'defaultvalue' => (isset($skillshareinformation->statementtitle)) ? $skillshareinformation->statementtitle :  '',
                        'size' => 80,
                        'rules' => array('maxlength' => 250, 'required' => true),
                    ),    
                    'statement' => array(
                        'title'=> get_string('statement', 'artefact.skillshare'),
                        'type' => 'wysiwyg',
                        'rows' => 5,
                        'cols' => 60,
                        'defaultvalue' => (isset($skillshareinformation->statement)) ? $skillshareinformation->statement : '',
                        'description' => get_string('statementdescription', 'artefact.skillshare'),
                        'rules' => array('maxlength' => 65536, 'required' => true),
                    ),
                    'tags'       => array(
                        'defaultvalue' => $tags,
                        'type'         => 'tags',
                        'title'        => get_string('tags'),
                        'description'  => get_string('tagsdesc'),
                        'help' => true,
                    ),
                    'skillshareoffered' => array(
                         'type'         => 'checkbox',
                         'title'        => get_string('skillshareoffered', 'artefact.skillshare'),
                         'defaultvalue' => (isset($skillshareinformation->offered)) ? $skillshareinformation->offered : 0,
                         'description'  => get_string('skillshareoffereddescription', 'artefact.skillshare'),
                    ),
                    'skillsharewanted' => array(
                         'type'         => 'checkbox',
                         'title'        => get_string('skillsharewanted', 'artefact.skillshare'),
                         'defaultvalue' => (isset($skillshareinformation->wanted)) ? $skillshareinformation->wanted : 0,
                         'description'  => get_string('skillsharewanteddescription', 'artefact.skillshare'),
                    ),
                    'externalwebsite' => array(
                        'type'       => 'text',
                        'defaultvalue' => '', // fill with entry in Contact Information if exists
                        'title' => get_string('externalwebsite', 'artefact.skillshare'),
                        'description' => get_string('externalwebsitedescription', 'artefact.skillshare'),
                        'defaultvalue' => (isset($skillshareinformation->externalwebsite)) ? $skillshareinformation->externalwebsite :  '',
                        'size' => 80,
                        'rules' => array('maxlength' => 250),
                    ),
                    'externalwebsiterole' => array(
                        'type'       => 'text',
                        'defaultvalue' => null, // fill with entry in Contact Information if exists
                        'title' => get_string('externalwebsiterole', 'artefact.skillshare'),
                        'description' => get_string('externalwebsiteroledescription', 'artefact.skillshare'),
                        'defaultvalue' => (isset($skillshareinformation->externalwebsiterole)) ? $skillshareinformation->externalwebsiterole :  '',
                        'size' => 80,
                        'rules' => array('maxlength' => 100),
                    ),
                    'publishskills' => array(
                         'type'         => 'checkbox',
                         'title'        => get_string('publishskills', 'artefact.skillshare'),
                         'defaultvalue' => (isset($skillshareinformation->publishskills)) ? $skillshareinformation->publishskills : true,
                         'description' => get_string('publishskillsdescription', 'artefact.skillshare'),
                    ),
                    'save' => array(
                        'type'       => 'html',
                        'title' => '',
                        'value' => '<input type="submit" value="Save" tabindex="3" name="save" id="skillshareinformation_save" class="submit"> <input type="button" value="View Skillshare directory" tabindex="4" name="godirectory" id="skillshareinformation_godirectory" class="button">',
                    ),  
    ),
));

$smarty = smarty(array('jquery', 'artefact/skillshare/js/skillsharefields.js', 'artefact/skillshare/js/divrenderer.js', 'artefact/skillshare/js/jquery-ui-1.8.19.custom.min.js'));
$smarty->assign('INLINEJAVASCRIPT', '$j(skillshare_init);' . $IJS);
$smarty->assign('imageuploadform', $imageuploadform);
$smarty->assign('imagesettingsformtag', $imagesettingsform->get_form_tag());
$smarty->assign('imagemaxdimensions', array($imagemaxwidth, $imagemaxheight));
$smarty->assign('skillshareinformationform',$skillshareinformationform);
$smarty->assign('PAGEHEADING', TITLE);
$smarty->display('artefact:skillshare:index.tpl');

function skillshareinformation_submit(Pieform $form, $values) {
    global $USER;
    $userid = $USER->get('id');
    $errors = array();
    $usercollege = "";
    $usercourse = "";

    try {        
        $skillshareid = get_field('artefact', 'id', 'owner',  $USER->get('id'), 'artefacttype', 'skillshare');
        if (!$skillshareid){
            $skillshareid = 0;
        }
        
        if (strlen($values['externalwebsite']) && strpos($values['externalwebsite'], '://') == false) {
            $values['externalwebsite'] = 'http://' . $values['externalwebsite'];
        }
        
        $skillshareinformation = new ArtefactTypeSkillshare($skillshareid, array(
            'owner' => $userid,
            'statementtitle'      => $values['statementtitle'], 
            'offered'             => $values['skillshareoffered'],
            'wanted'              => $values['skillsharewanted'],
            'statement'           => $values['statement'],
            'externalwebsite'     => $values['externalwebsite'],
            'externalwebsiterole' => $values['externalwebsiterole'],
            'publishskills'       => $values['publishskills'],
        ));
        $skillshareinformation->set('tags', $values['tags']);
        $skillshareinformation->commit();
    }
    catch (Exception $e) {
        $errors['skillshareinformation'] = true;
    }

    if (empty($errors)) {
        $form->json_reply(PIEFORM_OK, get_string('skillsharesaved','artefact.skillshare'));
    }
    else {
        $message = '';
        foreach (array_keys($errors) as $key) {
            $message .= get_string('skillsharesavefailed', 'artefact.skillshare')."\n";
        }
        $form->json_reply(PIEFORM_ERR, $message);
    }
}
