$(function () {
    //看板类型
    var pannel_type = '1';
    var startDate = '';
    var endDate = '';
    var user_ids = '';

    // 加载过滤条件
    var dom = '';

    var view_list = [
        {id: '1' , name: '时长视图'},
        {id: '2' , name: '时间视图'},
        {id: '4' , name: '能效视图'}
    ];

    var view_dom = '';
    view_list.forEach(function (val) {
        view_dom += filter_item_base_dom('view_type', val);
    });

    //填充看板类型
    dom += '<div class="ui dropdown fc-filter-d">视图类型<i class="dropdown icon"></i>' +
        '<div class="menu">' +
        view_dom +
        '</div>' +
        '</div>';

    // 填充人员过滤项
    var u_list_dom = '<div style="padding: 10px;"><div class="item"><ul id="usergroup"></ul></div></div>';

    dom += filter_with_search('user', '团队成员', u_list_dom);

    // 填充时间过滤项
    dom += filter_end_time();

    $('#fc_filter_wrap').append(dom);

    $.ajax({
        type: 'POST',
        url: ReportPHP['getUserPlannedData'],
        data: {
            type: 3
        },
        dataType: "json",
        beforeSend: function () {
            $('#fc_filter_wrap').append(Strack.loading_dom('white','','c_filter'));
        },
        success: function (res) {
            $('#st-load_c_filter').remove();

            if(res.status == 200){
                res = res.data;
                $('#usergroup').tree({
                    checkbox: true,
                    data: res,
                    filter:function(q, node){  // q: doFilter的第二个参数
                        var parent = $('#usergroup').tree('find', node.parent_id);
                        if(parent && parent.hidden == false){
                            return true;
                        }
                        if(node.text.indexOf(q) != -1){
                            return true;
                        }else{
                            return false;
                        }
                    },
                    onCheck: function (node, checked) {
                        var nodes = $(this).tree('getChecked');
                        user_ids = [];

                        nodes.forEach(function (val) {
                            var val_str = val['id']+'';
                            if(val_str.indexOf("u_") != -1){
                                user_ids.push(val['id'].replace('u_',''));
                            }
                        });

                        if(user_ids){
                            if(pannel_type == '1'){
                                get_duration_data();
                            }else if(pannel_type == '2'){
                                get_time_data();
                            }else{
                                get_eer_data();
                            }
                        }
                    }
                });
            }

        }
    });

    // 初始化下拉框
    Strack.init_dropdown('.fc-toolbar .ui.menu , .ui.dropdown');

    // 初始化日期选择框
    // 初始化开始时间
    $('#fc_endtime_from').datebox({
        width: 100,
        height: 25,
        end_limit: "fc_endtime_to",
        onSelect: function (startTime) {
            // 清除时间其他选项
            Strack.handle_calendar_check_icon(this, 'date_all', 'end_time');

            if(pannel_type == '1'){
                startDate = endDate = Strack.date_format(startTime, "yyyy-MM-dd");
                get_duration_data();
            }else if(pannel_type == '2'){
                startDate = endDate = Strack.date_format(startTime, "yyyy-MM-dd");
                get_time_data();
            }else{
                //当视图类型不为1和2时，显示时间段选择
                // 判断是否选择了结束时间
                startDate = Strack.date_format(startTime, "yyyy-MM-dd");
                if(endDate){
                    get_eer_data();
                }
            }

        }
    });

    // 初始化结束时间
    $('#fc_endtime_to').datebox({
        width: 100,
        height: 25,
        start_limit: "fc_endtime_from",
        onSelect: function (endTime) {
            // 清除时间其他选项
            Strack.handle_calendar_check_icon(this, 'date_all', 'end_time');

            // 判断是否选择了开始时间
            endDate = Strack.date_format(endTime, "yyyy-MM-dd");
            if(startDate){
                get_eer_data();
            }
        }
    });

    Strack.calendar_click_filter = function (e, i) {
        e.stopPropagation();//阻止冒泡
        e.preventDefault();//阻止默认行为

        var type = $(i).data('type'),
            value = $(i).data('value');

        Strack.handle_calendar_check_icon(i, value, type);

        if(type == 'view_type'){
            pannel_type = value
        }

        if(type == 'end_time'){
            // 日期
            if(value == 'today'){
                var now = new Date();
                startDate = endDate = now.getFullYear()+'-'+(now.getMonth()+1)+'-'+now.getDate();
            }else if(value == 'yesterday') {
                var yesterday = new Date(new Date().getTime()-24*60*60*1000);
                startDate = endDate = yesterday.getFullYear()+'-'+(yesterday.getMonth()+1)+'-'+yesterday.getDate();
            }else if(value == 'tomorrow') {
                var tomorrow = new Date(new Date().getTime()+24*60*60*1000);
                startDate = endDate = tomorrow.getFullYear()+'-'+(tomorrow.getMonth()+1)+'-'+tomorrow.getDate();
            }else{
                startDate = endDate = '';
            }
        }else if(type == 'user') {
            // 成员
            $('.tree-checkbox').removeClass('tree-checkbox1').removeClass('tree-checkbox2').addClass('tree-checkbox0');
            user_ids = [];
        }
        if(pannel_type == '1'){
            $('.endtime').hide();
            $('#teamViewer').show();
            $('#overTime').hide();
            $('#loadRate').hide();
            get_duration_data();
        }else if(pannel_type == '2'){
            $('.endtime').hide();
            $('#teamViewer').show();
            $('#overTime').hide();
            $('#loadRate').hide();
            get_time_data();
        }else{
            $('.endtime').show();
            $('#teamViewer').hide();
            $('#overTime').show();
            $('#loadRate').show();
            get_eer_data();
        }
    }

    // 组装项目过滤条件列表
    function filter_with_search(type, name, list_dom, is_hide) {
        var dom = '';
        var style = [];
        if(is_hide){
            style.push('display: none');
        }
        var dom_id = 'fc_filter_wrap_'+type;
        dom +=  '<div id="'+dom_id+'" class="ui multiple dropdown fc-filter-d" style="'+style.join(';')+'">' +
            name +
            '<i class="dropdown icon"></i>' +
            '<div class="menu">' +
            '<div class="ui icon userSearch input">' +
            '<i class="search icon"></i>' +
            '<input type="text" placeholder="Search...">' +
            '</div>' +
            '<div class="divider"></div>' +
            '<div class="header">' +
            filter_item_full_choice(type) +
            '</div>' +
            '<div class="scrolling menu">' +
            list_dom +
            '</div>' +
            '</div>' +
            '</div>';
        return dom;
    }

    // 组装截止时间过滤条件列表
    function filter_end_time() {
        // 1.今天
        // 2.昨天
        // 3.明天
        // 4.选定时间范围
        var dom = '';
        var time_list = [
            {id: 'today' , name: StrackLang['Today']},
            {id: 'yesterday' , name: StrackLang['Yesterday']},
            {id: 'tomorrow' , name: StrackLang['Tomorrow']}
        ];

        var time_dom = '';
        time_list.forEach(function (val) {
            time_dom += filter_item_base_dom('end_time', val);
        });

        dom += '<div class="ui dropdown fc-filter-d">日期<i class="dropdown icon"></i>' +
            '<div class="menu">' +
            '<div class="header">' +
            filter_item_full_choice('end_time') +
            '</div>' +
            '<div class="divider"></div>' +
            time_dom +
            '<div class="fc-endtime-bw">' +
            '<div class="from aign-left"><input id="fc_endtime_from" data-options="editable:false" /></div>'+
            '<span class="endtime" style="display: none;"><div class="symbol aign-left"> - </div>' +
            '<div class="to aign-left"><input id="fc_endtime_to" data-options="editable:false" /></div></span>' +
            '</div>' +
            '</div>' +
            '</div>';

        return dom;
    }

    //人员筛选搜索
    $('#fc_filter_wrap').on('input', '.userSearch input', function(){
        $('#usergroup').tree('doFilter', $(this).val());
    });

    // 菜单全选按钮
    function filter_item_full_choice(type) {
        var dom = '';
        dom += '<a href="javascript:;" class="clear" onclick="Strack.calendar_click_filter(event,this);" data-type="'+type+'" data-value="all">' +
            StrackLang['Clear_Selected'] +
            '</a>';
        return dom;
    }

    // 日程选项基础Dom
    function filter_item_base_dom(type, param) {
        var dom = '';
        dom += '<a href="javascript:;" class="item" onclick="Strack.calendar_click_filter(event,this);" data-type="'+type+'" data-value="'+param.id+'">' +
            '<i class="icon-left icon-unchecked"></i>' +
            param.name +
            '</a>';
        return dom;
    }

    //公共变量
    var teamViewer = document.getElementById("teamViewer");
    var overTime = document.getElementById("overTime");
    var loadRate = document.getElementById("loadRate");
    var myChart = echarts.init(teamViewer);
    var myChart1 = echarts.init(overTime);
    var myChart2 = echarts.init(loadRate);

    var categories = [];
    var categories1 = [];
    var categories2 = [];
    var colorList1 = ['#DF6262', '#C36464', '#B83D3D', '#E77676', '#E78383'];
    var colorList2 = ['#3B8686', '#3C7575', '#246E6E', '#54A4A4', '#5DA4A4'];
    var colorList3 = ['#DF9B62', '#C38F64', '#B8753D', '#E7A976', '#E7B083'];
    var colorList4 = ['#4FB34F', '#509C50', '#319331', '#65C665', '#70C670'];

    //时间视图
    var plan_data = [],
        actual_data = [];
    //时长视图
    var plan = [],
        actual = [],
        estimate = [],
        settlement = [];
    //能效视图
    var overtime_data = [],
        loadrate_data = [];

    /**
     * 渲染数据到图表--时间视图
     */
    function renderItem(params, api) {
        var categoryIndex = api.value(0);
        var start = api.coord([categoryIndex, api.value(2)]);
        var end = api.coord([categoryIndex, api.value(1)]);
        var width = api.size([0, 1])[0] * 0.2;
        var rectShape = echarts.graphic.clipRectByRect({
            x: start[0] - (1 - params.seriesIndex) * width,
            y: start[1],
            width: width,
            height: (end[1] - start[1])
        }, {
            x: params.coordSys.x,
            y: params.coordSys.y,
            width: params.coordSys.width,
            height: params.coordSys.height
        });

        return rectShape && {
            type: 'rect',
            shape: rectShape,
            style: api.style()
        };
    }

    /**
     * 渲染数据到图表--时长视图
     */
    function renderItem1(params, api) {
        var categoryIndex = api.value(0);
        var start = api.coord([categoryIndex, api.value(2)]);
        var end = api.coord([categoryIndex, api.value(1)]);
        var width = api.size([0, 1])[0] * 0.2;
        var rectShape = echarts.graphic.clipRectByRect({
            x: start[0] - (2 - params.seriesIndex) * width,
            y: start[1],
            width: width,
            height: end[1] - start[1]
        }, {
            x: params.coordSys.x,
            y: params.coordSys.y,
            width: params.coordSys.width,
            height: params.coordSys.height
        });

        return rectShape && {
            type: 'rect',
            shape: rectShape,
            style: api.style()
        };
    }

    /**
     * 将分钟数量转换为小时和分钟字符串
     */
    function toHourMinute(minutes) {
        var timeval = '';
        if (Math.floor(minutes / 60) > 0) {
            timeval = Math.floor(minutes / 60) + "小时";
        }
        if (Math.floor(minutes % 60) > 0) {
            timeval += Math.floor(minutes % 60) + "分钟";
        }
        return timeval;
    }

    /**
     * 时间戳转时间
     */
    function getTime(timestr) {
        var timestamp = new Date(timestr);
        return ('0' + timestamp.getHours()).slice(-2) + ':' + ('0' + timestamp.getMinutes()).slice(-2);
    }

    /**
     * 获取数据--时间视图
     */
    function get_time_data() {
        $.ajax({
            type: "POST",
            url: ReportPHP['getUserPlannedData'],
            data: {
                type: 2,
                start_date: startDate,
                end_date: endDate,
                user_ids: user_ids
            },
            dataType: "json",
            beforeSend: function () {
                $("#container").append(Strack.loading_dom("white", "", "report_toolbar"));
            },
            success: function (res) {
                if(res.status == 200) {
                    res = res.data;
                    categories = [];
                    plan_data = [];
                    actual_data = [];
                    echarts.util.each(res, function (item, index) {
                        categories.push(item.user_name);
                        //计划任务
                        echarts.util.each(item.plan_data, function (val, i) {
                            var startTime1 = val.start_time * 1000;
                            var endTime1 = val.end_time * 1000;
                            var duration1 = parseInt((val.end_time - val.start_time) / 60);
                            plan_data.push({
                                val_id: val.id,
                                project_id: val.project_id,
                                module_id: val.module_id,
                                name: val.name,
                                project_name: val.project_name,
                                value: [
                                    index,
                                    startTime1,
                                    endTime1,
                                    duration1
                                ],
                                itemStyle: {
                                    normal: {
                                        color: colorList1[i],
                                        borderColor: '#ffffff',
                                        borderWidth: 2,
                                        borderType: 'dotted'
                                    }
                                }
                            });
                        });
                        //实际任务
                        echarts.util.each(item.actual_data, function (val, i) {
                            var startTime2 = val.start_time * 1000;
                            var endTime2 = val.end_time * 1000;
                            var duration2 = parseInt((val.end_time - val.start_time) / 60);
                            actual_data.push({
                                val_id: val.id,
                                project_id: val.project_id,
                                module_id: val.module_id,
                                name: val.name,
                                project_name: val.project_name,
                                value: [
                                    index,
                                    startTime2,
                                    endTime2,
                                    duration2
                                ],
                                itemStyle: {
                                    normal: {
                                        color: colorList2[i],
                                        borderColor: '#ffffff',
                                        borderWidth: 2,
                                        borderType: 'dotted'
                                    }
                                }
                            });
                        });
                    });

                    //时间视图配置项
                    var option = {
                        tooltip: {
                            formatter: function (params) {
                                return params.marker  + params.data.project_name + '<br\>' +  params.name + '<br\>工时：' + toHourMinute(params.value[3]) + '<br\>开始时间：' + getTime(params.value[1]) + '<br\>结束时间：' + getTime(params.value[2]);
                            }
                        },
                        // toolbox: {
                        //     top: 10,
                        //     right: 150,
                        //     feature: {
                        //         myTool1: {
                        //             show: true,
                        //             title: '时长视图',
                        //             icon: 'path://M432.45,595.444c0,2.177-4.661,6.82-11.305,6.82c-6.475,0-11.306-4.567-11.306-6.82s4.852-6.812,11.306-6.812C427.841,588.632,432.452,593.191,432.45,595.444L432.45,595.444z M421.155,589.876c-3.009,0-5.448,2.495-5.448,5.572s2.439,5.572,5.448,5.572c3.01,0,5.449-2.495,5.449-5.572C426.604,592.371,424.165,589.876,421.155,589.876L421.155,589.876z M421.146,591.891c-1.916,0-3.47,1.589-3.47,3.549c0,1.959,1.554,3.548,3.47,3.548s3.469-1.589,3.469-3.548C424.614,593.479,423.062,591.891,421.146,591.891L421.146,591.891zM421.146,591.891',
                        //             onclick: function () {
                        //                 get_duration_data();
                        //             }
                        //         }
                        //     }
                        // },
                        title: {
                            text: '时间视图',
                            left: 'center'
                        },
                        dataZoom: [{
                            startValue: 0,
                            endValue: 4,
                            type: 'slider',
                            filterMode: 'weakFilter',
                            showDataShadow: false,
                            top: 415,
                            height: 10,
                            borderColor: 'transparent',
                            backgroundColor: '#e2e2e2',
                            handleIcon: 'M10.7,11.9H9.3c-4.9,0.3-8.8,4.4-8.8,9.4c0,5,3.9,9.1,8.8,9.4h1.3c4.9-0.3,8.8-4.4,8.8-9.4C19.5,16.3,15.6,12.2,10.7,11.9z M13.3,24.4H6.7v-1.2h6.6z M13.3,22H6.7v-1.2h6.6z M13.3,19.6H6.7v-1.2h6.6z', // jshint ignore:line
                            handleSize: 20,
                            handleStyle: {
                                shadowBlur: 6,
                                shadowOffsetX: 1,
                                shadowOffsetY: 2,
                                shadowColor: '#aaa'
                            },
                            labelFormatter: ''
                        }, {
                            type: 'inside',
                            filterMode: 'weakFilter'
                        }],
                        grid: {
                            height: 300
                        },
                        xAxis: {
                            data: categories,
                            axisLabel: {
                                interval: 0,
                                formatter:function(value){
                                    if(value){
                                        return value.split("").join("\n");
                                    }
                                }
                            }
                        },
                        yAxis: {
                            min: function (val) {
                                return val.min;
                            },
                            max: function (val) {
                                return val.max;
                            },
                            interval: 7200000,
                            type: 'time',
                            axisLabel: {
                                formatter: function (val) {
                                    return getTime(val);
                                }
                            }
                        },
                        color: ['#DF6262', '#3B8686'],
                        legend: {
                            top: 10,
                            left: 80,
                            itemWidth: 14,
                            data: [{
                                name: '计划工时',
                                icon: 'path://M1,7.76a7,7,0,1,0,7-7A7,7,0,0,0,1,7.76Zm5.6,1.9,5.2-5.25a.5.5,0,0,1,.71,0h0l.46.45A.15.15,0,0,1,13,5L7,11.11a.5.5,0,0,1-.71,0h0l-.42-.43h0L3,7.87a.15.15,0,0,1,0-.18l.46-.45a.5.5,0,0,1,.71,0h0L6.6,9.66Z'
                            }, {
                                name: '实际工时',
                                icon: 'path://M1,7.76a7,7,0,1,0,7-7A7,7,0,0,0,1,7.76Zm5.6,1.9,5.2-5.25a.5.5,0,0,1,.71,0h0l.46.45A.15.15,0,0,1,13,5L7,11.11a.5.5,0,0,1-.71,0h0l-.42-.43h0L3,7.87a.15.15,0,0,1,0-.18l.46-.45a.5.5,0,0,1,.71,0h0L6.6,9.66Z'
                            }]
                        },
                        series: [{
                            name: '计划工时',
                            type: 'custom',
                            renderItem: renderItem,
                            encode: {
                                x: 0,
                                y: [1, 2]
                            },
                            data: plan_data
                        }, {
                            name: '实际工时',
                            type: 'custom',
                            renderItem: renderItem,
                            encode: {
                                x: 0,
                                y: [1, 2]
                            },
                            data: actual_data
                        }]
                    };

                    if (option && typeof option === "object") {
                        myChart.setOption(option, true);
                        myChart.on('dblclick', function (params) {
                            if(params.data){
                                var url = Strack.details_url(params.data, params.data.val_id);
                                window.open(url);
                            }
                        });
                    }
                }
                $('#st-load_report_toolbar').remove();
            }
        });
    }

    /**
     * 获取数据--时长视图
     */
    function get_duration_data() {
        $.ajax({
            type: "POST",
            url: ReportPHP['getUserPlannedData'],
            data: {
                start_date: startDate,
                end_date: endDate,
                user_ids: user_ids
            },
            dataType: "json",
            beforeSend: function () {
                $("#container").append(Strack.loading_dom("white", "", "report_toolbar"));
            },
            success: function (res) {
                if(res.status == 200){
                    res = res.data;
                    categories = [];
                    plan = [];
                    actual = [];
                    estimate = [];
                    settlement = [];
                    echarts.util.each(res, function (item, index) {
                        categories.push(item.user_name);
                        var baseTime = 0,
                            baseTime1 = 0,
                            baseTime2 = 0,
                            baseTime3 = 0;

                        echarts.util.each(item.plan_data, function (val, i) {
                            //计划工时
                            plan.push({
                                val_id: val.id,
                                project_id: val.project_id,
                                module_id: val.module_id,
                                name: val.name,
                                project_name: val.project_name,
                                value: [
                                    index,
                                    baseTime,
                                    baseTime += Strack.translate_timespinner_val(val.plan),
                                    Strack.translate_timespinner_val(val.plan)
                                ],
                                itemStyle: {
                                    normal: {
                                        color: colorList1[i],
                                        borderColor: '#ffffff',
                                        borderWidth: 2,
                                        borderType: 'dotted'
                                    }
                                }
                            });
                        });
                        echarts.util.each(item.actual_data, function (val, i) {
                            //实际工时
                            actual.push({
                                val_id: val.id,
                                project_id: val.project_id,
                                module_id: val.module_id,
                                name: val.name,
                                project_name: val.project_name,
                                value: [
                                    index,
                                    baseTime1,
                                    baseTime1 += Strack.translate_timespinner_val(val.actual),
                                    Strack.translate_timespinner_val(val.actual)
                                ],
                                itemStyle: {
                                    normal: {
                                        color: colorList2[i],
                                        borderColor: '#ffffff',
                                        borderWidth: 2,
                                        borderType: 'dotted'
                                    }
                                }
                            });
                        });
                        echarts.util.each(item.estimate_data, function (val, i) {
                            //预估工时
                            estimate.push({
                                val_id: val.id,
                                project_id: val.project_id,
                                module_id: val.module_id,
                                name: val.name,
                                project_name: val.project_name,
                                value: [
                                    index,
                                    baseTime2,
                                    baseTime2 += Strack.translate_timespinner_val(val.estimate),
                                    Strack.translate_timespinner_val(val.estimate),
                                ],
                                itemStyle: {
                                    normal: {
                                        color: colorList3[i],
                                        borderColor: '#ffffff',
                                        borderWidth: 2,
                                        borderType: 'dotted'
                                    }
                                }
                            });
                        });
                        echarts.util.each(item.settlement_data, function (val, i) {
                            //结算工时
                            settlement.push({
                                val_id: val.id,
                                project_id: val.project_id,
                                module_id: val.module_id,
                                name: val.name,
                                project_name: val.project_name,
                                value: [
                                    index,
                                    baseTime3,
                                    baseTime3 += Strack.translate_timespinner_val(val.settlement),
                                    Strack.translate_timespinner_val(val.settlement)
                                ],
                                itemStyle: {
                                    normal: {
                                        color: colorList4[i],
                                        borderColor: '#ffffff',
                                        borderWidth: 2,
                                        borderType: 'dotted'
                                    }
                                }
                            });
                        });
                    });

                    //时长视图配置项
                    var option = {
                        tooltip: {
                            formatter: function (params) {
                                return params.marker + params.data.project_name + '<br\>' + params.name + '<br\>工时：' + toHourMinute(params.value[3]);
                            }
                        },
                        // toolbox: {
                        //     top: 10,
                        //     right: 150,
                        //     feature: {
                        //         myTool2: {
                        //             show: true,
                        //             title: '时间视图',
                        //             icon: 'path://M432.45,595.444c0,2.177-4.661,6.82-11.305,6.82c-6.475,0-11.306-4.567-11.306-6.82s4.852-6.812,11.306-6.812C427.841,588.632,432.452,593.191,432.45,595.444L432.45,595.444z M421.155,589.876c-3.009,0-5.448,2.495-5.448,5.572s2.439,5.572,5.448,5.572c3.01,0,5.449-2.495,5.449-5.572C426.604,592.371,424.165,589.876,421.155,589.876L421.155,589.876z M421.146,591.891c-1.916,0-3.47,1.589-3.47,3.549c0,1.959,1.554,3.548,3.47,3.548s3.469-1.589,3.469-3.548C424.614,593.479,423.062,591.891,421.146,591.891L421.146,591.891zM421.146,591.891',
                        //             onclick: function () {
                        //                 get_time_data();
                        //             }
                        //         }
                        //     }
                        // },
                        title: {
                            text: '时长视图',
                            left: 'center'
                        },
                        dataZoom: [{
                            startValue: 0,
                            endValue: 4,
                            type: 'slider',
                            filterMode: 'weakFilter',
                            showDataShadow: false,
                            top: 415,
                            height: 10,
                            borderColor: 'transparent',
                            backgroundColor: '#e2e2e2',
                            handleIcon: 'M10.7,11.9H9.3c-4.9,0.3-8.8,4.4-8.8,9.4c0,5,3.9,9.1,8.8,9.4h1.3c4.9-0.3,8.8-4.4,8.8-9.4C19.5,16.3,15.6,12.2,10.7,11.9z M13.3,24.4H6.7v-1.2h6.6z M13.3,22H6.7v-1.2h6.6z M13.3,19.6H6.7v-1.2h6.6z', // jshint ignore:line
                            handleSize: 20,
                            handleStyle: {
                                shadowBlur: 6,
                                shadowOffsetX: 1,
                                shadowOffsetY: 2,
                                shadowColor: '#aaa'
                            },
                            labelFormatter: ''
                        }, {
                            type: 'inside',
                            filterMode: 'weakFilter'
                        }],
                        grid: {
                            height: 300
                        },
                        xAxis: {
                            data: categories,
                            axisLabel: {
                                interval: 0,
                                formatter:function(value){
                                    if(value){
                                        return value.split("").join("\n");
                                    }
                                }
                            }
                        },
                        yAxis: {
                            min: 0,
                            max: function (val) {
                                return val.max + 120;
                            },
                            minInterval: 120,
                            axisLabel: {
                                formatter: function (val) {
                                    if((val%60)!=0){
                                        return (val / 60).toFixed(1) + '小时';
                                    }else{
                                        return (val / 60) + '小时';
                                    }
                                }
                            }
                        },
                        color: ['#DF6262', '#3B8686', '#DF9B62', '#4FB34F'],
                        legend: {
                            top: 10,
                            left: 80,
                            itemWidth: 14,
                            data: [{
                                name: '计划工时',
                                icon: 'path://M1,7.76a7,7,0,1,0,7-7A7,7,0,0,0,1,7.76Zm5.6,1.9,5.2-5.25a.5.5,0,0,1,.71,0h0l.46.45A.15.15,0,0,1,13,5L7,11.11a.5.5,0,0,1-.71,0h0l-.42-.43h0L3,7.87a.15.15,0,0,1,0-.18l.46-.45a.5.5,0,0,1,.71,0h0L6.6,9.66Z'
                            }, {
                                name: '实际工时',
                                icon: 'path://M1,7.76a7,7,0,1,0,7-7A7,7,0,0,0,1,7.76Zm5.6,1.9,5.2-5.25a.5.5,0,0,1,.71,0h0l.46.45A.15.15,0,0,1,13,5L7,11.11a.5.5,0,0,1-.71,0h0l-.42-.43h0L3,7.87a.15.15,0,0,1,0-.18l.46-.45a.5.5,0,0,1,.71,0h0L6.6,9.66Z'
                            }, {
                                name: '预估工时',
                                icon: 'path://M1,7.76a7,7,0,1,0,7-7A7,7,0,0,0,1,7.76Zm5.6,1.9,5.2-5.25a.5.5,0,0,1,.71,0h0l.46.45A.15.15,0,0,1,13,5L7,11.11a.5.5,0,0,1-.71,0h0l-.42-.43h0L3,7.87a.15.15,0,0,1,0-.18l.46-.45a.5.5,0,0,1,.71,0h0L6.6,9.66Z'
                            }, {
                                name: '结算工时',
                                icon: 'path://M1,7.76a7,7,0,1,0,7-7A7,7,0,0,0,1,7.76Zm5.6,1.9,5.2-5.25a.5.5,0,0,1,.71,0h0l.46.45A.15.15,0,0,1,13,5L7,11.11a.5.5,0,0,1-.71,0h0l-.42-.43h0L3,7.87a.15.15,0,0,1,0-.18l.46-.45a.5.5,0,0,1,.71,0h0L6.6,9.66Z'
                            }]
                        },
                        series: [{
                            name: '计划工时',
                            type: 'custom',
                            renderItem: renderItem1,
                            encode: {
                                x: 0,
                                y: [1, 2]
                            },
                            data: plan
                        }, {
                            name: '实际工时',
                            type: 'custom',
                            renderItem: renderItem1,
                            encode: {
                                x: 0,
                                y: [1, 2]
                            },
                            data: actual
                        }, {
                            name: '预估工时',
                            type: 'custom',
                            renderItem: renderItem1,
                            encode: {
                                x: 0,
                                y: [1, 2]
                            },
                            data: estimate
                        }, {
                            name: '结算工时',
                            type: 'custom',
                            renderItem: renderItem1,
                            encode: {
                                x: 0,
                                y: [1, 2]
                            },
                            data: settlement
                        }]
                    };

                    if (option && typeof option === "object") {
                        myChart.setOption(option, true);
                        myChart.on('dblclick', function (params) {
                            if(params.data){
                                var url = Strack.details_url(params.data, params.data.val_id);
                                window.open(url);
                            }
                        });
                    }
                }
                $('#st-load_report_toolbar').remove();
            }
        });
    }

    /**
     * 获取数据--能效视图
     */
    function get_eer_data() {
        $.ajax({
            type: "POST",
            url: ReportPHP['getUserPlannedData'],
            data: {
                type: 4,
                start_date: startDate,
                end_date: endDate,
                user_ids: user_ids
            },
            dataType: "json",
            beforeSend: function () {
                $("#container").append(Strack.loading_dom("white", "", "report_toolbar"));
            },
            success: function (res) {
                if(res.status == 200){
                    res = res.data;
                    categories1 = [];
                    categories2 = [];
                    overtime_data = [];
                    loadrate_data = [];
                    echarts.util.each(res.overtimeData, function (item, index) {
                        categories1.push(item.user_name);
                        overtime_data.push((item.overtime_working_hours/60).toFixed(2));
                    });
                    echarts.util.each(res.loadRateData, function (item, index) {
                        categories2.push(item.user_name);
                        loadrate_data.push(item.loadRate);
                    });

                    //超时情况视图配置项
                    var option1 = {
                        title: {
                            text: '超时情况',
                            left: 'center'
                        },
                        dataZoom: [{
                            startValue: 0,
                            endValue: 9,
                            type: 'slider',
                            filterMode: 'weakFilter',
                            showDataShadow: false,
                            top: 415,
                            height: 10,
                            borderColor: 'transparent',
                            backgroundColor: '#e2e2e2',
                            handleIcon: 'M10.7,11.9H9.3c-4.9,0.3-8.8,4.4-8.8,9.4c0,5,3.9,9.1,8.8,9.4h1.3c4.9-0.3,8.8-4.4,8.8-9.4C19.5,16.3,15.6,12.2,10.7,11.9z M13.3,24.4H6.7v-1.2h6.6z M13.3,22H6.7v-1.2h6.6z M13.3,19.6H6.7v-1.2h6.6z', // jshint ignore:line
                            handleSize: 20,
                            handleStyle: {
                                shadowBlur: 6,
                                shadowOffsetX: 1,
                                shadowOffsetY: 2,
                                shadowColor: '#aaa'
                            },
                            labelFormatter: ''
                        }, {
                            type: 'inside',
                            filterMode: 'weakFilter'
                        }],
                        grid: {
                            height: 300
                        },
                        xAxis: {
                            data: categories1,
                            axisLabel: {
                                interval: 0,
                                formatter:function(value){
                                    if(value){
                                        return value.split("").join("\n");
                                    }
                                }
                            },
                            triggerEvent:true
                        },
                        yAxis: {
                            type: 'value',
                            splitNumber: 10,
                            axisLabel: {
                                formatter: function (val) {
                                    return val+'小时';
                                }
                            }
                        },
                        series: [{
                            data: overtime_data,
                            name: '超时时间',
                            type: 'bar',
                            label: {
                                normal: {
                                    show: true,
                                    position: 'top'
                                }
                            }
                        }]
                    };

                    //负荷率视图配置项
                    var option2 = {
                        title: {
                            text: '负荷情况',
                            left: 'center'
                        },
                        dataZoom: [{
                            startValue: 0,
                            endValue: 9,
                            type: 'slider',
                            filterMode: 'weakFilter',
                            showDataShadow: false,
                            top: 415,
                            height: 10,
                            borderColor: 'transparent',
                            backgroundColor: '#e2e2e2',
                            handleIcon: 'M10.7,11.9H9.3c-4.9,0.3-8.8,4.4-8.8,9.4c0,5,3.9,9.1,8.8,9.4h1.3c4.9-0.3,8.8-4.4,8.8-9.4C19.5,16.3,15.6,12.2,10.7,11.9z M13.3,24.4H6.7v-1.2h6.6z M13.3,22H6.7v-1.2h6.6z M13.3,19.6H6.7v-1.2h6.6z', // jshint ignore:line
                            handleSize: 20,
                            handleStyle: {
                                shadowBlur: 6,
                                shadowOffsetX: 1,
                                shadowOffsetY: 2,
                                shadowColor: '#aaa'
                            },
                            labelFormatter: ''
                        }, {
                            type: 'inside',
                            filterMode: 'weakFilter'
                        }],
                        grid: {
                            height: 300
                        },
                        xAxis: {
                            data: categories2,
                            axisLabel: {
                                interval: 0,
                                formatter:function(value){
                                    if(value){
                                        return value.split("").join("\n");
                                    }
                                }
                            },
                            triggerEvent:true
                        },
                        yAxis: {
                            type: 'value'
                        },
                        series: [{
                            data: loadrate_data,
                            name: '负荷率',
                            type: 'line',
                            label: {
                                normal: {
                                    show: true,
                                    position: 'top'
                                }
                            }
                        }]
                    };

                    if (option1 && typeof option1 === "object" && option2 && typeof option2 === "object") {
                        myChart1.setOption(option1, true);
                        myChart1.on('click', function (params) {
                            if(params.componentType == "xAxis"){
                                alert("单击了"+params.event.target.anid+"x轴标签");
                            }
                        });
                        myChart2.setOption(option2, true);
                        myChart2.on('click', function (params) {
                            if(params.componentType == "xAxis"){
                                alert("单击了"+params.event.target.anid+"x轴标签");
                            }
                        });
                    }
                }
                $('#st-load_report_toolbar').remove();
            }
        });
    }
    
    get_duration_data();
});
