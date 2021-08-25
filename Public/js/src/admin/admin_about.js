$(function(){
    obj = {
        //更新系统
        upgrade_system: function () {

        }
    };

    //ajax错误信息
    var clipboard;

    load_data('new');

    Strack.delete_storage('admin_menu_top');

    /**
     * 加载About页面统计数据
     */
    function load_data(mode) {
        $.ajax({
            type:'POST',
            url: AboutPHP['getSystemAbout'],
            dataType: 'json',
            success : function (data) {
                // 版本号
                $("#strack_version").html(data["package_version"]);
                // 服务器状态显示
                generate_server_status_list(data["server_list"]);
            }
        });
    }

    /**
     * 生成服务器状态列表
     * @param server_list
     */
    function generate_server_status_list(server_list) {
        var dom = '';
        server_list.forEach(function (val) {
            dom += server_dom(val);
        });

        $("#server_list").empty().append(dom);
    }

    /**
     * 服务器状态Base DOM
     * @returns {string}
     */
    function server_dom(val) {
        var dom = '';
        dom +='<div class="server-sta-item aign-left '+server_status_css(val["status"])+'">'+
            '<div class="server-item-name">'+
            server_status_name(val["status"])+
            '</div>'+
            '<div class="server-item-sta">'+
            val["name"]+
            '</div>'+
            '<div class="server-item-sta">'+
            val["connect_time"]+ ' ms' +
            '</div>'+
            '</div>';
        return dom;
    }

    /**
     * 服务器状态 背景颜色 css
     * @param code
     * @returns {string}
     */
    function server_status_css(code) {
        if(parseInt(code) === 200){
            return "background-success";
        }else {
            return "background-danger";
        }
    }

    /**
     * 获得状态
     */
    function server_status_name(code) {
        if(parseInt(code) === 200){
            return StrackLang['Status_OK'];
        }else {
            return StrackLang['Status_Lose'];
        }
    }
});
