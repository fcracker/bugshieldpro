$(function () {
    Calendar.setup({inputField: "from_date", ifFormat: "%Y-%m-%d", showsTime: false, button: "from_date_trigger"});
    Calendar.setup({inputField: "to_date", ifFormat: "%Y-%m-%d", showsTime: false, button: "to_date_trigger"});
    /**
     * unFalg Yello
     */
    $(".make_investigated").each(function () {
        $(this).click(function (evt) {
            evt.preventDefault();

            var affId = $(this).attr("rel");
            var from_date = $(this).attr("from_date");
            var to_date = $(this).attr("to_date");

            var me = $(this);
            $.post(
                    "ajax_handler.php",
                    {action: 'make_investigate_affid', affid: affId, from_date: from_date, to_date: to_date},
            function (data) {
                if (data.status == 'OK') {
                    $(me).hide();
                    $(me).parents("tr").removeClass("yellow-flag");
                    $(me).parents("tr").find(".separate").hide();
                } else {
                    alert(data.error);
                }
            },
                    "json"
                    );
        });
    });

    /**
     * Ignore red highlight
     */
    $(".highlight_check").each(function () {
        $(this).change(function (evt) {           

            var affId = $(this).attr("rel");
            var me = $(this), ignoreFlag;
            if($(me).attr("checked")){
                ignoreFlag = 1;
                $(me).parents("tr").removeClass("red-flag");
            }else{
                if($(me).parents("tr").attr("data-highlight") == "red"){
                    $(me).parents("tr").addClass("red-flag");
                }
                ignoreFlag = 0;
            }           
            
            $.post(
                    "ajax_handler.php",
                    {action: 'make_ignore_highlight', affid: affId, ignoreflag:ignoreFlag},
                    function (data) {
                        if (data.status == 'OK') {

                        } else {
                            alert(data.error);
                        }
                    },
                    "json"
                    );
        });
    });


    $(".notes a.edit_notes").each(function () {
        $(this).click(function (evt) {
            evt.preventDefault();
            var id = $(this).attr("rel");
            console.log(id);
            var notes_content = $("#notes_" + id).text();
            var center = $(this);
            $("#notes_" + id).hide();

            if ($("#notes_edit_" + id).length) {
                $("#notes_edit_" + id).show();
            } else {
                center.parent().append("<textarea style=\"min-width:300px;min-height: 50px;\" id='notes_edit_" + id + "'>" + notes_content + "</textarea>");
            }
            center.next("span").show();
            center.hide();
        });
    });

    $(".notes a.cancel_notes").each(function () {
        $(this).click(function (evt) {
            evt.preventDefault();
            var id = $(this).attr("rel");
            $("#notes_" + id).show();
            $("#notes_edit_" + id).hide();
            $(this).parent().hide().prev("a").show();
        });
    });



    $(".notes a.save_notes").each(function () {
        $(this).click(function (evt) {
            evt.preventDefault();
            var id = $(this).attr("rel");
            var new_text = $("#notes_edit_" + id).val();
            var center = $(this);
            $.post(
                    "ajax_handler.php",
                    {action: 'editnote_affid', affid: id, note:new_text},
                    function (data) {
                        $("#notes_" + id).show().text(new_text);
                        $("#notes_edit_" + id).hide();
                        center.parent().hide().prev("a").show();
                    }
            );
        });
    });
});

