<extend name="tpl/Base/common.tpl"/>

<block name="head-title"><title>{$Think.lang.Team_kanban_Title}</title></block>

<block name="head-js">
  <if condition="$is_dev == '1' ">
    <!-- <script type="text/javascript" src="__JS__/src/xmlayout.js"></script> -->
    <script type="text/javascript" src="__JS__/lib/echarts.js"></script>
    <script type="text/javascript" src="__JS__/lib/echart.theme.js"></script>
    <script type="text/javascript" src="__JS__/src/home/report.js"></script>
    <else/>
    <!-- <script type="text/javascript" src="__JS__/src/xmlayout.min.js"></script> -->
    <script type="text/javascript" src="__JS__/lib/echarts.min.js"></script>
    <script type="text/javascript" src="__JS__/lib/echart.theme.min.js"></script>
    <script type="text/javascript" src="__JS__/build/home/report.min.js"></script>
  </if>
  <div id="head_js"></div>
</block>
<block name="head-css">
  <script type="text/javascript">
    var ReportPHP = {
      'getUserPlannedData': '{:U("Home/Report/getUserPlannedData")}'
    };
    Strack.G.MenuName="report";
  </script>
</block>

<block name="main">
  <div class="page-wrap">
    <div id="container" style="height: 100%">
      <div class="fc-toolbar">
        <div id="fc_filter_wrap"></div>
        <input type="hidden" id="date" value="">
        <input type="hidden" id="user_ids" value="">
      </div>
      <div id="teamViewer" style="height: 500px"></div>
      <div id="overTime" style="height: 500px"></div>
      <div id="loadRate" style="height: 500px"></div>
    </div>
  </div>
</block>
