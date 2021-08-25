$(function(){
    obj={
        dingtalk_update:function(){
            var formData = Strack.validate_form('ding_talk_setting');

            if(parseInt(formData['status']) === 200){

                // 处理网盘节点
                var endpoints = {};
                for(var key in formData['data']){
                    if(key.indexOf("endpoint_") != -1 ){
                        var key_arr= key.split('-');
                        if(!endpoints.hasOwnProperty(key_arr[0])){
                            endpoints[key_arr[0]] = {};
                        }

                        endpoints[key_arr[0]][key_arr[1]] = formData['data'][key];
                    }
                }

                if($.isEmptyObject(endpoints) === false){
                    $.ajax({
                        type : 'POST',
                        url : DingTalkPHP['updateDingTalkConfig'],
                        data : JSON.stringify(endpoints),
                        dataType : 'json',
                        contentType: "application/json",
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
        },
        // 增加云盘
        add_company_node: function (i) {
            var endpoint_item = endpoint_item_dom(rules_submit, {
                'node_name' : '',
                'corp_id' : '',
                'app_key': '',
                'app_secret': '',
                'control_department': ''
            });

            $('#cloud_disk_list').append(endpoint_item['dom']);

            // 初始化
            init_department_list('endpoint_val_control_department_'+endpoint_item['random_id']);
        },
        // 删除云盘
        delete_dingtalk_item: function (i) {
            var $cloud_parent = $(i).closest('.cloud-e-item');
            $cloud_parent.remove();

            obj.dingtalk_update();
        }
    };

    var rules_submit = $('#rules_submit').val();

    //ajax获取默认设置参数
    $.ajax({
        type: 'POST',
        url: DingTalkPHP['getDingTalkConfig'],
        dataType: 'json',
        success: function (data) {
            if($.isEmptyObject(data) === false){
                var temp_dom = '';
                for(var key in data ){
                    temp_dom = endpoint_item_dom(rules_submit, data[key]);
                    $('#cloud_disk_list').append(temp_dom['dom']);
                    init_department_list('endpoint_val_control_department_'+temp_dom['random_id']);
                }
            }
        }
    });


    /**
     * 节点dom
     * @param rules_submit
     * @param data
     * @returns {string}
     */
    function endpoint_item_dom(rules_submit, data) {
        var dom = '';
        var random_id = Math.floor(Math.random() * 10000 + 1);

        dom += '<div class="cloud-e-item">';

        if(rules_submit === 'yes') {
            dom += '<a href="javascript:;" class="e-del-bnt" onclick="obj.delete_dingtalk_item(this)">' +
                '<i class="icon-uniE6752"></i>' +
                '</a>';
        }

        for(var key in data){

            if(key==='control_department'){
                dom += endpoint_item_comb_dom(random_id, rules_submit, key, data[key], 'Ding_Talk_'+Strack.string_ucwords(key), 'Ding_Talk_Notice_'+Strack.string_ucwords(key));
            }else {
                dom += endpoint_item_input_dom(random_id, rules_submit, key, data[key], 'Ding_Talk_'+Strack.string_ucwords(key), 'Ding_Talk_Notice_'+Strack.string_ucwords(key));
            }

        }

        dom +='</div>';

        return {
            dom : dom,
            random_id: random_id
        };
    }

    /**
     * 节点input dom
     * @param random_id
     * @param rules_submit
     * @param field
     * @param val
     * @param field_lang
     * @param placeholder_lang
     * @returns {string}
     */
    function endpoint_item_input_dom(random_id, rules_submit, field, val, field_lang, placeholder_lang) {

        var dom = '';

        if(rules_submit === 'yes'){
            dom += '<input id="endpoint_val_'+field+'_'+random_id+'" class="form-control form-input" wiget-type="input" wiget-need="yes" wiget-field="endpoint_'+random_id+'-'+field+'" wiget-name="'+StrackLang[field_lang]+'" autocomplete="off" placeholder="'+StrackLang[placeholder_lang]+'" value="'+val+'">';
        }else{
            dom += '<input class="form-control form-input input-disabled" disabled="disabled" placeholder="'+StrackLang[placeholder_lang]+'" value="'+val+'">';
        }

        return '<div class="e-item-input stcol-lg-2">'+
            dom +
            '</div>';
    }

    /**
     * 节点combobox dom
     * @param random_id
     * @param rules_submit
     * @param field
     * @param val
     * @param field_lang
     * @param placeholder_lang
     * @returns {string}
     */
    function endpoint_item_comb_dom(random_id, rules_submit, field, val, field_lang, placeholder_lang) {
        var dom = '';

        if(rules_submit === 'yes'){
            dom += '<input id="endpoint_val_'+field+'_'+random_id+'" class="form-input" wiget-type="combobox_s" wiget-need="yes" wiget-field="endpoint_'+random_id+'-'+field+'" wiget-name="'+StrackLang[field_lang]+'" autocomplete="off" placeholder="'+StrackLang[placeholder_lang]+'" value="'+val+'">';
        }else{
            dom += '<input id="endpoint_val_'+field+'_'+random_id+'" class="form-input" data-options="disabled:true" placeholder="'+StrackLang[placeholder_lang]+'" value="'+val+'">';
        }

        return '<div class="e-item-input stcol-lg-2">'+
            dom +
            '</div>';
    }

    /**
     * 初始化部门列表选择框
     * @param id
     */
    function init_department_list(id) {
        Strack.combobox_widget('#'+id, {
            url: DingTalkPHP["getDepartmentList"],
            valueField: 'id',
            textField: 'name',
            width: 489,
            height: 39,
            multiple: true,
            prompt: StrackLang['Ding_Talk_Notice_Control_Department']
        });
    }
});
