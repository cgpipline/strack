<extend name="tpl/Base/common.tpl"/>

<block name="head-title"><title>{$Think.lang.My_Schedule_Title}</title></block>

<block name="head-js">
    <if condition="$is_dev == '1' ">
        <script type="text/javascript" src="__JS__/src/home/schedule.js"></script>
        <else/>
        <script type="text/javascript" src="__JS__/build/home/schedule.min.js"></script>
    </if>
    <div id="head_js"></div>
</block>
<block name="head-css">
    <script type="text/javascript">
        var SchedulePHP = {

        };
        Strack.G.MenuName = "schedule";
    </script>
</block>

<block name="main">

    <div id="page_hidden_param">
        <input name="rule_my_schedule_index" type="hidden" value="{$view_rules.my_schedule_index}">
        <input name="rule_update_widget" type="hidden" value="{$view_rules.update_widget}">
        <input name="rule_create" type="hidden" value="{$view_rules.create}">
        <input name="rule_lock_task_plan" type="hidden" value="{$view_rules.lock_task_plan}">
        <input name="rule_delete_task_plan" type="hidden" value="{$view_rules.delete_task_plan}">
        <input name="rule_schedule_delete_task" type="hidden" value="{$view_rules.schedule_delete_task}">
    </div>

    <div class="page-wrap">
        <div id="scheduler_my_index" class="my-scheduler-index">
        </div>
    </div>


</block>
