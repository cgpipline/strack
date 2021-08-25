$(function () {
    obj = {
        save_setting: function () {
            var formData = Strack.validate_form('save_setting');
            if(formData['status'] === 200){

                // 添加计算公式数据
                var formula_data = {
                    'formula_data' : {},
                    'fields' : {}
                };

                var formula_assignee_field = $('#formula_assignee_field').combobox('getValue');
                var formula_reviewed_by = $('#formula_reviewed_by').combobox('getValue');
                var formula_no_start_status = $('#formula_no_start_status').combobox('getValue');
                var formula_reviewed_by_status = $('#formula_reviewed_by_status').combobox('getValue');
                var formula_end_by_status = $('#formula_end_by_status').combobox('getValue');
                var formula_in_progress_status  = $('#formula_in_progress_status').combobox('getValue');

                var formula_actual_time_consuming = $('#formula_actual_time_consuming').combobox('getValue');

                var estimate_working_hours = $('#estimate_working_hours').combobox('getValue');
                var examine_working_hours = $('#examine_working_hours').combobox('getValue');

                var settlement_time_consuming = $('#settlement_time_consuming').combobox('getValue');

                var grouping_of_persons = $('#grouping_of_persons').combobox('getValue');
                var grouping_of_stage = $('#grouping_of_stage').combobox('getValue');

                var formula_sub_task = $('#sub_task').combobox('getValue');


                formula_data['fields']['assignee_field'] = formula_assignee_field;
                formula_data['fields']['reviewed_by'] = formula_reviewed_by;
                formula_data['fields']['reviewed_by_status'] = formula_reviewed_by_status;
                formula_data['fields']['no_start_status'] = formula_no_start_status;
                formula_data['fields']['end_by_status'] = formula_end_by_status;
                formula_data['fields']['in_progress_status'] = formula_in_progress_status;
                formula_data['fields']['actual_time_consuming'] = formula_actual_time_consuming;
                formula_data['fields']['estimate_working_hours'] = estimate_working_hours;
                formula_data['fields']['grouping_of_stage'] = grouping_of_stage;
                formula_data['fields']['examine_working_hours'] = examine_working_hours;

                formula_data['fields']['sub_task'] = formula_sub_task;
                formula_data['fields']['settlement_time_consuming'] = settlement_time_consuming;

                formData['data']['view'] = {};
                formData['data']['view']['grouping_of_persons'] = grouping_of_persons;
                formData['data']['view']['grouping_of_stage'] = grouping_of_stage;

                formData['data']['formula'] = formula_data;

                $.ajax({
                    type : 'POST',
                    url : FieldsSettingsPHP['updateFieldSettings'],
                    contentType: 'application/json',
                    data : JSON.stringify(formData['data']),
                    dataType : 'json',
                    beforeSend : function () {
                        $.messager.progress({ title:StrackLang['Waiting'], msg:StrackLang['loading']});
                    },
                    success : function (data) {
                        $.messager.progress('close');
                        if(parseInt(data['status']) === 200){
                            Strack.top_message({bg:'g',msg: data['message']});
                        }else {
                            layer.msg(data["message"], {icon: 7, time: 1200, anim: 6});
                        }
                    }
                });
            }
        }
    };


    //ajax获取默认设置参数
    $.ajax({
        type: 'POST',
        url: FieldsSettingsPHP['getFieldSettings'],
        dataType: 'json',
        success: function (data) {

            var assignee_field = '',
                reviewed_by = '',
                no_start_status = '',
                reviewed_by_status = '',
                end_by_status = '',
                in_progress_status = '',
                actual_time_consuming = '',
                estimate_working_hours = '',
                examine_working_hours = '',
                grade_time_consuming = '',
                settlement_time_consuming = '',
                grouping_of_persons = '',
                grouping_of_stage = '',
                sub_task = '' ;


            if(data.hasOwnProperty('formula')){
                assignee_field = data['formula']['fields']['assignee_field'];
                reviewed_by = data['formula']['fields']['reviewed_by'];
                no_start_status = data['formula']['fields']['no_start_status'];
                reviewed_by_status = data['formula']['fields']['reviewed_by_status'];
                in_progress_status = data['formula']['fields']['in_progress_status'];
                end_by_status = data['formula']['fields']['end_by_status'];
                actual_time_consuming = data['formula']['fields']['actual_time_consuming'];
                estimate_working_hours = data['formula']['fields']['estimate_working_hours'];
                examine_working_hours = data['formula']['fields']['examine_working_hours'];
                grade_time_consuming = data['formula']['fields']['grade_time_consuming'];
                settlement_time_consuming = data['formula']['fields']['settlement_time_consuming'];
                sub_task = data['formula']['fields']['sub_task'];
            }

            if(data.hasOwnProperty('view')) {
                grouping_of_persons = data['view']['grouping_of_persons'];
                grouping_of_stage = data['view']['grouping_of_stage'];
            }

            // 审核
            $('#formula_reviewed_by').combobox({
                url: FieldsSettingsPHP['getCustomFieldList'],
                height: 30,
                width: 170,
                valueField: 'id',
                textField: 'name',
                value: reviewed_by
            });

            // 审核
            $('#formula_reviewed_by_status').combobox({
                url: FieldsSettingsPHP['getStatusList'],
                height: 30,
                width: 170,
                valueField: 'id',
                textField: 'name',
                value: reviewed_by_status
            });

            // 审核
            $('#formula_no_start_status').combobox({
                url: FieldsSettingsPHP['getStatusList'],
                height: 30,
                width: 170,
                valueField: 'id',
                textField: 'name',
                value: no_start_status
            });

            // 审核
            $('#formula_in_progress_status').combobox({
                url: FieldsSettingsPHP['getStatusList'],
                height: 30,
                width: 170,
                valueField: 'id',
                textField: 'name',
                value: in_progress_status
            });

            // 审核
            $('#formula_end_by_status').combobox({
                url: FieldsSettingsPHP['getStatusList'],
                height: 30,
                width: 170,
                valueField: 'id',
                textField: 'name',
                value: end_by_status
            });

            //结算工时
            $('#grouping_of_persons').combobox({
                url: FieldsSettingsPHP['getCustomFieldList'],
                height: 30,
                width: 170,
                valueField: 'id',
                textField: 'name',
                value: grouping_of_persons
            });

            //结算工时
            $('#grouping_of_stage').combobox({
                url: FieldsSettingsPHP['getCustomFieldList'],
                height: 30,
                width: 170,
                valueField: 'id',
                textField: 'name',
                value: grouping_of_stage
            });

            //结算工时
            $('#settlement_time_consuming').combobox({
                url: FieldsSettingsPHP['getCustomFieldList'],
                height: 30,
                width: 170,
                valueField: 'id',
                textField: 'name',
                value: settlement_time_consuming
            });

            // 审核
            $('#formula_actual_time_consuming').combobox({
                url: FieldsSettingsPHP['getCustomFieldList'],
                height: 30,
                width: 170,
                valueField: 'id',
                textField: 'name',
                value: actual_time_consuming
            });

            $('#estimate_working_hours').combobox({
                url: FieldsSettingsPHP['getCustomFieldList'],
                height: 30,
                width: 170,
                valueField: 'id',
                textField: 'name',
                value: estimate_working_hours
            });

            $('#examine_working_hours').combobox({
                url: FieldsSettingsPHP['getCustomFieldList'],
                height: 30,
                width: 170,
                valueField: 'id',
                textField: 'name',
                value: examine_working_hours
            });

            $('#sub_task').combobox({
                url: FieldsSettingsPHP['getCustomFieldList'],
                height: 30,
                width: 170,
                valueField: 'id',
                textField: 'name',
                value: sub_task
            });


            $('#formula_assignee_field').combobox({
                url: FieldsSettingsPHP['getCustomFieldList'],
                height: 30,
                width: 170,
                valueField: 'id',
                textField: 'name',
                value: assignee_field
            });

            $('#formula_grade_time_consuming').combobox({
                url: FieldsSettingsPHP['getCustomFieldList'],
                height: 30,
                width: 170,
                valueField: 'id',
                textField: 'name',
                value: grade_time_consuming
            });
        }
    });
});
