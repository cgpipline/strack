$(function () {
    obj = {
        base_delete: function (i) {
            var data = {
                param : {
                    module_code: Strack.G.myScheduleDeleteTaskData['module_code'],
                    module_id: Strack.G.myScheduleDeleteTaskData['module_id'],
                    module_type: Strack.G.myScheduleDeleteTaskData['module_type'],
                    project_id: Strack.G.myScheduleDeleteTaskData['project_id']
                },
                primary_ids: Strack.G.myScheduleDeleteTaskData.item_id
            };

            $.messager.confirm(StrackLang['Confirmation_Box'], StrackLang['Delete_Base_Notice'], function (flag) {
                if (flag) {
                    $.ajax({
                        type: 'POST',
                        url: StrackPHP['deleteGridData'],
                        data: JSON.stringify(data),
                        dataType: 'json',
                        contentType: 'application/json',
                        beforeSend: function () {
                            $.messager.progress({title: StrackLang['Waiting'], msg: StrackLang['loading']});
                        },
                        success: function (data) {
                            $.messager.progress('close');
                            if (parseInt(data['status']) === 200) {
                                Strack.top_message({bg: 'g', msg: data['message']});
                                $('#scheduler_my_index').fullCalendar('refetchEvents');
                            } else {
                                layer.msg(data["message"], {icon: 7, time: 1200, anim: 6});
                            }
                        }
                    });
                }
            });
        }
    };

    var param = Strack.generate_hidden_param();

    Strack.init_schedule_panel('scheduler_my_index', 40, param);
});
