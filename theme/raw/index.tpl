{include file="header.tpl"}
<p class="intro">{str tag="skillsharetitledescription" section="artefact.skillshare"}</p>
<div id="skillsharewrap">
        <h3>{str tag="skillshareinformation" section="artefact.skillshare"}</h3>
        {$skillshareinformationform|safe} 

        <h3>{str tag="uploadexampleimages" section="artefact.skillshare"}</h3>
        <p>{str tag="exampleimagesizenotice" section="artefact.skillshare" args=$imagemaxdimensions}</p>
        {$imageuploadform|safe}

        {$imagesettingsformtag|safe}
            <div id="skillshareimages">
                <div id="exampleimagesimages" class="columnheading divrowimg">{str tag="uploadedimage" section=artefact.skillshare}</div>
                <div id="exampleimagesrole" class="columnheading divrowrole">{str tag="imagerole" section=artefact.skillshare}</div>
                <div id="exampleimagesdelete" class="columnheading divrowdelete">{str tag="delete" section=artefact.skillshare}</div>
                <div id="exampleimagesrearrange" class="columnheading divrowrearrange hidden">{str tag="rearrange" section=artefact.skillshare}</div>
                <div class="divrowscontainerbody sortable"></div>
                <div id="exampleimagesfooter">
                    <div class="right"><input id="imagesettings_delete" type="submit" class="cancel" name="delete" value="{str tag="deleteselectedimages" section=artefact.skillshare}" tabindex="2"></div>
                </div>
            </div>

        <input type="hidden" name="pieform_imagesettings" value="">
        <input type="hidden" name="sesskey" value="{$USER->get('sesskey')}">
        </form>
</div>
{include file="footer.tpl"}
