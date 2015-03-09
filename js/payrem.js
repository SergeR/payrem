$(function () {
    $.payrem = {
        init: function () {
            $("#s-plugin-payrem-test-btn").unbind('click').click(function () {
                $("#s-plugin-payrem-sendtest-result").html('<i class="icon16 loading"></i> ' + 'Отправка…');
                $("#s-plugin-payrem-test-btn").prop("disabled", true);
                $.post(
                    "?plugin=payrem&action=testsend",
                    $("input,select", "#s-plugin-payrem-sendtest").serialize(),
                    function (r) {
                        $("#s-plugin-payrem-sendtest-result").removeClass('success error');
                        if (r.status == 'ok') {
                            $("#s-plugin-payrem-sendtest-result").addClass('success').html('<i class="icon16 yes"></i> ' + r.data);
                        }
                        if (r.status == 'fail') {
                            $("#s-plugin-payrem-sendtest-result").addClass('error').html('<i class="icon16 no"></i> ' + r.errors[0][0]);
                        }

                    },
                    'json'
                ).always(function () {
                        $("#s-plugin-payrem-test-btn").removeProp('disabled');
                        setTimeout(function () {
                            $("#s-plugin-payrem-sendtest-result").removeClass('success error').html('');
                        }, 9000);
                    })
            })
        }
    };

    $.payrem.init();
});
