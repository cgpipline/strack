<extend name="tpl/Base/common_admin.tpl" />

<block name="head-title"><title>{$Think.lang.Field_Settings_Title}</title></block>

<block name="head-js">
	<if condition="$is_dev == '1' ">
		<script type="text/javascript" src="__JS__/src/admin/admin_fields_settings.js"></script>
		<else/>
		<script type="text/javascript" src="__JS__/build/admin/admin_fields_settings.min.js"></script>
	</if>
</block>
<block name="head-css">
	<script type="text/javascript">
		var FieldsSettingsPHP = {
			'getFieldSettings' : '{:U("Admin/FieldSettings/getFieldSettings")}',
			'updateFieldSettings' : '{:U("Admin/FieldSettings/updateFieldSettings")}',
			'getCustomFieldList' : '{:U("Admin/Field/getCustomFieldList")}',
			'getAccountList': '{:U("Admin/Account/getAccountList")}',
			'getStatusList': '{:U("Admin/Status/getStatusList")}',
		};
		Strack.G.MenuName="fieldSettings";
	</script>
</block>

<block name="admin-main-header">
	{$Think.lang.Field_Settings}
</block>

<block name="admin-main">
	<div id="active-fieldSettings" class="admin-content-wrap">
		<div class="admin-ui-full">
			<form id="save_setting" class="form-horizontal">
				<div class="form-group">
					<div class="formula-list">
						<div class="formula-item">
							<strong>{$Think.lang.Reviewed_By}</strong> <input id="formula_reviewed_by" name="formula_reviewed_by">
						</div>
						<div class="formula-item">
							<strong>{$Think.lang.Assignee_Field}</strong> <input id="formula_assignee_field" name="formula_assignee_field">
						</div>
						<div class="formula-item">
							<strong>{$Think.lang.NoStart_By_Status}</strong> <input id="formula_no_start_status" name="formula_no_start_status">
						</div>
						<div class="formula-item">
							<strong>{$Think.lang.Reviewed_By_Status}</strong> <input id="formula_reviewed_by_status" name="formula_reviewed_by_status">
						</div>
						<div class="formula-item">
							<strong>{$Think.lang.In_Progress_Status}</strong> <input id="formula_in_progress_status" name="formula_in_progress_status">
						</div>
						<div class="formula-item">
							<strong>{$Think.lang.End_By_Status}</strong> <input id="formula_end_by_status" name="formula_end_by_status">
						</div>
						<div class="formula-item">
							<strong>{$Think.lang.Estimate_Time_Consuming_Fields}</strong> <input id="estimate_working_hours" name="estimate_working_hours">
						</div>
						<div class="formula-item">
							<strong>{$Think.lang.Reviewed_Time_Consuming_Fields}</strong> <input id="examine_working_hours" name="examine_working_hours">
						</div>
						<div class="formula-item">
							<strong>{$Think.lang.Actual_Time_Consuming}</strong> <input id="formula_actual_time_consuming" name="formula_actual_time_consuming">
						</div>
						<div class="formula-item">
							<strong>{$Think.lang.Settlement_Time_Consuming_Fields}</strong> <input id="settlement_time_consuming" name="settlement_time_consuming">
						</div>
						<div class="formula-item">
							<strong>{$Think.lang.Grouping_of_Persons}</strong> <input id="grouping_of_persons" name="grouping_of_persons">
						</div>
						<div class="formula-item">
							<strong>{$Think.lang.Grouping_of_Stage}</strong> <input id="grouping_of_stage" name="grouping_of_stage">
						</div>
						<div class="formula-item">
							<strong>{$Think.lang.Subtask_Fields}</strong> <input id="sub_task" name="sub_task">
						</div>
					</div>
				</div>
				<div class="form-button-full">
					<if condition="$view_rules.submit == 'yes' ">
						<a href="javascript:;" onclick="obj.save_setting();" >
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
