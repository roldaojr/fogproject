$(function() {
    checkboxToggleSearchListPages();
    validatorOpts = {
        submitHandler: submithandlerfunc,
        rules: {
            name: {
                required: true,
                minlength: 1,
                maxlength: 255
            },
            subnets: {
                required: true,
                regex: /^([0-9]{1,3}\.){3}[0-9]{1,3}\/([0-9]|[1-2][0-9]|3[0-2])(\s*,\s*([0-9]{1,3}\.){3}[0-9]{1,3}\/([0-9]|[1-2][0-9]|3[0-2]))*$/g
            },
            storagegroup: {
                required: true
            }
        }
    };
    setupTimeoutElement('#add, #update', '.subnetstorage-input, #storagegroup', 1000);
    $('.action-boxes').on('submit',function() {
        var checked = $('input.toggle-action:checked');
        var subnetstorageIDArray = new Array();
        for (var i = 0,len = checked.size();i < len;i++) {
            subnetstorageIDArray[subnetstorageIDArray.length] = checked.eq(i).attr('value');
        }
        $('input[name="subnetstorageIDArray"]').val(subnetstorageIDArray.join(','));
    });
});
