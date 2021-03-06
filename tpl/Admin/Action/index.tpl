<extend name="tpl/Base/common_admin.tpl"/>

<block name="head-title"><title>{$Think.lang.Action_Title}</title></block>

<block name="head-js">
    <if condition="$is_dev == '1' ">
        <script type="text/javascript" src="__JS__/src/admin/admin_action.js"></script>
        <else/>
        <script type="text/javascript" src="__JS__/build/admin/admin_action.min.js"></script>
    </if>
</block>
<block name="head-css">
    <script type="text/javascript">
        var ActionPHP = {
            'getActionGridData': '{:U("Admin/Action/getActionGridData")}',
            'deleteAction': '{:U("Admin/Action/deleteAction")}'
        };
        Strack.G.MenuName = "action";
    </script>
</block>

<block name="admin-main-header">
    {$Think.lang.Admin_Action}
</block>

<block name="admin-main">
    <div id="active-action" class="admin-content-account">
        <div id="page_hidden_param">
            <input name="page" type="hidden" value="{$page}">
            <input name="module_id" type="hidden" value="{$module_id}">
            <input name="module_type" type="hidden" value="{$module_type}">
            <input name="module_code" type="hidden" value="{$module_code}">
            <input name="module_name" type="hidden" value="{$module_name}">
            <input name="rule_panel_filter" type="hidden" value="{$view_rules.filter_panel}">
            <input name="rule_modify_filter" type="hidden" value="{$view_rules.filter_panel__save_filter}">
            <input name="rule_sort" type="hidden" value="{$view_rules.toolbar__sort}">
            <input name="rule_group" type="hidden" value="{$view_rules.toolbar__group}">
        </div>

        <div class="projtable-content">

            <!--assembly grid data list-->
            <div id="grid_datagrid_main" class="entity-datalist base-m-grid">
                <div id="tb_grid" class="proj-tb tb-padding-grid grid-toolbar" data-grid="main_datagrid_box" data-page="{$page}" data-schemapage="{$page}" data-moduleid="{$module_id}" data-projectid="0" data-maindom="grid_datagrid_main" data-bardom="grid_filter_main">

                    <eq name="view_rules.toolbar__delete" value="yes">
                        <div class="st-buttons-sig st-buttons-blue aign-left">
                            <a href='javascript:;' onclick="obj.action_delete(this);" data-lang="{$Think.lang.Delete}">
                                <i class="icon-remove icon-left"></i>
                                <span class="stp-title">{$Think.lang.Delete}</span>
                            </a>
                        </div>
                    </eq>

                    <eq name="view_rules.toolbar__sort" value="yes">
                        <div class="ui dropdown st-dropdown aign-left">
                            {$Think.lang.Sort}<i class="dropdown icon"></i>
                            <div class="menu grid_sort data-fields">
                                <!--sort list-->
                                <a href="javascript:;" class="item sort-bnt field-disable" onclick="Strack.dropdown_sort(this);" data-sort="asc" data-panel="grid">
                                    <i class="icon-uniE93F icon-left"></i>{$Think.lang.Sort_Asc}
                                </a>
                                <a href="javascript:;" class="item sort-bnt field-disable" onclick="Strack.dropdown_sort(this);" data-sort="desc" data-panel="grid">
                                    <i class="icon-uniE946 icon-left"></i>{$Think.lang.Sort_Desc}
                                </a>
                                <a href="javascript:;" class="item sort-bnt field-disable" onclick="Strack.advance_sort(this);" data-sort="advance" data-panel="grid">
                                    <i class="icon-uniE9C6 icon-left"></i>{$Think.lang.Sort_Adv}
                                </a>
                                <div class="divider"></div>
                                <a href="javascript:;" class="item" onclick="Strack.sort_cancel(this);" data-panel="grid">
                                    <i class="icon-uniE682 icon-left"></i>{$Think.lang.Sort_Cancel}
                                </a>
                            </div>
                        </div>
                    </eq>

                    <eq name="view_rules.toolbar__group" value="yes">
                        <div class="ui dropdown st-dropdown aign-left">
                            {$Think.lang.Group}<i class="dropdown icon"></i>
                            <div class="menu grid_group data-fields">
                                <!--group list-->
                                <a href="javascript:;" class="item" onclick="Strack.delete_group(this);" data-panel="grid">
                                    <i class="icon-uniE682 icon-left"></i>{$Think.lang.Group_Cancel}
                                </a>
                            </div>
                        </div>
                    </eq>

                    <eq name="view_rules.toolbar__column" value="yes">
                        <div class="ui dropdown st-dropdown aign-left">
                            {$Think.lang.Display_Column}<i class="dropdown icon"></i>
                            <div class="menu grid_fields">
                                <!--fields list-->
                                <eq name="view_rules.toolbar__column__manage_custom_fields" value="yes">
                                    <a href="javascript:;" class="item" onclick="Strack.manage_fields(this);" data-lang="{$Think.lang.Manage_Custom_Fields}">
                                        <i class="icon-uniE71D icon-left"></i>{$Think.lang.Manage_Custom_Fields}
                                    </a>
                                </eq>
                            </div>
                        </div>
                    </eq>

                    <eq name="view_rules.filter_panel" value="yes">
                        <div class="ui search aign-right">
                            <input id="main_grid_search" autocomplete="off"/>
                        </div>
                    </eq>

                </div>
                <table id="main_datagrid_box" class="datagrid-table"></table>
            </div>
            <eq name="view_rules.filter_panel" value="yes">
                <div id="grid_filter_main" class="datagrid-filter filter-full-active filter-min" data-grid="main_datagrid_box" data-page="{$page}" data-schemapage="{$page}" data-moduleid="{$module_id}" data-projectid="0" data-maindom="grid_datagrid_main" data-bardom="grid_filter_main">
                    <!--????????????-->
                </div>
            </eq>

        </div>
    </div>
</block>