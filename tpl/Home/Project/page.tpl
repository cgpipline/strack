<extend name="tpl/Base/common.tpl"/>

<block name="head-title"><title>{$Think.lang.Cloud_Disk_Title}</title></block>

<block name="head-js">
    <if condition="$is_dev == '1' ">
        <script type="text/javascript" src="__JS__/src/home/project_cloud_disk.js"></script>
        <else/>
        <script type="text/javascript" src="__JS__/build/home/project_cloud_disk.min.js"></script>
    </if>
</block>
<block name="head-css">
    <script type="text/javascript">
        Strack.G.MenuName = "project_inside";
        Strack.G.ModuleId = '{$module_id}';
        Strack.G.ModuleType = '{$module_type}';
        Strack.G.ProjectId = '{$project_id}';
    </script>
</block>

<block name="main">
    <!--插入网盘地址-->
</block>
