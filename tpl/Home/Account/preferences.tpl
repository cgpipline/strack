<extend name="tpl/Base/common_account.tpl" />

<block name="head-title"><title>{$Think.lang.Preferences_Title}</title></block>

<block name="head-js">
  <if condition="$is_dev == '1' ">
    <script type="text/javascript" src="__JS__/src/home/my_preference.js"></script>
    <else/>
    <script type="text/javascript" src="__JS__/build/home/my_preference.min.js"></script>
  </if>
</block>
<block name="head-css">
  <script type="text/javascript">
    var AccountPHP = {
        'getUserPreference': '{:U('Home/User/getUserPreference')}',
        'saveUserPreference': '{:U('Home/User/saveUserPreference')}',
        'getLangList': '{:U("Home/Widget/getLangList")}',
        'getTimezoneList': '{:U("Home/Widget/getTimezoneList")}',
        'submitBind': '{:U("Home/User/submitBind")}',
        'cancelBind': '{:U("Home/User/cancelBind")}'
    };
    Strack.G.AccMenu="preferences";
    Strack.G.MenuName="my_account";
  </script>
</block>
<block name="account-main">

  <div id="page_hidden_param">
    <input name="page" type="hidden" value="{$page}">
    <input name="module_id" type="hidden" value="{$module_id}">
    <input name="rule_save" type="hidden" value="{$view_rules.edit}">
  </div>

  <div id="account-pre-main">
    <div class="account-my-bottom">
      <form id="account-pref">
        <div class="account-my-base account-my-bline">
          <div class="account-my-title">
            <div class="account-my-iname">
              <i class="icon-uniE9BD icon-left"></i>
              {$Think.lang.My_Account_Preference}
            </div>
          </div>
        </div>
        <div class="account-my-base">
          <!---account my infor base-->
          <div class="account-my-item">
            <div class="account-my-iname">
              {$Think.lang.Language}
            </div>
            <div class="account-my-input">
              <input id="my_language" class="form-control" type="text" name="my_language">
            </div>
          </div>
          <div class="account-my-item">
            <div class="account-my-iname">
              {$Think.lang.Timezone}
            </div>
            <div class="account-my-input">
              <input  id="my_timezone" class="form-control" type="text" name="my_timezone">
            </div>
          </div>
        </div>
        <if condition="$view_rules.edit == 'yes' ">
          <div class="account-my-savebnt">
            <a href="javascript:;" class="st-dialog-button button-dgsub ah_userinfo_pref" onclick="obj.preference_save();">{$Think.lang.Save}</a>
          </div>
        </if>
      </form>
    </div>
  </div>

  <br>
  <div id="account-login-bind">
    <div class="account-my-bottom">
        <div class="account-my-base account-my-bline">
          <div class="account-my-title">
            <div class="account-my-iname">
              <i class="icon-uniE9BD icon-left"></i>
              {$Think.lang.My_Logon_Binding}
            </div>
          </div>
        </div>
        <div class="account-my-base">

          <eq name="userInfo.qq_bind_open" value="yes">
            <!---绑定QQ登录-->
            <div class="account-pref-item">
              <div class="account-my-iname">
                {$Think.lang.Bind_QQ}
              </div>
              <div class="account-my-input ah_userinfo_pref">
                <if condition="$userInfo.qq_bind_status eq 'no'">
                  <div class="userinfo_bind_name aign-left">{$Think.lang.Unbound}</div>
                  <a href="{$qqurl}" class="st-dialog-button button-dgsub  aign-left">{$Think.lang.To_Bind}</a>
                  <else />
                  <div class="userinfo_bind_name aign-left">{$Think.lang.Binded}</div>
                  <a href="javascript:;" class="st-dialog-button button-dgcel aign-left" onclick="obj.loginbind_cancel(this);" data-logintype="qq">{$Think.lang.Relieve_Bound}</a>
                </if>
              </div>
            </div>
          </eq>

          <eq name="userInfo.strack_union_bind_open" value="yes">
            <!---绑定Strack联合登录-->
            <div class="account-pref-item">
              <div class="account-my-iname">
                {$Think.lang.Bind_Strack_Union}
              </div>
              <div class="account-my-input ah_userinfo_pref">
                <if condition="$userInfo.strack_union_bind_status eq 'no'">
                  <div class="userinfo_bind_name aign-left">{$Think.lang.Unbound}</div>
                  <a href="{$strack_union_url}" class="st-dialog-button button-dgsub  aign-left">{$Think.lang.To_Bind}</a>
                  <else />
                  <div class="userinfo_bind_name aign-left">{$Think.lang.Binded}</div>
                  <a href="javascript:;" class="st-dialog-button button-dgcel aign-left" onclick="obj.loginbind_cancel(this);" data-logintype="strack">{$Think.lang.Relieve_Bound}</a>
                </if>
              </div>
            </div>
          </eq>

        </div>
    </div>
  </div>

</block>
