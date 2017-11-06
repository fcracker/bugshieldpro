$(function () {

    $("#sel_all").change(function () {

        if ($(this).attr("checked")) {
            $(".orderid").each(function () {
                $(this).attr("checked", "checked");
            });
        } else {
            $(".orderid").each(function () {
                $(this).removeAttr("checked");
            });
        }

    });


    $(".tracking_no").each(function () {
        $(this).click(function (evt) {
            evt.preventDefault();
            $(this).hide().next("div").show();
        });
    });

    $(".tracking_no_save").each(function () {
        $(this).click(function (evt) {
            evt.preventDefault();
            var value = $("#tracking_no_" + $(this).attr("rel")).val();
            var me = $(this);
            $.post(
                    "ajax_handler.php",
                    {action: 'save_tracking_no', order: $(this).attr("rel"), tracking_no: value},
            function (data) {
                me.parent("div").hide().prev("a").show().text(data);
            }
            );
        });
    });

    $(".tracking_no_cancel").each(function () {
        $(this).click(function (evt) {
            evt.preventDefault();
            $(this).parent("div").hide().prev("a").show();
        });
    });


    $(".ship_order").each(function () {
        $(this).click(function (evt) {
            evt.preventDefault();
            $(this).hide().next("div").show();

            set_up_cal("ship_" + $(this).attr("rel"));

        });
    });

    $(".ship_save").each(function () {
        $(this).click(function (evt) {
            evt.preventDefault();

            if ($(this).text() == "Ship") {
                $(this).text("Sure?");
            } else {

                var oid = $(this).attr("rel");
                var me = $(this);
                var ship_date = $("#ship_" + oid).val();

                $.post(
                        "ajax_handler.php",
                        {action: 'ship_order', order: oid, date: ship_date},
                function (data) {
                    if (data.status == 'OK') {
                        me.parents("tr").fadeOut("slow", function () {
                            $(this).remove()
                        });
                    } else {
                        alert(data.error);
                    }
                },
                        "json"
                        );

            }

        });
    });

    $(".ship_cancel").each(function () {
        $(this).click(function (evt) {
            evt.preventDefault();
            $(this).parent("div").hide().prev("a").show();
        });
    });

    $(".unship_order").each(function () {
        $(this).click(function (evt) {
            evt.preventDefault();

            if (confirm('Are you sure you want to un-ship this order ?')) {

                var oid = $(this).attr("rel");
                var me = $(this);

                $.post(
                        "ajax_handler.php",
                        {action: 'unship_order', order: oid},
                function (data) {
                    if (data.status == 'OK') {
                        me.parents("tr").fadeOut("slow", function () {
                            $(this).remove()
                        });
                    } else {
                        alert(data.error);
                    }
                },
                        "json"
                        );

            }

        });
    });

    $("#dialog_fraud").dialog({
        modal: true,
        autoOpen: false,
        resizable: false,
        hide: "hide",
        width: 800,
        height: 600,
        buttons: {
            "Close": function () {
                jQuery(this).dialog("close");
            }
        }
    });

    $(".fraud_info").each(function () {
        $(this).click(function (evt) {
            evt.preventDefault();
            var oid = $(this).attr("rel");
            var uid = $(this).attr("data-uid");
            var me = $(this);
            $.post(
                    "ajax_handler.php",
                    {action: 'fraud_info', order: oid, uid: uid},
                    function (data) {
                        if (data.status == 'OK') {
                            $("#dialog_fraud")
                                    .html(data.html)
                                    .dialog('option', 'title', 'Fraud Info for order #' + uid)
                                    .dialog("open");

                        } else {
                            alert(data.error);
                        }
                    },
                    "json"
                    );
        });
    });
    
    $(".flag_fraudulent").each(function () {
        $(this).click(function (evt) {
            evt.preventDefault();
            var oid = $(this).attr("rel");
            var uid = $(this).attr("data-uid");
            var flagFraudulent =+ $(this).attr("data-fraudulent");
            var newFlag = (flagFraudulent+1) % 2;
            var trElement = $(this).parents("tr");
            var aElement = $(this);            
            $.post(
                    "ajax_handler.php",
                    {action: 'flag_as_fraudulent', order: oid, uid: uid, fraudulent_flag:newFlag},
                    function (data) {
                        if (data.status == 'OK') {
                            if(newFlag == 1){
                                $(trElement).addClass("yellow-flag");
                                $(aElement).html("Unflag Fraudulent");
                                $(aElement).attr("data-fraudulent", newFlag);
                            }else{
                                $(trElement).removeClass("yellow-flag");
                                $(aElement).html("Flag as Fraudulent");
                                $(aElement).attr("data-fraudulent", newFlag);
                            }
                        } else {
                            alert(data.error);
                        }
                    },
                    "json"
                    );
        });
    });
    
    
    $(".bank_info").each(function () {
        $(this).click(function (evt) {
            evt.preventDefault();
            var oid = $(this).attr("rel");
            var uid = $(this).attr("data-uid");
            var me = $(this);
            $.post(
                    "ajax_handler.php",
                    {action: 'bank_info', order: oid, uid: uid},
                    function (data) {
                        if (data.status == 'OK') {
                            $("#dialog_fraud")
                                    .html(data.html)
                                    .dialog('option', 'title', 'Bank Info for order #' + uid)
                                    .dialog("open");

                        } else {
                            alert(data.error);
                        }
                    },
                    "json"
                    );
        });
    });
    
    $(".note_info").each(function () {
        $(this).click(function (evt) {
            evt.preventDefault();
            var oid = $(this).attr("rel");
            var uid = $(this).attr("data-uid");
            var me = $(this);
            $.post(
                    "ajax_handler.php",
                    {action: 'note_info', order: oid, uid: uid},
                    function (data) {
                        if (data.status == 'OK') {
                            $("#dialog_fraud")
                                    .html(data.html)
                                    .dialog('option', 'title', 'Bank Info for order #' + uid)
                                    .dialog("open");

                        } else {
                            alert(data.error);
                        }
                    },
                    "json"
                    );
        });
    });


    Calendar.setup({inputField: "from_date", ifFormat: "%Y-%m-%d", showsTime: false, button: "from_date_trigger"});
    Calendar.setup({inputField: "to_date", ifFormat: "%Y-%m-%d", showsTime: false, button: "to_date_trigger"});


});


function view_now() {

    $("#order_form").attr("action", "orders.php").submit();

}



function export_now() {

    $("#order_form").attr("action", "export.php").submit();
}

function export_nowv2() {

    $("#order_form").attr("action", "exportv2.php").submit();
}

function export_now_staging() {

    $("#order_form").attr("action", "export_staging.php").submit();
}

function export_now_ibs() {

    $("#order_form").attr("action", "export_ibs.php").submit();
}

function set_up_cal(id) {

    Calendar.setup(
            {
                inputField: id,
                ifFormat: "%Y-%m-%d",
                showsTime: false
            }
    );
}

function import_callback() {
    //scroll to bottom
    var $contents = $('#import_iframe').contents();
    $('#import_iframe').height($contents.height() + 30);
}