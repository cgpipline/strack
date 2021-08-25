var Strack_Check = {
    /**
     * 判断浏览器内核
     * @param callback
     */
    check_browser_version: function (callback) {
        //判断浏览器
        var mozilla = /firefox/.test(navigator.userAgent.toLowerCase());
        var webkit = /webkit/.test(navigator.userAgent.toLowerCase());
        var chrome = /chrome/.test(navigator.userAgent.toLowerCase());
        var opera = /opera/.test(navigator.userAgent.toLowerCase());
        var msie = /msie/.test(navigator.userAgent.toLowerCase());
        var msie6 = /msie 6.0/.test(navigator.userAgent.toLowerCase());


        if (msie || msie6||!!window.ActiveXObject || "ActiveXObject" in window) {
            // 不支持IE提示错误
            document.body.innerHTML = Strack_Check.show_error_board(StrackLang['Login_Error_Title'], StrackLang['Login_Error_ContentL1'], StrackLang['Login_Error_ContentL2']);
        }else {
            // 不是IE内核浏览器继续操作
            callback();
        }
    },
    /**
     * 错误信息提示板
     * @param title
     * @param contentL1
     * @param contentL2
     * @returns {string}
     */
    show_error_board: function (title, contentL1, contentL2) {
        var login_error_dom = '';
        login_error_dom +=
            '<div class="login-main">'+
            '<div class="login-error">' +
            '<div class="login-error-title">'+title+'</div>' +
            '<div class="login-error-content">' +
            '<div class="login-error-icon"><i class="icon-uniEA30"></i></div>' +
            '<p>'+contentL1+'</p>' +
            '<p>'+contentL2+'</p>' +
            '</div>' +
            '</div>' +
            '</div>';
        return login_error_dom;
    },
    // 登录面板顶部提示
    login_top_notice : function (dom, msg) {
        $(dom).css('min-height','35px').html(msg);
        window.setTimeout(function () {
            $(dom).css('min-height','0px').html('');
        }, 6000);
    },
    // 格式化时间
    time_format: function (time) {
        var a = time,
            m=0,
            s=0,
            ctiem='';
        m = parseInt(a/60);
        s = parseInt(a%60);
        if(m!=0){
            ctiem +=Strack_Check.timelog_fix(m,2)+':';
        }
        //当前s为零的时候
        if(m!=0&&!s){
            ctiem +=Strack_Check.timelog_fix(s,2);
        }
        if(s!=0){
            ctiem +=Strack_Check.timelog_fix(s,2);
        }
        //当只有秒的时候
        if(m==0){ctiem +=' sec'}
        return ctiem;
    },
    timelog_fix: function (num, length) {
        return ('' + num).length < length ? ((new Array(length + 1)).join('0') + num).slice(-length) : '' + num;
    },
    device_unique_code: function() {
        var canvas = document.createElement('canvas');
        var ctx = canvas.getContext('2d');
        var txt = 'http://security.device_unique.com/';
        ctx.textBaseline = "top";
        ctx.font = "14px 'Arial'";
        ctx.textBaseline = "device_unique_code";
        ctx.fillStyle = "#f60";
        ctx.fillRect(125, 1, 62, 20);
        ctx.fillStyle = "#069";
        ctx.fillText(txt, 2, 15);
        ctx.fillStyle = "rgba(102, 204, 0, 0.7)";
        ctx.fillText(txt, 4, 17);
        var b64 = canvas.toDataURL().replace("data:image/png;base64,", "");
        var bin = atob(b64);
        var crc = Strack_Check.bin2hex(bin.slice(-16, -12));
        return crc;
    },
    bin2hex: function(str) {
        var result = "";
        for (i = 0; i < str.length; i++) {
            result += Strack_Check.int16_to_hex(str.charCodeAt(i));
        }
        return result;
    },
    int16_to_hex:function(i) {
        var result = i.toString(16);
        var j = 0;
        while (j + result.length < 4) {
            result = "0" + result;
            j++;
        }
        return result;
    },
    // 判断当前设备是否启用oss登录
    get_oss_status: function (callback) {
        var device_unique_code = Strack_Check.device_unique_code();
        var oauth_oss_open = $('#oauth_oss_open').val();

        if(oauth_oss_open === 'yes'){
            var layer_index;
            $.ajax({
                type : 'POST',
                url : LoginPHP['getOssStatus'],
                dataType:'json',
                data : {
                    device_unique_code: device_unique_code
                },
                beforeSend: function () {
                    layer_index = layer.load(1, {
                        shade: [0.1,'#fff']
                    });
                },
                success : function (data) {
                    layer.close(layer_index);
                    if (parseInt(data['status']) === 200) {
                        window.location.href = data['data']['redirect_uri'] + "?code=" + data['data']['code']+ "&&state=" + data['data']['state']+ "&&device_unique_code=" + data['data']['device_unique_code'];
                    }else {
                        callback();
                    }
                }
            });
        }else {
            callback();
        }
    }
};
