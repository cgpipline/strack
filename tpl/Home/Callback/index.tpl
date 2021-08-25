<extend name="tpl/Base/common_login.tpl"/>

<block name="head-title"><title>{$Think.lang.Login_Title}</title></block>

<block name="head-js">
    <if condition="$is_dev == '1' ">
        <script type="text/javascript" src="__JS__/src/login/bind_user.js"></script>
        <else/>
        <script type="text/javascript" src="__JS__/build/login/bind_user.min.js"></script>
    </if>
    <script type="text/javascript" src="__JS__/lib/ellipsis.min.js"></script>
</block>
<block name="head-css">
    <if condition="$is_dev == '1' ">
        <link rel="stylesheet" href="__CSS__/src/login.css">
        <else/>
        <link rel="stylesheet" href="__CSS__/build/login.min.css">
    </if>
    <script type="text/javascript">
        var LoginPHP = {
            'verifyLogin': '{:U("Home/Login/verifyLogin")}',
            'getThirdServerList': '{:U("Home/Login/getThirdServerList")}'
        };
    </script>
</block>

<block name="main">
    <div id="login-main" class="login-main">
        <div id="login-container" class="login-container">
            <div id="login-main-dom">
                <div class="login-container-title">
                    <p>{$Think.lang.Bind_Account}</p>
                </div>
                <div class="login-error-show"></div>
                <div class="login-content">
                    <form id="login_form" method="post" autocomplete="off">
                        <input type="hidden" name="openid" value="{$open_id}" />
						<input type="hidden" name="login_type" value="{$login_type}" />
                        <div id="username" class="login-container-field">
                            <div class="login-input-icon">
                                <i class="icon-uniE997"></i>
                            </div>
                            <div class="login-input-text">
                                <input class="login-username" name="login_name" placeholder="{$Think.lang.Login_Username}" type="text" data-notice="{$Think.lang.Input_Login_Login_Name_Require}" >
                            </div>
                        </div>
                        <div id="password" class="login-container-field">
                            <div class="login-input-icon">
                                <i class="icon-uniE9B7"></i>
                            </div>
                            <div class="login-input-text">
                                <input class="login-password" name="password" placeholder="{$Think.lang.Login_Password}" type="password" data-notice="{$Think.lang.Input_Login_Password_Require}">
                            </div>
                        </div>
                    </form>
                    <div class="login-submit-field">
                        <a href="javascript:;" class="submit-btn" onclick="obj.login_verify(this)">{$Think.lang.Confirm}</a>
                    </div>
                    <div class="login-bottom">
                    </div>
                </div>
            </div>
        </div>
    </div>
</block>
