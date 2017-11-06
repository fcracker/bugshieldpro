$(function () {
    Calendar.setup({inputField: "from_date",ifFormat: "%Y-%m-%d",showsTime: false,button: "from_date_trigger"});
    Calendar.setup({inputField: "to_date",ifFormat: "%Y-%m-%d",showsTime: false,button: "to_date_trigger"});

    $(".adjust").each(function () {
        $(this).click(function (evt) {
            evt.preventDefault();
            $(this).hide().next("div").show();
        });
    });

    $(".save").each(function () {
        $(this).click(function (evt) {
            evt.preventDefault();

            if ($(this).text() == "Ship") {
                $(this).text("Sure?");
            } else {
                var aff_id = $(this).attr("rel");
                if(parseInt($("#adjust_" + aff_id).val()) <= 0 ){
                    $(".error" + aff_id).fadeIn();
                    setTimeout(function(){
                        $(".error" + aff_id).fadeOut();
                    },3000);
                    return;
                };
                var value = $("#adjust_" + aff_id).val();
                var from_date = $('#from_date').val();
                var to_date = $('#to_date').val();

                $.post(
                    "ajax_handler.php",
                    {
                        action: 'adjust_affiliates',
                        aff_id: aff_id,
                        value: value,
                        from_date: from_date,
                        to_date: to_date
                    },
                    function (data) {
                        if (data.status == 'OK') {
                            window.location.reload();
                        } else {
                            alert(data.html);
                        }
                    },
                    "json"
                );

            }

        });
    });

    $(".adjust_cancel").each(function () {
        $(this).click(function (evt) {
            evt.preventDefault();
            $(this).parent("div").hide().prev("a").show();
        });
    });
});