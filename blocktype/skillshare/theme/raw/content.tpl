<div class="blocktype_skillshare">
{if $images}
    {foreach from=$images item=image}
        <a href="{$image.link}" target="_blank"><img src="{$image.source}" alt="{$image.title}" title="{$image.title}"/></a>
    {/foreach}
{/if}

{if $statement}<h3>{$statementtitle}</h3>
{$statement|safe}{/if}
    <ul>
    {if $wanted}<li><strong>{str tag=wanted section=artefact.skillshare}</strong></li>{/if}
    
    {if $offered}<li><strong>{str tag=offered section=artefact.skillshare}</strong></li>{/if}
    
    {if $externalwebsite}<li><strong>{str tag=externalwebsite section=artefact.skillshare}:</strong>
    {$externalwebsite|safe}</li>{/if}
    
    
    {if $externalwebsiterole}<li><strong>{str tag=externalwebsiterole section=artefact.skillshare}:</strong>
    {$externalwebsiterole|safe}</li>{/if}
    
    </ul>
</div>