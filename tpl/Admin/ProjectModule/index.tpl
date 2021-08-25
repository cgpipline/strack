<extend name="tpl/Base/common_admin.tpl" />

<block name="head-title"><title>{$Think.lang.Project_Module_Title}</title></block>

<block name="head-js">
	<if condition="$is_dev == '1' ">
		<script type="text/javascript" src="__JS__/src/admin/admin_project_module.js"></script>
		<else/>
		<script type="text/javascript" src="__JS__/build/admin/admin_project_module.min.js"></script>
	</if>
</block>
<block name="head-css">
	<script type="text/javascript">
		var ProjectModulePHP = {
            'getProjectModuleConfig' : '{:U("Admin/ProjectModule/getProjectModuleConfig")}',
            'updateProjectModuleConfig' : '{:U("Admin/ProjectModule/updateProjectModuleConfig")}',
			'getProjectNavModuleList': '{:U("Admin/Template/getProjectNavModuleList")}'
		};
		Strack.G.MenuName="projectModule";
	</script>
</block>

<block name="admin-main-header">
	{$Think.lang.Project_Module_Setting}
</block>

<block name="admin-main">
	<input id="rules_submit" type="hidden" name="rules_submit" value="{$view_rules.submit}"/>
	<div id="active-logServer" class="admin-content-wrap">
		<div class="admin-ui-full">
			<div class="form-module-list">
				<!--模块列表-->
				<table id="module_list_box"></table>
			</div>
			<div class="form-button-full">
				<if condition="$view_rules.submit == 'yes' ">
					<a href="javascript:;" onclick="obj.project_module_update(this);" >
						<div class="form-button-long form-button-hover">
							{$Think.lang.Submit}
						</div>
					</a>
				</if>
			</div>
		</div>
	</div>
</block>
