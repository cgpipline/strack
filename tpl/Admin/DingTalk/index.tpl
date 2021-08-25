<extend name="tpl/Base/common_admin.tpl" />

<block name="head-title"><title>{$Think.lang.Ding_Talk_Title}</title></block>

<block name="head-js">
	<if condition="$is_dev == '1' ">
		<script type="text/javascript" src="__JS__/src/admin/admin_ding_talk.js"></script>
		<else/>
		<script type="text/javascript" src="__JS__/build/admin/admin_ding_talk.min.js"></script>
	</if>
</block>
<block name="head-css">
	<script type="text/javascript">
		var DingTalkPHP = {
            'getDingTalkConfig' : '{:U("Admin/DingTalk/getDingTalkConfig")}',
			'getDepartmentList' : '{:U("Admin/Department/getDepartmentList")}',
            'updateDingTalkConfig' : '{:U("Admin/DingTalk/updateDingTalkConfig")}'
		};
		Strack.G.MenuName="dingtalk";
	</script>
</block>

<block name="admin-main-header">
	{$Think.lang.Ding_Talk_Setting}
</block>

<block name="admin-main">
	<input id="rules_submit" type="hidden" name="rules_submit" value="{$view_rules.submit}"/>
	<div id="active-logServer" class="admin-content-wrap">
		<div class="admin-ui-full">
			<form id="ding_talk_setting" class="form-horizontal">
				<div class="form-group required">
					<label class="stcol-lg-1 control-label">{$Think.lang.Node}</label>
				</div>
				<div id="cloud_disk_list" class="cloud-disk">
					<!--网盘节点配置-->
				</div>
				<div class="cloud-disk-bnt">
					<if condition="$view_rules.submit == 'yes' ">
						<a href="javascript:;" class="add-cloud-disk" onclick="obj.add_company_node(this)">
							<i class="icon-uni3432"></i> {$Think.lang.Adding_Ding_Talk_Nodes}
						</a>
					</if>
				</div>
				<div class="form-button-full">
					<if condition="$view_rules.submit == 'yes' ">
						<a href="javascript:;" onclick="obj.dingtalk_update();" >
							<div class="form-button-long form-button-hover">
								{$Think.lang.Submit}
							</div>
						</a>
					</if>
				</div>
			</form>
		</div>
	</div>
</block>
