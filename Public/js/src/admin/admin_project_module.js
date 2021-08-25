$(function () {
    obj = {
        project_module_update: function (i) {
            var select_rows = $('#module_list_box').datagrid('getSelections');
            var all_rows = $('#module_list_box').datagrid('getRows');

            var select_rows_map = [];
            select_rows.forEach(function (val) {
                select_rows_map.push(val['code']);
            });

            var all_rows_map = {};
            all_rows.forEach(function (val) {
                if($.inArray(val['code'], select_rows_map) >= 0){
                    val['selected'] = 'yes';
                }else {
                    val['selected'] = 'no';
                }
                all_rows_map[val['code']] = val;
            });

            $.ajax({
                type : 'POST',
                url : ProjectModulePHP['updateProjectModuleConfig'],
                data : JSON.stringify(all_rows_map),
                dataType : 'json',
                contentType: "application/json",
                beforeSend : function () {
                    $.messager.progress({ title:StrackLang['Waiting'], msg:StrackLang['loading']});
                },
                success : function (data) {
                    $.messager.progress('close');
                    if(parseInt(data['status']) === 200){
                        Strack.top_message({bg:'g',msg: data['message']});
                        obj.project_module_reset();
                    }else {
                        layer.msg(data["message"], {icon: 7, time: 1200, anim: 6});
                    }
                }
            });
        },
        project_module_reset:function(){
            $('#module_list_box').datagrid('reload');
        }
    };

    $('#module_list_box').datagrid({
        url: ProjectModulePHP['getProjectNavModuleList'],
        idField: 'module_id',
        queryParams: {
            template_id: 0
        },
        frozenColumns:[[
            {field: 'module_id', checkbox:true}
        ]],
        columns:[[
            {field:'code',title:'编码',width:140, align:'center'},
            {field:'name',title:'名称',width:140, align:'center'}
        ]],
        onLoadSuccess: function (data) {
            $.ajax({
                type : 'POST',
                url : ProjectModulePHP['getProjectModuleConfig'],
                dataType : 'json',
                success : function (data) {
                    for(var key in data){
                        if(data[key]['selected'] === 'yes'){
                            $('#module_list_box').datagrid('selectRecord', data[key]['module_id']);
                        }
                    }
                }
            });
        }
    });
});
