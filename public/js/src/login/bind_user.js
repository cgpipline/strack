/**
 * Login.js
 */
window.onload=function() {

    obj = {
        //登录验证
        login_verify : function () {
            var login_param = {};
            $("#login_form").find("input").each(function () {
                var val = $(this).val();
                if(!val){
                    layer.tips($(this).attr("data-notice"), this, {
                        tips: [2, '#FF4949'],
                        time: 1000
                    });
                    login_param = {};
                    return false;
                }else {
                    login_param[$(this).attr("name")] = val;
                }
            });

            if(!$.isEmptyObject(login_param)){
                login_param["from"] = "web";
                login_param["method"] = "";
                verify_login(login_param);
            }
        }
    };


    var Glogin_Server = {};

    // 验证浏览器版本是否合乎要求
    Strack_Check.check_browser_version(
        function () {
            // 按回车键登录
            $(document).keydown(function(e){
                if(e.keyCode === 13){
                    //阻止浏览器默认动作
                    e.preventDefault();
                    obj.login_verify();
                }
            });
        }
    );


    /**
     * 验证登录信息
     * @param login_param
     */
    function verify_login(login_param) {
        if(!$.isEmptyObject(Glogin_Server)){
            login_param["method"] = Glogin_Server["type"];
            login_param["server_id"] = Glogin_Server["id"];
        }
        $.ajax({
            type : 'POST',
            url : LoginPHP['verifyLogin'],
            dataType:'json',
            data : login_param,
            success : function (data) {
                if (parseInt(data['status']) === 200) {

                    $('head').append(data['data']['uc_script']);

                    if (data["url"]) {
                        location.href = data["url"];
                    } else {
                        //goto home index
                        location.href = StrackLogin['INDEX'];
                    }
                } else {
                    Strack_Check.login_top_notice('.login-error-show', data["message"]);
                }
            }
        });
    }
};
