function skillshare_success(form, data) {
    var displaynode = $j("#statementform_" + data.update + "display_container td");
    displaynode.html(data.content);
    skillshare_init();
    formSuccess(form, data);
}

function skillshare_error(form, data) {
    skillshare_init();
    var errornodeid = $j("#statementform textarea.error.wysiwyg").attr("id");
    if (errornodeid) {
        var editbutton = $j("input#" + errornodeid + "edit");
        if (editbutton) {
            editbutton.click();
        }
    }
}

function make_rows_sortable() {
    // requires jquery-ui
    $j(".sortable" ).sortable({
        handle: '.divrowmove',
        placeholder: 'ui-state-highlight',
        stop: function(event, ui) {
            var data = {'action':'updateorder'};
            $j('.divrowscontainerbody .divrowdelete input').each(function(index) {
                var id = $j(this).attr('name').replace (/[^\d]/g, "");
                data['order'+index] = id;
            });
            sendjsonrequest('skillshareimages.json.php', data, 'POST', function (response) {
                // display default image message
            });
        }
    });
}

function skillshare_init() {
    make_rows_sortable();
    $j('#skillshareinformation_godirectory').click(function(){
        window.location = config.wwwroot + 'artefact/browseskillshare';
    });
}
