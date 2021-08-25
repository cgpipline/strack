$(function(){
    obj={
        cloud_disk_update:function(){
            var formData = Strack.validate_form('cloud_disk_setting');

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
                        url : CloudDiskPHP['updateCloudDiskConfig'],
                        data : JSON.stringify({
                            type: formData['data']['type'],
                            open_cloud_disk: formData['data']['open_cloud_disk'],
                            endpoints: endpoints
                        }),
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
        add_endpoint: function (i) {
            $('#cloud_disk_list').append(endpoint_item_dom(rules_submit, {
                'base_url' : '',
                'check_url' : '',
                'login_name': '',
                'password': ''
            }));
        },
        // 删除云盘
        delete_cloud_item: function (i) {
            var $cloud_parent = $(i).closest('.cloud-e-item');
            $cloud_parent.remove();

            obj.cloud_disk_update();
        }
    };

    var rules_submit = $('#rules_submit').val();

    //ajax获取默认设置参数
    $.ajax({
        type: 'POST',
        url: CloudDiskPHP['getCloudDiskConfig'],
        dataType: 'json',
        success: function (data) {

            // 邮件开启开关
            var open_cloud_disk = data["open_cloud_disk"]? data["open_cloud_disk"] : 0;
            Strack.init_open_switch({
                dom: '#open_cloud_disk',
                value: open_cloud_disk,
                onText: StrackLang['Switch_ON'],
                offText: StrackLang['Switch_OFF'],
                width: 100
            });

            for(var key in data){
                if($.inArray(key, [ "open_cloud_disk",  "endpoints"]) < 0){
                    $('#set_'+key).val(data[key]);
                }
            }


            var e_dom = [];
            if($.isEmptyObject(data['endpoints']) === false){
                for(var key in data['endpoints'] ){
                    e_dom.push(endpoint_item_dom(rules_submit, data['endpoints'][key]));
                }
            }

            $('#cloud_disk_list').append(e_dom.join(''));
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
            dom += '<a href="javascript:;" class="e-del-bnt" onclick="obj.delete_cloud_item(this)">' +
                '<i class="icon-uniE6752"></i>' +
                '</a>';
        }

        for(var key in data){
            dom += endpoint_item_input_dom(random_id, rules_submit, key, data[key], 'Cloud_Disk_'+Strack.string_ucwords(key), 'Cloud_Disk_Notice_'+Strack.string_ucwords(key));
        }

        dom +='</div>';

        return dom;
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
});
