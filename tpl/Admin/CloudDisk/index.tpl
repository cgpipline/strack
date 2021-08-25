<extend name="tpl/Base/common_admin.tpl" />

<block name="head-title"><title>{$Think.lang.Cloud_Disk_Title}</title></block>

<block name="head-js">
	<if condition="$is_dev == '1' ">
		<script type="text/javascript" src="__JS__/src/admin/admin_cloud_disk.js"></script>
		<else/>
		<script type="text/javascript" src="__JS__/build/admin/admin_cloud_disk.min.js"></script>
	</if>
</block>
<block name="head-css">
	<script type="text/javascript">
		var CloudDiskPHP = {
            'getCloudDiskConfig' : '{:U("Admin/CloudDisk/getCloudDiskConfig")}',
            'updateCloudDiskConfig' : '{:U("Admin/CloudDisk/updateCloudDiskConfig")}'
		};
		Strack.G.MenuName="cloudDisk";
	</script>
</block>

<block name="admin-main-header">
	{$Think.lang.Cloud_Disk_Setting}
</block>

<block name="admin-main">
	<input id="rules_submit" type="hidden" name="rules_submit" value="{$view_rules.submit}"/>
	<div id="active-logServer" class="admin-content-wrap">
		<div class="admin-ui-full">
			<form id="cloud_disk_setting" class="form-horizontal">
				<div class="form-group required">
					<label class="stcol-lg-1 control-label">{$Think.lang.Cloud_Disk_Open}</label>
					<div class="stcol-lg-2">
						<div class="bs-component">
							<if condition="$view_rules.submit == 'yes' ">
								<input id="open_cloud_disk" class="form-input" wiget-type="switch" wiget-need="no" wiget-field="open_cloud_disk" wiget-name="{$Think.lang.Cloud_Disk_Open}">
								<else/>
								<input id="open_cloud_disk" class="form-input" data-options="disabled:true">
							</if>
						</div>
					</div>
				</div>
				<div class="form-group required">
					<label class="stcol-lg-1 control-label">{$Think.lang.Type}</label>
					<div class="stcol-lg-2">
						<div class="bs-component">
							<input id="cloud_disk_type" class="form-control form-input input-disabled"  wiget-type="input" wiget-need="yes" wiget-field="type" wiget-name="{$Think.lang.Type}" readonly="readonly" value="s3">
						</div>
					</div>
				</div>
				<div class="form-group required">
					<label class="stcol-lg-1 control-label">{$Think.lang.Endpoint}</label>
				</div>
				<div id="cloud_disk_list" class="cloud-disk">
					<!--网盘节点配置-->
				</div>
				<div class="cloud-disk-bnt">
					<if condition="$view_rules.submit == 'yes' ">
						<a href="javascript:;" class="add-cloud-disk" onclick="obj.add_endpoint(this)">
							<i class="icon-uni3432"></i> {$Think.lang.Adding_Cloud_Disk_Nodes}
						</a>
					</if>
				</div>
				<div class="form-button-full">
					<if condition="$view_rules.submit == 'yes' ">
						<a href="javascript:;" onclick="obj.cloud_disk_update();" >
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
