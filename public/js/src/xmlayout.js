// version 2.4.0
//author : xpzsoft myy
// __fun() 内部函数; fun() API函数
$.xmlayout = {
	pop_zindex : 0,
	xmparams : {},
	panel_ids : [],
	panel_tree_list_i : [],
	resize_fun : [],
	timer : 10000,
	play_panel : [],
	anim_wd : null,
	anim_tf : false,
	playbottom : null,
	playheader : null,
	playfi : false,
	playfn : false,
	LANG : null,
	change_panel : null,//即将换位置的panel
	pages : [],
	layout : function(root_panel){
		if(root_panel == undefined || root_panel == null)
			throw "XMlayout info: param 'panel' in function $.xmlayout init() is null!";

		if($.xmlayout.__isNaN(root_panel.height()) || $.xmlayout.__isNaN(root_panel.width()) || root_panel.height() <= 0 || root_panel.width() <= 0)
			throw "XMlayout info: can't get the width and height of 'panel'";

		var xmlayout = {
			root_panel : root_panel,//布局面板

			panel_data : null,
			panel_tree : null,
			panel_tree_list : [],

			random_class : {
				tool_random_class:null,
				f_tool_random_class:null,
				targ_panel_random_class : null,
			    targ_title_random_class : null,
			    f_targ_panel_random_class : null,
			    f_targ_title_random_class : null,
			},

			panel_arr : [],
			panel_offset : null,
			drag_bar_unit : 4,
			drag_bar_color : "black",
			pop_count : [],
			popped_count : 0,
			RPID : {ZOOM_IN : 0, ZOOM_OUT : 1, FULLSCREEN_RESIZE : 2, POPPED_BERTH : 3, FULLSCREEN_RESIZE1 : 4, CHANGE_PANEL : 5},

			POPWH : {width : 800, height : 600},
			RPCOLOR : "#CFCFCF",
			resize_fun : [],

			pborder : "no",
			dragfix : 100,

			iszoom : false,
			ispop : false,
			isradius : true,
			isindexshow : false,
			playuseable : true,

			init : function(obj){
				var obj_this = this;
				var data = obj.data;
				var drag_bar_unit = obj.drag_bar_unit;
				var drag_bar_color = obj.drag_bar_color;
				var lang = obj.lang;
				var popw = obj.popw;
				var poph = obj.poph;
				var timer = obj.timer;
				var playuseable = obj.playuseable;
				var isborder = obj.isborder;
				var isradius = obj.isradius;
				var pborder = obj.pborder;
				var isindexshow = obj.isindexshow;
				var dragfix = obj.dragfix;
				var isdestroy = obj.isdestroy;

				try{
					if(data == undefined || data == null)
						throw "XMlayout info: param 'data' in function $.xmlayout init() is null!";

					if(drag_bar_unit != undefined && drag_bar_unit != null){
						if($.xmlayout.__isNaN(drag_bar_unit))
							throw "XMlayout info: param 'drag_bar_unit' in function $.xmlayout init() is not a number!";
						else if(drag_bar_unit < 0)
							throw "XMlayout info: param 'drag_bar_unit' < 0 in function $.xmlayout init()!";
						else
							obj_this.drag_bar_unit = drag_bar_unit;
					}

					if(drag_bar_color != undefined && drag_bar_color != null){
						if(typeof drag_bar_color != "string")
							throw "XMlayout info: param 'drag_bar_color' in function $.xmlayout init() is not a string!";
						else
							obj_this.drag_bar_color = drag_bar_color;
					}

					if(lang != undefined && lang != null){
						if(typeof lang != "string")
							throw "XMlayout info: param 'drag_bar_color' in function $.xmlayout init() is not a string!";
					}
					else
						lang = "chs";

					$.xmlayout.LANG = $.xmlayout.__getLang(lang);

					if(popw != undefined && popw != null){
						if($.xmlayout.__isNaN(popw))
							throw "XMlayout info: param 'popw' in function $.xmlayout init() is not a number!";
						else if(popw < 1)
							throw "XMlayout info: param 'popw' < 1 in function $.xmlayout init()!";
						else
							obj_this.POPWH.width = popw;
					}

					if(poph != undefined && poph != null){
						if($.xmlayout.__isNaN(poph))
							throw "XMlayout info: param 'poph' in function $.xmlayout init() is not a number!";
						else if(poph < 1)
							throw "XMlayout info: param 'poph' < 1 in function $.xmlayout init()!";
						else
							obj_this.POPWH.height = poph;
					}

					if(timer != undefined && timer != null){
						if($.xmlayout.__isNaN(timer))
							throw "XMlayout info: param 'timer' in function $.xmlayout init() is not a number!";
						else
							$.xmlayout.timer = timer;
					}

					if(dragfix != undefined && dragfix != null){
						if($.xmlayout.__isNaN(dragfix))
							throw "XMlayout info: param 'dragfix' in function $.xmlayout init() is not a number!";
						else
							obj_this.dragfix = dragfix;
					}

					if(playuseable != undefined && playuseable != null){
						if(typeof playuseable != "boolean")
							throw "XMlayout info: param 'playuseable' in function $.xmlayout init() is not a boolean!";
						else
							obj_this.playuseable = playuseable;
					}

					if(isborder != undefined && isborder != null){
						if(typeof isborder != "boolean" && !$.isArray(isborder))
							throw "XMlayout info: param 'isborder' in function $.xmlayout init() is not a boolean or object!";
						if($.isArray(isborder) && isborder.length != 4)
							throw "XMlayout info: param 'isborder' in function $.xmlayout init() must be a array of length 4!";
						if($.isArray(isborder))
							$.each(isborder, function(i, v){
								if(typeof v != "boolean"){
									throw "XMlayout info: param 'isborder' in function $.xmlayout init() must be a array of boolean!";
								}
							});
					}
					else{
						isborder = true;
					}

					if(isradius != undefined && isradius != null){
						if(typeof isradius != "boolean")
							throw "XMlayout info: param 'isradius' in function $.xmlayout init() is not a boolean!";
						obj_this.isradius = isradius;
					}
					else{
						obj_this.isradius = true;
					}

					if(isdestroy != undefined && isdestroy != null){
						if(typeof isdestroy != "boolean")
							throw "XMlayout info: param 'isdestroy' in function $.xmlayout init() is not a boolean!";
					}
					else{
						isdestroy = true;
					}

					if(isindexshow != undefined && isindexshow != null){
						if(typeof isindexshow != "boolean")
							throw "XMlayout info: param 'isindexshow' in function $.xmlayout init() is not a boolean!";
						obj_this.isindexshow = isindexshow;
					}
					else{
						obj_this.isindexshow = false;
					}

					if(obj_this.root_panel.children().length > 0){
						obj_this.root_panel.children().remove();
					}

					if(pborder != undefined && pborder != null){
						obj_this.pborder = pborder;
					}
				}
				catch(err){
					throw err;
				}

				var panel = $('<div style="width:100%; height:100%;"></div>');
				panel.prop("isDestory", isdestroy);
				obj_this.root_panel.append(panel);

				var rdx = Math.random().toString().substr(2);
				obj_this.random_class.tool_random_class = "xm" + rdx;
				obj_this.random_class.f_tool_random_class = "." + obj_this.random_class.tool_random_class;
				obj_this.random_class.targ_panel_random_class = "targ_panel" + rdx;
				obj_this.random_class.f_targ_panel_random_class = "." + obj_this.random_class.targ_panel_random_class;
				obj_this.random_class.targ_title_random_class = "targ_title" + rdx;
				obj_this.random_class.f_targ_title_random_class = "." + obj_this.random_class.targ_title_random_class;
				obj_this.panel_offset = {left:0, top:0};
				panel.css("overflow", "hidden");
				panel.css("background-color", obj_this.drag_bar_color);
				if(typeof isborder == "boolean" && isborder)
					panel.css("border", obj_this.drag_bar_unit + "px solid " + obj_this.drag_bar_color);
				else if($.isArray(isborder)){
					if(isborder[0])
						panel.css("border-top", obj_this.drag_bar_unit + "px solid " + obj_this.drag_bar_color);
					if(isborder[1])
						panel.css("border-right", obj_this.drag_bar_unit + "px solid " + obj_this.drag_bar_color);
					if(isborder[2])
						panel.css("border-bottom", obj_this.drag_bar_unit + "px solid " + obj_this.drag_bar_color);
					if(isborder[3])
						panel.css("border-left", obj_this.drag_bar_unit + "px solid " + obj_this.drag_bar_color);
				}
				panel.prop({zoom : false, popped : 0, id : "id" + Math.random().toString().substr(2)});
				xmid = parseInt(Math.random() * 1000000);
				obj_this.panel_tree = {panel : panel, lcpanel : null, rcpanel : null, parent : null, ref_id : panel.prop("id")};
				obj_this.panel_tree_list[panel.prop("id")] = obj_this.panel_tree;
				obj_this.__createLayout(data, panel, 1, obj_this.panel_tree);//绘制布局
				obj_this.__changePanelLayout();//变换布局

				$.xmlayout.__clearInvalidObjects();
				$.xmlayout.panel_tree_list_i.push(obj_this.panel_tree);//保证当前document下所有xm布局panel都可以添加到动画中
				$.xmlayout.panel_ids.push(panel.prop("id"));//保证当前document下所有xm布局panel都可以添加到动画中

				if(obj_this.playuseable){
					$.xmlayout.addPlay(obj_this.panel_arr);
				}
				else{
					$.xmlayout.playheader.prop("ap", $.xmlayout.play_panel.length);
					$.xmlayout.playheader.prop("np", 1);
					$.xmlayout.playheader.find("span").text("1/" + $.xmlayout.play_panel.length);
				}
			},
			__createLayout : function(data, panel, deep, panel_tree){
				obj_this = this;
				var div1, div2;
				if(data.lr.length == 2){
					if(!$.xmlayout.__isNaN(data.lr[0].value) && !$.xmlayout.__isNaN(data.lr[1].value)){
						var mv = data.lr[0].value + data.lr[1].value;
						var mv1 = parseInt(data.lr[0].value * 100 / mv);
						var mv2 = 100 - mv1;
						div1 = $('<div style="width:' + mv1 + '%; height:100%; float:left;"></div>');
						div2 = $('<div style="width:' + mv2 + '%; height:100%; float:left;"></div>');
					}
					else if(!$.xmlayout.__isNaN(data.lr[0].value) && $.xmlayout.__isPx(data.lr[1].value)){
						div1 = $('<div style="width:calc(100% - ' + data.lr[1].value + '); height:100%; float:left;"></div>');
						div2 = $('<div style="width:' + data.lr[1].value + '; height:100%; float:left;"></div>');
					}
					else if($.xmlayout.__isPx(data.lr[0].value)){
						div1 = $('<div style="width:' + data.lr[0].value + '; height:100%; float:left;"></div>');
						div2 = $('<div style="width:calc(100% - ' + data.lr[0].value + '); height:100%; float:left;"></div>');
					}
					div1.prop({zoom : false, popped : 0, pvalue:"width", key:1, ovalue : mv1});
					div2.prop({zoom : false, popped : 0, pvalue:"width", key:2, ovalue : mv2});
					var div21 = $('<div style="width:' + obj_this.drag_bar_unit + 'px; height:100%; background-color:'+ obj_this.drag_bar_color +'; float:left; overflow:hidden;"></div>');
					div21.prop({key:2});
					var div211 = $('<div style="width:100%; height:100%; opacity:0;"></div>');
					div211.prop({pvalue:"width"});
					div21.append(div211);
					if(data.lr[0].resize && data.lr[1].resize){
						div21.css("cursor", "w-resize");
						//div211.draggable({ axis: "x" });
						//div211.css("background-color", "red");
						$.xmlayout.div_drag(div211, "axis-x");
						obj_this.__addListener(div211);
					}
					var div22 = $('<div style="width: -moz-calc(100% - ' + obj_this.drag_bar_unit + 'px);width: -webkit-calc(100% - ' + obj_this.drag_bar_unit + 'px);width: calc(100% - ' + obj_this.drag_bar_unit + 'px); height:100%; float:left;" ></div>');
					div22.prop({key:1});
					div2.append(div21);
					div2.append(div22);
					panel.append(div1);
					panel.append(div2);
					div1.prop("id", "id" + Math.random().toString().substr(2));
					div2.prop("id", "id" + Math.random().toString().substr(2));
					panel_tree.lcpanel = {panel : div1, lcpanel : null, rcpanel : null, parent : panel_tree, ref_id : obj_this.panel_tree.ref_id};
					panel_tree.rcpanel = {panel : div2, lcpanel : null, rcpanel : null, parent : panel_tree, ref_id : obj_this.panel_tree.ref_id};
					obj_this.panel_tree_list[div1.prop("id")] = panel_tree.lcpanel;
					obj_this.panel_tree_list[div2.prop("id")] = panel_tree.rcpanel;
					$.xmlayout.panel_tree_list_i.push(panel_tree.lcpanel);
					$.xmlayout.panel_tree_list_i.push(panel_tree.rcpanel);
					obj_this.__createLayout(data.lr[0], div1, ++deep, panel_tree.lcpanel);
					obj_this.__createLayout(data.lr[1], div22, deep, panel_tree.rcpanel);

				}
				else if(data.ud.length == 2){
					if(!$.xmlayout.__isNaN(data.ud[0].value) && !$.xmlayout.__isNaN(data.ud[1].value)){
						var mv = data.ud[0].value + data.ud[1].value;
						var mv1 = parseInt(data.ud[0].value * 100 / mv);
						var mv2 = 100 - mv1;
						div1 = $('<div style="height:' + mv1 + '%; width:100%;"></div>');
						div2 = $('<div style="height:' + mv2 + '%; width:100%;;"></div>');
					}
					else if(!$.xmlayout.__isNaN(data.ud[0].value) && $.xmlayout.__isPx(data.ud[1].value)){
						div1 = $('<div style="height:calc(100% - ' + data.ud[1].value + '); width:100%;"></div>');
						div2 = $('<div style="height:' + data.ud[1].value + '; width:100%;"></div>');
					}
					else if($.xmlayout.__isPx(data.ud[0].value)){
						div1 = $('<div style="height:' + data.ud[0].value + '; width:100%;"></div>');
						div2 = $('<div style="height:calc(100% - ' + data.ud[0].value + '); width:100%;"></div>');
					}
					div1.prop({zoom : false, popped : 0, pvalue:"height", key:1, ovalue : mv1});
					div2.prop({zoom : false, popped : 0, pvalue:"height", key:2, ovalue : mv2});
					var div21 = $('<div style="height:' + obj_this.drag_bar_unit + 'px; width:100%; background-color:'+ obj_this.drag_bar_color +'; overflow:hidden;"></div>');
					div21.prop({key:2});
					var div211 = $('<div style="width:100%; height:100%; opacity:0;"></div>');
					div211.prop({pvalue:"height"});
					div21.append(div211);
					if(data.ud[0].resize && data.ud[1].resize){
						div21.css("cursor", "n-resize");
						//div211.draggable({ axis: "y" });
						//div211.css("background-color", "green");
						$.xmlayout.div_drag(div211, "axis-y");
						obj_this.__addListener(div211);
					}
					var div22 = $('<div style="width:100%; height: -moz-calc(100% - ' + obj_this.drag_bar_unit + 'px); height: -webkit-calc(100% - ' + obj_this.drag_bar_unit + 'px); height: calc(100% - ' + obj_this.drag_bar_unit + 'px);"></div>');
					div22.prop({key:1});
					div2.append(div21);
					div2.append(div22);
					panel.append(div1);
					panel.append(div2);
					div1.prop("id", "id" + Math.random().toString().substr(2));
					div2.prop("id", "id" + Math.random().toString().substr(2));
					panel_tree.lcpanel = {panel : div1, lcpanel : null, rcpanel : null, parent : panel_tree, ref_id : obj_this.panel_tree.ref_id};
					panel_tree.rcpanel = {panel : div2, lcpanel : null, rcpanel : null, parent : panel_tree, ref_id : obj_this.panel_tree.ref_id};
					obj_this.panel_tree_list[div1.prop("id")] = panel_tree.lcpanel;
					obj_this.panel_tree_list[div2.prop("id")] = panel_tree.rcpanel;
					$.xmlayout.panel_tree_list_i.push(panel_tree.lcpanel);
					$.xmlayout.panel_tree_list_i.push(panel_tree.rcpanel);
					obj_this.__createLayout(data.ud[0], div1, ++deep, panel_tree.lcpanel);
					obj_this.__createLayout(data.ud[1], div22, deep, panel_tree.rcpanel);
				}
				else{
					if(obj_this.isradius)
						panel.css("border-radius", "4px");

					panel.css({backgroundColor:"white", boxSizing : "border-box", border : obj_this.pborder});

					if(panel_tree.parent == null){
						data.rp = false;
						data.tb = false;
					}
					obj_this.__addRightPanel(panel, data);
				}
			},
			__changePanelLayout : function(){
				var obj_this = this;
				var arr = [];
				$.each(obj_this.getPanels(), function(i,v){
					arr.push(v.parent());
					v.prop({seq : (i+1)});
				});

				$.each(arr, function(i,v){
					if(v.prop("mdata")){
						var pos_mark = v.prop("mdata").pos_mark;
						if(pos_mark.opid !== pos_mark.npid){
							v.append(obj_this.getPanels(pos_mark.npid));
						}
					}
				});

			},
			__addListener : function(panel){
				var pre_pos = {x:0,y:0};
				var dx, nwidth, pvalue;
				var pleft, pright;
				panel.on("dragstart", function(e1, e){
					pre_pos.x = e.pageX;
					pre_pos.y = e.pageY;
					pleft = $(this).parent().parent().siblings();
					pright = $(this).parent().parent();
					pvalue = $(this).prop("pvalue");
				});


				panel.on("drag", function(e1, e){
					if(pvalue == "width"){
						dx = e.pageX - pre_pos.x;
						pre_pos.x = e.pageX;
					}
					else{
						dx = e.pageY - pre_pos.y;
						pre_pos.y = e.pageY;
					}

					var xx = parseFloat(pright.parent().css(pvalue));
					if(dx > 0){
						nwidth = parseFloat(pright.css(pvalue));
						if(nwidth <= obj_this.dragfix)
							return;
						var v1 = (nwidth - dx) * 100 / xx;
						pright.css(pvalue, v1 + "%");
						pleft.css(pvalue, (100 - v1) + "%");

					}
					else{
						nwidth = parseFloat(pleft.css(pvalue));
						if(nwidth <= obj_this.dragfix)
							return;
						var v1 = (nwidth + dx) * 100 / xx;
						pleft.css(pvalue, v1 + "%");
						pright.css(pvalue, (100 - v1) + "%");
					}
				});
				panel.on("dragstop", function(e1, e){
					if(pvalue == "width")
						$(this).css("left", "0px");
					else
						$(this).css("top", "0px");
					var width = parseInt($(this).parent().parent().parent().css(pvalue));
					var v1 = (parseInt(pleft.css(pvalue)) * 100.0 / width).toFixed(4);
					var v3 = 100 - v1;
					pleft.css(pvalue, v1 + "%");
					pright.css(pvalue, v3 + "%");
					pleft.prop("ovalue", v1);
					pright.prop("ovalue", v3);

					for(var i = 0; i < obj_this.resize_fun.length; i++){
						if(obj_this.resize_fun[i].params != null)
							obj_this.resize_fun[i].fun(obj_this.resize_fun[i].params);
						else
							obj_this.resize_fun[i].fun();
					}
				});

				panel.parent().mouseover(function(){
					$(this).css("opacity", 0.5);
				});
				panel.parent().mouseout(function(){
					$(this).css("opacity", 1.0);
				});
			},
			__load : function(panel, url, data, fun, title, style){
				var obj_this = this;
				var tgd = panel;
				if(title != undefined && title != null){
					var div = panel.parent().find(obj_this.random_class.f_targ_title_random_class);
					if(div[0]){
						div.children().remove();
						if(style != undefined && style != null && style.bgcolor != undefined && style.bgcolor!= null)
							div.css("background-color", style.bgcolor);
						if(style != undefined && style != null && style.pos == "center"){
							div.append($('<center><span class="xmlayout_tt" style="font-size:15px; margin-left: 5px;">' + title + '</label></center>'));
						}
						else{
							div.append($('<span class="xmlayout_tt" style="font-size:15px;margin-left: 5px;">' + title + '</label>'));
						}
						if(style != undefined && style != null && style.fontsize != undefined && style.fontsize!= null){
							style.fontsize > 24 ? style.fontsize = 24 : style.fontsize;
							div.find(".xmlayout_tt").css("font-size", style.fontsize + "px");
							div.find("div").css("height", (25 - style.fontsize)/2 + "px");
						}
						if(style != undefined && style != null && style.fontcolor != undefined && style.fontcolor!= null)
							div.find(".xmlayout_tt").css("color", style.fontcolor);
					}
					tgd.prop("mtitle", title);
				}

				if(url == null || url == undefined){
					throw "XMlayout info: param 'url/obj' in function __load is null!";
					return;
				}

				if(typeof url == 'string'){
					url = $.trim(url);
					tgd.load(url, data, function(responseTxt, statusTxt, xhr){

						if(fun != undefined && fun != null)
							fun(responseTxt, statusTxt, xhr);

						var index1 = url.indexOf('/');
						while(index1 > -1){
							if($.xmlayout.pages[url] != null){
								break;
							}
							else{
								url = url.substr(index1 + 1);
								index1 = url.indexOf('/');
							}
						}

						if($.xmlayout.pages[url] != null)
							$.xmlayout.pages[url]();
					});
				}
				else if(typeof url == "object"){
					tgd.text('');
					tgd.children().remove();
					tgd.append(url);
					if(fun != undefined && fun != null)
						fun();
				}

			},
			__addRightPanel : function(panel, def_data){
				var obj_this = this;

				var rp = def_data.rp, tb = def_data.tb, indexp = def_data.indexp;
				if(!def_data.pos_mark){
					def_data.pos_mark = {opid : indexp, npid : indexp};
				}

				if(tb){
					var mid = "xmlayout_tb" + Math.random().toString().substr(2);
					panel.prop({mindex1 : "#" + mid, mdata : def_data});
					panel.css("overflow", "hidden");
					var toolbar = $('<div id="' + mid + '" class="' + obj_this.random_class.tool_random_class + '" style = "float:right; right:10px; margin-top:5px; width:140px; height:25px;">'
						+ '<div type="button" class="xm-list-group-item popped" action=3 popped="false" value=3 style="height:25px; width: 20px; float:right; opacity:0.25; margin-right:5px;"></div>'
						+ '<div type="button" class="xm-list-group-item fullscreen1" action=4 fullscreen="false" value=4 style="height:25px; width: 20px; float:right; display:none; opacity:0.25; margin-right:5px;"></div>'
						+ '<div type="button" class="xm-list-group-item fullscreen" action=2 fullscreen="false" value=2 style="height:25px; width: 20px; float:right; opacity:0.25; margin-right:5px;"></div>'
						+ '<div type="button" class="xm-list-group-item change" action=5 value=5 style="height:25px; width: 20px; float:right; opacity:0.25; margin-right:5px;"></div>'
						+ '<div type="button" class="xm-list-group-item zoom-out" action=1 value=1 style="height: 25px; width: 20px; float:right; opacity:0.25; margin-right:5px; display:none;"></div>'
						+ '<div type="button" class="xm-list-group-item zoom-in" action=0 value=0 style="height: 25px; width: 20px; float:right; opacity:0.25; margin-right:5px;"></div>'
						+ '</div>');
					panel.append(toolbar);
					toolbar.children().eq(0).append($.xmlayout.__getPNGs(4));
					toolbar.children().eq(0).append($.xmlayout.__getPNGs(5));
					toolbar.children().eq(1).append($.xmlayout.__getPNGs(3));
					toolbar.children().eq(2).append($.xmlayout.__getPNGs(3));
					toolbar.children().eq(3).append($.xmlayout.__getPNGs(11));
					toolbar.children().eq(4).append($.xmlayout.__getPNGs(2));
					toolbar.children().eq(5).append($.xmlayout.__getPNGs(1));
					toolbar.find(".xm-list-group-item").mouseover(function(){
						$(this).css("opacity", "0.8");
					});
					toolbar.find(".xm-list-group-item").mouseout(function(){
						$(this).css("opacity", "0.25");
					});
				}

				var div_html = '<div class="' + obj_this.random_class.targ_panel_random_class + '" style="width:100%; height:100%; background-color: white; border-radius : 4px; overflow: auto;"></div>';
				if(!obj_this.isradius)
					div_html = '<div class="' + obj_this.random_class.targ_panel_random_class + '" style="width:100%; height:100%; background-color: white; overflow: auto;"></div>';
				var upanel = $(div_html);
				if(tb){
					panel.append($('<div class="' + obj_this.random_class.targ_title_random_class + '" style="width:100%; height: 30px; border-bottom : 0.5px solid #E3E3E3; padding : 5px 5px 0px 5px;"></div>'));
					upanel.css({height : "-moz-calc(100% - 30px)", height : "-webkit-calc(100% - 30px)", height : "calc(100% - 30px)"});
				}
				upanel.attr("id", indexp);
				upanel.prop({role : "targ_panel", indexp : indexp, ref_id : obj_this.panel_tree.ref_id});
				if(obj_this.isindexshow)
					upanel.html("<span>D " + indexp + "</span>");
				upanel.design_i = function(rows, cols, datas, istext, isline){
					obj_this.__design(rows, cols, datas, $(this), istext, isline);
					var cr = $(this).find(".xmlayout-in");
					if(cr !=undefined && cr != null){
						for(var i = 0; i < cr.children().length; i++){
							var cv = cr.children().eq(i);
							if(cv.attr("xmidx") !=undefined && cv.attr("xmidx") != null){
								if($(this).prop('mchildren')[cv.attr("xmidx")] != undefined && $(this).prop('mchildren')[cv.attr("xmidx")] != null){
									$(this).prop('mchildren')[cv.attr("xmidx")].children().remove();
									$(this).prop('mchildren')[cv.attr("xmidx")].append(cv);
									i--;
								}
							}
						}
					}
				};
				upanel.getPanels_i = function(midx){
					if(midx == undefined || midx == null)
						return $(this).prop('mchildren');
					else
						return $(this).prop('mchildren')[midx];
				};
				upanel.draw = function(){
					if($(this).find(".xmlayout-in")[0]){
						var tag = $(this).find(".xmlayout-in").eq(0);
						if(tag.attr("type") == null || tag.attr("type") == "inherit")
							tag.css({width : "100%", height : "100%"});
						else if(tag.attr("type") == "auto")
							tag.css({width : "auto", height : "auto"});
						else if(!tag.attr("type") == "self-def"){
							throw "XMlayout info : unknow value of 'type' [" + tag.attr("type") + "] which should be [auto], [inherit] and [self-def]!";
						}

						$(this).find(".xmlayout-in").children().remove();
						for(var i = 1; i < $(this).find(".xmlayout-in").length; i++){
							$(this).find(".xmlayout-in").eq(i).remove();
						}
						tag.css("display", "block");
						tag.append(tag.siblings());
					}
				};
				panel.append(upanel);
				obj_this.panel_arr.push(upanel);
				upanel.loadPage = function(url, data, fun, title, style){
					obj_this.__load($(this), url, data, fun, title, style);
				};
				upanel.loadElement = function(element, fun, title, style){
					if(element == null || element.length < 1){
						throw "XMlayout Error : 'element' in function 'loadElement' should not be null!";
					}
					obj_this.__load($(this), element, null, fun, title, style);
				};
				upanel.getTitlePanel = function(){
					return $(this).parent().find(obj_this.random_class.f_targ_title_random_class);
				};

				if(rp){
					var mid = "xmlayout_rp" + Math.random().toString().substr(2);
					panel.prop("mindex", "#" + mid);
					var rp = $('<div id="' + mid + '" class="' + obj_this.random_class.tool_random_class + '" style = "z-index:10000000; left:0px; top:0px; width:100px; height:auto; position:fixed; display:none;"> <ul class="list-group" style="margin-top: 0; padding-left: 0;">'
							+ '<li class="xm-list-group-item zoom-in" action=0 value=0 style="position: relative;display: block;padding: 5px 15px;margin-bottom: -1px;background-color: #fff;border: 1px solid #ddd;"><span style="cursor:default; font-size:12px;">' + $.xmlayout.LANG.zi + '</span></li>'
							+ '<li class="xm-list-group-item zoom-out" action=1 value=1 style="position: relative;display: none;padding: 5px 15px;margin-bottom: -1px;background-color: #fff;border: 1px solid #ddd;"><span style="cursor:default; font-size:12px;">' + $.xmlayout.LANG.zo + '</span></li>'
							+ '<li class="xm-list-group-item fullscreen" action=2 fullscreen="false" value=2 style="position: relative;display: block;padding: 5px 15px;margin-bottom: -1px;background-color: #fff;border: 1px solid #ddd;"><span style="cursor:default;font-size:12px;">' + $.xmlayout.LANG.fs + '</span></li>'
							+ '<li class="xm-list-group-item fullscreen1" action=4 fullscreen="false" value=4 style="position: relative;display: block;padding: 5px 15px;margin-bottom: -1px;background-color: #fff;border: 1px solid #ddd; display:none;"><span style="cursor:default;font-size:12px;">' + $.xmlayout.LANG.ff + '</span></li>'
							+ '<li class="xm-list-group-item popped" action=3 popped="false" value=3 style="position: relative;display: block;padding: 5px 15px;margin-bottom: -1px;background-color: #fff;border: 1px solid #ddd;"><span style="cursor:default;font-size:12px;">' + $.xmlayout.LANG.pp + '</span></li>'
							+ '</ul></div>');
					panel.append(rp);
					rp.find("ul .xm-list-group-item").mouseover(function(){
						$(this).css("background-color", obj_this.RPCOLOR);
					});
					rp.find("ul .xm-list-group-item").mouseout(function(){
						$(this).css("background-color", "#fff");
					});

					panel.mousedown(function(e){
						var srp = $($(this).prop("mindex"));
						if(e.button > 0){
							srp.css("left", e.clientX + "px");
							srp.css("top", e.clientY + "px");
							srp.css("display", "block");
						}
						else{
							srp.css("display", "none");
						}
					});

					panel.bind("mousewheel", function(){
						$($(this).prop("mindex")).css("display", "none");
					});
				}

				panel.mouseover(function(){
					for(var i = 0; i < obj_this.panel_arr.length; i++){
						var mindex = obj_this.panel_arr[i].parent().prop("mindex");
						if(mindex != $(this).prop("mindex")){
							$(mindex).css("display", "none");
						}
					}
				});

				if(rp || tb){
					panel.find(obj_this.random_class.f_tool_random_class).find(".xm-list-group-item").mousedown(function(e){
						var action = parseInt($(this).attr("action"));
						switch(action){
							case 0:
								var pp = panel;
								if(pp.prop("zoom") == undefined || pp.prop("zoom") == null)
									pp = panel.parent();
								var pp_node = obj_this.panel_tree_list[pp.prop("id")];
								while(pp_node.panel.prop("zoom")){
									pp_node = pp_node.parent;
									if(pp_node.parent.parent == null){
										break;
									}
								}
								if(pp_node.parent.parent == null){
									var mlis = panel.find(obj_this.random_class.f_tool_random_class).find(".fullscreen");
									mlis.attr("fullscreen", "true");
									for(var i = 0; i < mlis.length; i++){
										if(mlis.eq(i).attr("type") != "button")
											mlis.eq(i).find("span").text($.xmlayout.LANG.rs);
										else
											mlis.eq(i).find("img").attr("title", $.xmlayout.LANG.rs);
									}
									obj_this.disablePanelItem(panel.find(obj_this.random_class.f_targ_panel_random_class), obj_this.RPID.ZOOM_IN);
								}
								obj_this.useablePanelItem(panel.find(obj_this.random_class.f_targ_panel_random_class), obj_this.RPID.ZOOM_OUT);

								pp_node.panel.prop("zoom", true);

								pp_node = obj_this.panel_tree_list[pp.prop("id")];
								obj_this.__drawz(pp_node);
								for(var i = 0; i < obj_this.panel_arr.length; i++){
									obj_this.disablePanelItem(obj_this.panel_arr[i], obj_this.RPID.POPPED_BERTH);
								}

								obj_this.iszoom = true;
								break;
							case 1:
								var pp = panel;
								if(pp.prop("zoom") == undefined || pp.prop("zoom") == null)
									pp = panel.parent();
								var pp_node = obj_this.panel_tree_list[pp.prop("id")];
								while(pp_node.parent != null && pp_node.parent.panel.prop("zoom")){
									pp_node = pp_node.parent;

								}
								pp_node.panel.prop("zoom", false);
								obj_this.__drawz(obj_this.panel_tree_list[pp.prop("id")]);

								var mlis = panel.find(obj_this.random_class.f_tool_random_class).find(".fullscreen");
								mlis.attr("fullscreen", "false");
								for(var i = 0; i < mlis.length; i++){
									if(mlis.eq(i).attr("type") != "button")
										mlis.eq(i).find("span").text($.xmlayout.LANG.fs);
									else
										mlis.eq(i).find("img").attr("title", $.xmlayout.LANG.fs);
								}

								if(pp_node.lcpanel == null && pp_node.rcpanel == null){
									obj_this.disablePanelItem(panel.find(obj_this.random_class.f_targ_panel_random_class), obj_this.RPID.ZOOM_OUT);
								}
								obj_this.useablePanelItem(panel.find(obj_this.random_class.f_targ_panel_random_class), obj_this.RPID.ZOOM_IN);

								var iszoom = false;
								for(var i = 0; i < obj_this.panel_arr.length; i++){
									var mpp = obj_this.panel_arr[i].parent();
									if(mpp.prop("zoom") == undefined || mpp.prop("zoom") == null)
										mpp = mpp.parent();
									if(mpp.prop("zoom")){
										iszoom = true;
										break;
									}
								}
								if(!iszoom){
									for(var i = 0; i < obj_this.panel_arr.length; i++){
										obj_this.useablePanelItem(obj_this.panel_arr[i], obj_this.RPID.POPPED_BERTH);
									}
									obj_this.iszoom = false;
								}
								break;
							case 2:
								if($(this).attr("fullscreen") == "false"){

									console.log();

									var pp = panel;
									if(pp.prop("zoom") == undefined || pp.prop("zoom") == null)
										pp = panel.parent();
									var pp_node = obj_this.panel_tree_list[pp.prop("id")];
									while(pp_node.parent != null){
										pp_node.panel.prop("zoom", true);
										pp_node = pp_node.parent;
									}
									pp_node = obj_this.panel_tree_list[pp.prop("id")];
									obj_this.__drawz(pp_node);
									for(var i = 0; i < obj_this.panel_arr.length; i++){
										obj_this.disablePanelItem(obj_this.panel_arr[i], obj_this.RPID.POPPED_BERTH);
									}
									var mlis = panel.find(obj_this.random_class.f_tool_random_class).find(".fullscreen");
									mlis.attr("fullscreen", "true");
									for(var i = 0; i < mlis.length; i++){
										if(mlis.eq(i).attr("type") != "button")
											mlis.eq(i).find("span").text($.xmlayout.LANG.rs);
										else
											mlis.eq(i).find("img").attr("title", $.xmlayout.LANG.rs);
									}

									obj_this.disablePanelItem(panel.find(obj_this.random_class.f_targ_panel_random_class), obj_this.RPID.ZOOM_IN);
									obj_this.useablePanelItem(panel.find(obj_this.random_class.f_targ_panel_random_class), obj_this.RPID.ZOOM_OUT);

									obj_this.iszoom = true;
								}
								else if($(this).attr("fullscreen") == "true"){
									for(var i = 0; i < obj_this.panel_arr.length; i++){
										var mpp = obj_this.panel_arr[i].parent();
										if(mpp.prop("zoom") == undefined || mpp.prop("zoom") == null)
											mpp = mpp.parent();
										var pp_node = obj_this.panel_tree_list[mpp.prop("id")];
										var bool_draw = false;
										while(pp_node.panel.prop("zoom")){
											pp_node.panel.prop("zoom", false);

											pp_node.panel.css(pp_node.panel.prop("pvalue"), pp_node.panel.prop("ovalue") + "%");
											pp_node.panel.siblings().css("display", "block");
											obj_this.__drawdragbar(pp_node.panel, true);
											obj_this.__drawdragbar(pp_node.panel.siblings(), true);

											pp_node = pp_node.parent;
											bool_draw = true;
										}
										if(bool_draw)
											obj_this.__drawz(pp_node);
									}
									for(var i = 0; i < obj_this.panel_arr.length; i++){
										obj_this.useablePanelItem(obj_this.panel_arr[i], obj_this.RPID.POPPED_BERTH);
										obj_this.useablePanelItem(obj_this.panel_arr[i], obj_this.RPID.ZOOM_IN);
										obj_this.disablePanelItem(obj_this.panel_arr[i], obj_this.RPID.ZOOM_OUT);
									}

									var mlis = panel.find(obj_this.random_class.f_tool_random_class).find(".fullscreen");
									mlis.attr("fullscreen", "false");
									for(var i = 0; i < mlis.length; i++){
										if(mlis.eq(i).attr("type") != "button")
											mlis.eq(i).find("span").text($.xmlayout.LANG.fs);
										else
											mlis.eq(i).find("img").attr("title", $.xmlayout.LANG.fs);
									}

									obj_this.iszoom = false;
								}
								break;
							case 3:
								var pp = panel;
								if(pp.prop("zoom") == undefined || pp.prop("zoom") == null)
									pp = panel.parent();
								var pp_node = obj_this.panel_tree_list[pp.prop("id")];
								if($(this).attr("popped") == "false"){
									pp_node.panel.prop("popped", 2);
									var opp_node = pp_node;
									obj_this.pop_count.push(pp.prop("id"));
									while(pp_node.panel.prop("popped") == 2){
										if(pp_node.parent == null)
											break;
										pp_node = pp_node.parent;
										pp_node.panel.prop("popped", pp_node.panel.prop("popped") + 1);

									}

									pp.css('z-index', ++$.xmlayout.pop_zindex);
									obj_this.__drawp(obj_this.panel_tree);

									var mlis = panel.find(obj_this.random_class.f_tool_random_class).find(".popped");
									mlis.attr("popped", "true");
									for(var i = 0; i < mlis.length; i++){
										if(mlis.eq(i).attr("type") != "button")
											mlis.eq(i).find("span").text($.xmlayout.LANG.bt);
									}

									mlis = panel.find(obj_this.random_class.f_tool_random_class).find(".fullscreen1");
									mlis.attr("fullscreen", "false");
									for(var i = 0; i < mlis.length; i++){
										if(mlis.eq(i).attr("type") != "button")
											mlis.eq(i).find("span").text($.xmlayout.LANG.ff);
										else
											mlis.eq(i).find("img").attr("title", $.xmlayout.LANG.ff);
									}
									obj_this.popped_count++;

									if(obj_this.popped_count == 1){
										for(var i = 0; i < obj_this.panel_arr.length; i++){
											obj_this.disablePanelItem(obj_this.panel_arr[i], obj_this.RPID.ZOOM_IN);
											obj_this.disablePanelItem(obj_this.panel_arr[i], obj_this.RPID.FULLSCREEN_RESIZE);
											obj_this.disablePanelItem(obj_this.panel_arr[i], obj_this.RPID.CHANGE_PANEL);
										}
									}

									$(panel.prop("mindex1")).children().eq(0).children().eq(0).css("display", "none");
									$(panel.prop("mindex1")).children().eq(0).children().eq(1).css("display", "block");

									obj_this.useablePanelItem(panel.find(obj_this.random_class.f_targ_panel_random_class), obj_this.RPID.FULLSCREEN_RESIZE1);
									opp_node.panel.prop("ispopped", true);
									$.xmlayout.pop_zindex += obj_this.pop_count.length;
									obj_this.ispop = true;
								}
								else if($(this).attr("popped") == "true"){
									pp_node.panel.prop("ispopped", false);
									pp_node.panel.prop("popped", 0);
									//pp_node.panel.resizable("option", "disabled", true);
									$.xmlayout.div_undrag(pp_node.panel);
									$.xmlayout.div_unresize(pp_node.panel);
									for(var i = 0; i < obj_this.pop_count.length; i++){
										if(obj_this.pop_count[i] == pp.prop("id")){
											obj_this.pop_count.splice(i,1);
											break;
										}
									}
									pp_node = pp_node.parent;
									while(pp_node != null && pp_node.panel.prop("popped") == 2){
										pp_node.panel.prop("popped", pp_node.panel.prop("popped") - 1);
										pp_node = pp_node.parent;
									}
									if(pp_node != null)
										pp_node.panel.prop("popped", pp_node.panel.prop("popped") - 1);

									pp.css('z-index', 0);
									obj_this.__drawp(obj_this.panel_tree);

									var mlis = panel.find(obj_this.random_class.f_tool_random_class).find(".popped");
									mlis.attr("popped", "false");
									for(var i = 0; i < mlis.length; i++){
										if(mlis.eq(i).attr("type") != "button")
											mlis.eq(i).find("span").text($.xmlayout.LANG.pp);
									}
									obj_this.popped_count--;
									if(obj_this.popped_count == 0){
										for(var i = 0; i < obj_this.panel_arr.length; i++){
											obj_this.useablePanelItem(obj_this.panel_arr[i], obj_this.RPID.ZOOM_IN);
											obj_this.useablePanelItem(obj_this.panel_arr[i], obj_this.RPID.FULLSCREEN_RESIZE);
											obj_this.useablePanelItem(obj_this.panel_arr[i], obj_this.RPID.CHANGE_PANEL);
										}
										obj_this.ispop = false;
									}

									$(panel.prop("mindex1")).children().eq(0).children().eq(0).css("display", "block");
									$(panel.prop("mindex1")).children().eq(0).children().eq(1).css("display", "none");

									obj_this.disablePanelItem(panel.find(obj_this.random_class.f_targ_panel_random_class), obj_this.RPID.FULLSCREEN_RESIZE1);
									var xds = $(this).siblings();
									for(var i = 0; i < xds.length; i++){
										if(xds.eq(i).attr("action") == 4){
											xds.eq(i).attr("fullscreen", "false");
											if($(this).attr("type") != "button")
												xds.eq(i).find("span").text($.xmlayout.LANG.ff);
											break;
										}
									}
								}
								break;
							case 4:
								var pp = panel;
								if(pp.prop("zoom") == undefined || pp.prop("zoom") == null)
									pp = panel.parent();
								var pp_node = obj_this.panel_tree_list[pp.prop("id")];

								if($(this).attr("fullscreen") == "false"){
									pp_node.panel.prop("mw", pp_node.panel.css("width"));
									pp_node.panel.prop("mh", pp_node.panel.css("height"));
									pp_node.panel.prop("ml", pp_node.panel.css("left"));
									pp_node.panel.prop("mt", pp_node.panel.css("top"));
									pp_node.panel.css("width", $(document).width() + "px");
									pp_node.panel.css("height", $(document).height() + "px");
									pp_node.panel.css("left", "0px");
									pp_node.panel.css("top", "0px");
									//pp_node.panel.draggable( "option", "disabled", true);
									$.xmlayout.div_undrag(pp_node.panel);
									$.xmlayout.div_unresize(pp_node.panel);

									var mlis = panel.find(obj_this.random_class.f_tool_random_class).find(".fullscreen1");
									mlis.attr("fullscreen", "true");
									for(var i = 0; i < mlis.length; i++){
										if(mlis.eq(i).attr("type") != "button")
											mlis.eq(i).find("span").text($.xmlayout.LANG.rs);
										else
											mlis.eq(i).find("img").attr("title", $.xmlayout.LANG.rs);
									}
								}
								else if($(this).attr("fullscreen") == "true"){
									pp_node.panel.css("width", pp_node.panel.prop("mw"));
									pp_node.panel.css("height", pp_node.panel.prop("mh"));
									pp_node.panel.css("left", pp_node.panel.prop("ml"));
									pp_node.panel.css("top", pp_node.panel.prop("mt"));
									//pp_node.panel.draggable( "option", "disabled", false);
									$.xmlayout.div_drag(pp_node.panel);
									$.xmlayout.div_resize(pp_node.panel);

									var mlis = panel.find(obj_this.random_class.f_tool_random_class).find(".fullscreen1");
									mlis.attr("fullscreen", "false");
									for(var i = 0; i < mlis.length; i++){
										if(mlis.eq(i).attr("type") != "button")
											mlis.eq(i).find("span").text($.xmlayout.LANG.ff);
										else
											mlis.eq(i).find("img").attr("title", $.xmlayout.LANG.ff);
									}
								}
								break;
							case 5:
								var pp = panel;
								var targ_panel = pp.find(obj_this.random_class.f_targ_panel_random_class);
								if(!targ_panel.prop("otc"))
									targ_panel.prop({otc : targ_panel.parent().find(obj_this.random_class.f_targ_title_random_class).css("background-color")});
								targ_panel.parent().find(obj_this.random_class.f_targ_title_random_class).css("background-color", "#EBEBEB");

								if($.xmlayout.change_panel && $.xmlayout.change_panel.parent().prop("mindex1") !== targ_panel.parent().prop("mindex1") && $.xmlayout.change_panel.prop("ref_id") === targ_panel.prop("ref_id")){
									var divc1 = targ_panel;
									var divc2 = $.xmlayout.change_panel;
									divc1.css({width:targ_panel.width()+"px", height:targ_panel.height()+"px", position:"absolute", border:"1px solid #CCCCCC"});
									divc2.css({width:targ_panel.width()+"px", height:targ_panel.height()+"px", position:"absolute", border:"1px solid #CCCCCC"});
									//var stl1 = obj_this.__getScroll(divc1);console.log(stl1);console.log(divc1.offset());
									var divc1_left = divc1.position().left;
									var divc1_top = divc1.position().top;
									var divc2_left = divc2.position().left;
									var divc2_top = divc2.position().top;
									divc1.css({left : divc1_left + "px", top : divc1_top + "px"});
									divc2.css({left : divc2_left + "px", top : divc2_top + "px"});

									var td1 = divc1.parent().find(obj_this.random_class.f_targ_title_random_class);
									var td2 = divc2.parent().find(obj_this.random_class.f_targ_title_random_class);
									var pp1 = divc1.parent();
									var pp2 = divc2.parent();

									divc1.animate({left : divc2_left + "px", top : divc2_top + "px"}, "normal", null, function(){

										divc1.css({position:"static", width:"100%", height : "-moz-calc(100% - 30px)", height : "-webkit-calc(100% - 30px)", height : "calc(100% - 30px)", left : "auto", top : "auto", border:"none"});
										td1.css({backgroundColor : divc1.prop("otc")});
										pp2.append(td1);
										pp2.append(divc1);
										pp2.prop("mdata").pos_mark.npid = divc1.prop("indexp");

									});
									divc2.animate({left : divc1_left + "px", top : divc1_top + "px"}, "normal", null, function(){

										divc2.css({position:"static", width:"100%", height : "-moz-calc(100% - 30px)", height : "-webkit-calc(100% - 30px)", height : "calc(100% - 30px)", left : "auto", top : "auto", border:"none"});
										td2.css({backgroundColor : divc2.prop("otc")});
										pp1.append(td2);
										pp1.append(divc2);
										pp1.prop("mdata").pos_mark.npid = divc2.prop("indexp");
									});
									$.xmlayout.change_panel = null;
								}
								else{
									if(!$.xmlayout.change_panel)
										$.xmlayout.change_panel = targ_panel;
									else{
										if($.xmlayout.change_panel.prop("ref_id") !== targ_panel.prop("ref_id")){
											targ_panel.parent().find(obj_this.random_class.f_targ_title_random_class).css("background-color", targ_panel.prop("otc"));
											alert("Not in the same layout!");
										}
										$.xmlayout.change_panel.parent().find(obj_this.random_class.f_targ_title_random_class).css("background-color", $.xmlayout.change_panel.prop("otc"));
										$.xmlayout.change_panel = null;
									}
								}
								break;
							default :
								break;
						}

						if(action == 0 || action == 1 || action == 2 || action == 3 || action == 4 || action == 5){
							for(var i = 0; i < obj_this.resize_fun.length; i++){
								if(obj_this.resize_fun[i].params != null)
									obj_this.resize_fun[i].fun(obj_this.resize_fun[i].params);
								else
									obj_this.resize_fun[i].fun();
							}
						}

						if(action != 5){
							if($.xmlayout.change_panel){
								$.xmlayout.change_panel.parent().find(obj_this.random_class.f_targ_title_random_class).css("background-color", $.xmlayout.change_panel.prop("otc"));
							}
							$.xmlayout.change_panel = null;
						}
					});
				}
			},
			__getScroll : function(element){
				var obj = {st : 0, sl : 0};
				element = element.parent();
				while(element.length > 0){
					obj.st += parseInt(element.scrollTop());
					obj.sl += parseInt(element.scrollLeft());
					element = element.parent();
				}
				return obj;
			},
			__drawz : function(panel_tree){
				if(panel_tree.parent != null){
					if(panel_tree.panel.prop("zoom")){
						var pp = panel_tree.parent.lcpanel.panel;
						var lpp = panel_tree.parent.rcpanel.panel;
						var pvalue = pp.prop("pvalue");
						if(pp.prop("zoom")){
							pp = panel_tree.parent.rcpanel.panel;
							lpp = panel_tree.parent.lcpanel.panel;
						}

						pp.css("display", "none");
						lpp.css(pvalue, "100%");
						this.__drawdragbar(lpp, false);
						this.__drawz(panel_tree.parent);
					}
					else{
						var pp = panel_tree.parent.lcpanel.panel;
						var lpp = panel_tree.parent.rcpanel.panel;
						var pvalue = pp.prop("pvalue");
						if(pp.css("display") == "none"){
							pp.css("display", "block");
							pp.css(pvalue, pp.prop("ovalue") + "%");
							lpp.css(pvalue, lpp.prop("ovalue") + "%");
							this.__drawdragbar(lpp, true);
						}
						if(lpp.css("display") == "none"){
							lpp.css("display", "block");
							pp.css(pvalue, pp.prop("ovalue") + "%");
							lpp.css(pvalue, lpp.prop("ovalue") + "%");
							this.__drawdragbar(lpp, true);
						}
					}
				}
			},
			__drawp : function(panel_tree){
				if(panel_tree != null){
					if(panel_tree.panel.prop("popped") == 2)
						this.__drawp2(panel_tree);
					else if(panel_tree.panel.prop("popped") == 1){
						this.__drawp1(panel_tree);
						this.__drawp(panel_tree.lcpanel);
						this.__drawp(panel_tree.rcpanel);
					}
					else if(panel_tree.panel.prop("popped") == 0){
						this.__drawp0(panel_tree);
						this.__drawp(panel_tree.lcpanel);
						this.__drawp(panel_tree.rcpanel);
					}
				}
			},
			__drawp0 : function(panel_tree){
				if(panel_tree != null){
					if(panel_tree.lcpanel != null){
						var lp = panel_tree.lcpanel.panel;
						var rp = panel_tree.rcpanel.panel;
						var pvalue = lp.prop("pvalue");
						lp.css(pvalue, lp.prop("ovalue") + "%");
						rp.css(pvalue, rp.prop("ovalue") + "%");
						this.__drawdragbar(lp, true);
						this.__drawdragbar(rp, true);
						if(panel_tree.lcpanel.lcpanel == null){
							this.__drawleafnode(lp, lp.prop("ovalue"));
						}
						if(panel_tree.rcpanel.lcpanel == null){
							this.__drawleafnode(rp, rp.prop("ovalue"));
						}
					}
				}
			},
			__drawp1 : function(panel_tree){
				var panel = panel_tree.lcpanel.panel;
				var npanel = panel_tree.lcpanel;
				if(panel_tree.rcpanel.panel.prop("popped") != 2){
					panel = panel_tree.rcpanel.panel;
					npanel = panel_tree.rcpanel;
				}
				var pvalue = panel.prop("pvalue");

				if(npanel.lcpanel == null)
					this.__drawleafnode(panel, 100);
				else
					panel.css(pvalue, "100%");

				this.__drawdragbar(panel, false);
			},
			__drawp2 : function(panel_tree){
				if(panel_tree.lcpanel != null){
					var panel = panel_tree.panel;
					var pvalue = panel.prop("pvalue");
					if(pvalue != undefined && pvalue != null)
						panel.css(pvalue, "0%");
					this.__drawp2(panel_tree.lcpanel);
					this.__drawp2(panel_tree.rcpanel);
				}
				else{
					var obj_this = this;
					var panel = panel_tree.panel;
					if(panel.css("position") != "fixed"){
						var pvalue = panel.prop("pvalue");
						panel.css("position", "fixed");
						panel.css("width", this.POPWH.width + "px");
						panel.css("height", this.POPWH.height + "px");
						//panel.css("border", "0.5px solid #C2C2C2");
						if(panel.prop("ispopped") == undefined || !panel.prop("ispopped")){
							for(var i = 0; i < obj_this.pop_count.length; i++){
								if(obj_this.pop_count[i] == panel.prop("id")){
									panel.css("left", (obj_this.panel_offset.left + 100 + 20 * i) + "px");
									panel.css("top", (obj_this.panel_offset.top + 160 + 20 * i) + "px");
									panel.css("z-index", $.xmlayout.pop_zindex + i + 1);
									break;
								}
							}
						}
						// panel.draggable();
						// panel.resizable();
						// panel.draggable("option", "disabled", false);
						// panel.resizable("option", "disabled", false);
						$.xmlayout.div_drag(panel);
						$.xmlayout.div_resize(panel);
						panel.on("resize", function() {
							for(var i = 0; i < obj_this.resize_fun.length; i++){
								if(obj_this.resize_fun[i].params != null)
									obj_this.resize_fun[i].fun(obj_this.resize_fun[i].params);
								else
									obj_this.resize_fun[i].fun();
							}
						});

						this.__drawdragbar(panel, false);

						if(panel.prop("key") == 2){
							var pa = panel.children().eq(1);
							pa.css(pvalue, "100%");
						}

						var rpp = panel;
						if(panel.prop("key") == 2){
							var rpp = panel.children().eq(1);
							if(rpp.prop("key") == 2)
								rpp = panel.children().eq(0);
						}
						if(rpp.prop("mindex")!=undefined && rpp.prop("mindex")!=null){
							obj_this.disablePanelItem(rpp.find(obj_this.random_class.f_targ_panel_random_class), obj_this.RPID.ZOOM_IN);
							obj_this.disablePanelItem(rpp.find(obj_this.random_class.f_targ_panel_random_class), obj_this.RPID.FULLSCREEN_RESIZE);
						}

						panel.click(function(){
							$(this).css("z-index", ++$.xmlayout.pop_zindex);
						});
					}
				}
			},
			__drawdragbar : function(panel, isdraw){
				var obj_this = this;
				if(isdraw){
					if(panel.prop("key") == 2){
						var dragbar = panel.children().eq(0);
						var divp = panel.children().eq(1);
						if(dragbar.prop("key") == 1){
							dragbar = panel.children().eq(1);
							divp = panel.children().eq(0);
						}
						dragbar.css("display", "block");
						divp.css(panel.prop("pvalue"), 'calc(100% - ' + obj_this.drag_bar_unit + 'px)');
					}
				}
				else{
					if(panel.prop("key") == 2){
						var dragbar = panel.children().eq(0);
						var divp = panel.children().eq(1);
						if(dragbar.prop("key") == 1){
							dragbar = panel.children().eq(1);
							divp = panel.children().eq(0);
						}
						dragbar.css("display", "none");
						divp.css(panel.prop("pvalue"), "100%");
					}
				}
			},
			__drawleafnode : function(panel, pvalue){
				var obj_this = this;
				panel.css(panel.prop("pvalue"), pvalue + "%");
				if(panel.css("position") == "fixed"){
					panel.css("position", "static");
					var qvalue = "width";
					if(panel.prop("pvalue") == "width"){
						qvalue = "height";
					}

					this.__drawdragbar(panel, true);

					if(panel.prop("key") == 2){
						var pa = panel.children().eq(1);
						pa.css(panel.prop("pvalue"), 'calc(100% - ' + obj_this.drag_bar_unit + 'px)');
					}
					panel.css(qvalue, "100%");

					var rpp = panel;
					if(panel.prop("key") == 2){
						var rpp = panel.children().eq(1);
						if(rpp.prop("key") == 2)
							rpp = panel.children().eq(0);
					}
					// if(rpp.prop("mindex")!=undefined && rpp.prop("mindex")!=null){
					// 	obj_this.useablePanelItem(rpp.find(obj_this.random_class.f_targ_panel_random_class), obj_this.RPID.ZOOM_IN);
					// 	obj_this.useablePanelItem(rpp.find(obj_this.random_class.f_targ_panel_random_class), obj_this.RPID.FULLSCREEN_RESIZE);
					// }
				}
			},
			getPanels : function(indexp){
				if(indexp == null || indexp == undefined)
					return this.panel_arr;
				else{
					for(var i = 0; i < this.panel_arr.length; i++){
						if(indexp === this.panel_arr[i].prop("seq") || indexp === this.panel_arr[i].prop("indexp")){
							return this.panel_arr[i];
						}
					}
					return null;
				}
			},
			element : function(value, resize, rp, tb, lrs, uds){
				var obj_this = this;
				try{
					if($.xmlayout.__isNaN(value))
						throw "XMlayout info: value='" + value + "' in function $.xmlayout element() is not a number!";
					if(resize == undefined || resize == null)
						resize = true;
					else if(typeof resize != 'boolean')
						throw "XMlayout info: 'resize' in function $.xmlayout element() is not a boolean!";
					if(rp == undefined || rp == null)
						rp = true;
					else if(typeof rp != 'boolean')
						throw "XMlayout info: 'rp' in function $.xmlayout element() is not a boolean!";
					if(tb == undefined || tb == null)
						tb = true;
					else if(typeof tb != 'boolean')
						throw "XMlayout info: 'tb' in function $.xmlayout element() is not a boolean!";
				}
				catch(err){
					throw err;
				}
				var obj = {
					value : value,
					resize : resize,
					rp : rp,
					tb : tb,
					lr : [],
					ud : [],
					index : null,
					indexp : null,
					p_vw : null,
					p_vh : null
				};

				if(lrs != null && lrs.length >= 2){
					for(var i = 0; i < 2; i++){
						obj.lr.push(lrs[i]);
					}
				}

				if(uds != null && uds.length >= 2){
					for(var i = 0; i < 2; i++){
						obj.ud.push(uds[i]);
					}
				}

				return obj;
			},
			design : function(rows, cols, rc, values, attr, node){
				var obj_this = this;
				if($.xmlayout.__isNaN(rows)){
					throw "XMlayout info: 'rows' in function $.xmlayout design() is not a number!";
				}
				if($.xmlayout.__isNaN(cols)){
					throw "XMlayout info: 'cols' in function $.xmlayout design() is not a number!";
				}
				if(rc != 0 && rc != 1){
					rc = 0;
				}

				rows = parseInt(rows);
				cols = parseInt(cols);

				if(rows < 1){
					throw "XMlayout info: 'rows' in function $.xmlayout design() should be bigger than 0!";
				}
				if(cols < 1){
					throw "XMlayout info: 'cols' in function $.xmlayout design() should be bigger than 0!";
				}

				if(values != null){
					if(rc == 0){
						if(values.length != rows){
							throw  "XMlayout info: 'values' length in function $.xmlayout design() is invalid, it should be equal with 'rows'=" + rows + "!";
						}
						else{
							for(var i = 0; i < rows; i++){
								if(values[i].length != cols + 1){
									throw "XMlayout info: 'values[" + i + "]' length in function $.xmlayout design() is invalid, it should be equal with 'cols'+1=" + cols + 1 + "!";
								}
								for(var j = 0; j < values[i].length; j++){
									if(!((!$.xmlayout.__isNaN(values[i][j]) && values[i][j] > 0) || $.xmlayout.__isPx(values[i][j]))){
										throw "XMlayout info: 'values[" + i + "," + j + "]=" + values[i][j] + "' in function $.xmlayout design() is invalid!";
									}
								}
							}
						}
					}
					else if(rc == 1){
						if(values.length != cols){
							throw "XMlayout info: 'values' length in function $.xmlayout design() is invalid, it should be equal with 'cols'=" + cols + "!";
						}
						else{
							for(var i = 0; i < cols; i++){
								if(values[i].length != rows + 1){
									throw "XMlayout info: 'values[" + i + "]' length in function $.xmlayout design() is invalid, it should be equal with 'rows'+1=" + rows + 1 + "!";
								}
								for(var j = 0; j < values[i].length; j++){
									if(!((!$.xmlayout.__isNaN(values[i][j]) && values[i][j] > 0) || $.xmlayout.__isPx(values[i][j]))){
										throw "XMlayout info: 'values[" + i + "," + j + "]=" + values[i][j] + "' in function $.xmlayout design() is invalid!";
									}
								}
							}
						}
					}
				}

				var values_px_d = null;
				if(values){
					if(!node){
						if(rc == 0){
							values_px_d = transDataAll(values, obj_this.root_panel.height(), obj_this.root_panel.width());
						}
						else{
							values_px_d = transDataAll(values, obj_this.root_panel.width(), obj_this.root_panel.height());
						}
					}
					else{
						if(rc == 0){
							values_px_d = transDataAll(values, parseInt(node.p_vh), parseInt(node.p_vw));
						}
						else{
							values_px_d = transDataAll(values, parseInt(node.p_vw), parseInt(node.p_vh));
						}
					}
				}

				//console.log(values);console.log(values_px_d);

				function setAttr(node, attr){
					if(attr == null || attr == undefined)
						return;

					if(attr.length == 2){
						if(typeof attr[0] == "boolean" && typeof attr[1] == "boolean"){
							if(!attr[0])
								node.rp = false;
							if(!attr[1])
								node.tb = false;
							return;
						}
					}

					for(var i = 0; i < attr.length; i++){
						if(attr[i].length != 3){
							throw "XMlayout info: 'attr' in function $.xmlayout design() is out off index!";
						}
						if(node.index == attr[i][0]){
							if(typeof attr[i][1] == 'boolean')
								node.rp = attr[i][1];
							if(typeof attr[i][2] == 'boolean')
								node.tb = attr[i][2];
							break;
						}
					}

				};

				function transDataAll(values, v1, v2){
					var values_px = [];

					var v_px = 0, v_bl = 0;
					$.each(values, function(i,v){
						values_px[i] = [];

						if($.xmlayout.__isPx(v[0])){
							v_px += parseInt(v[0]);
						}
						else{
							v[0] *= 100;
							v_bl += v[0];
						}

						var v_px1 = 0, v_bl1 = 0;
						for(var j = 1; j < v.length; j++){
							if($.xmlayout.__isPx(v[j])){
								v_px1 += parseInt(v[j]);
							}
							else{
								v[j] *= 100;
								v_bl1 += v[j];
							}
						}

						if(v2 < v_px1){
							throw "XMlayout info: parent panel's value is v='" + v2 + "' which is less than sum of set value sv='" + v_px1 + "'";
						}

						for(var j = 1; j < v.length; j++){
							if($.xmlayout.__isPx(v[j])){
								values_px[i][j] = v[j];
								if(v_bl1 > 0)
									v[j] = parseInt(v[j]) * v_bl1 / (v2 - v_px1);
								else{
									v[j] = parseInt(v[j]) * 100 / v_px1;
									values_px[i][j] = v[j] / 100 * v2;
								}
							}
							else{
								values_px[i][j] = v[j] * (v2 - v_px1) / v_bl1 + "px";
							}
						}
					});

					if(v1 < v_px){
						throw "XMlayout info: parent panel's value is v='" + v1 + "' which is less than sum of set value sv='" + v_px + "'";
					}

					$.each(values, function(i,v){
						if($.xmlayout.__isPx(v[0])){
							values_px[i][0] = v[0];
							if(v_bl > 0)
								v[0] = parseInt(v[0]) * v_bl / (v1 - v_px);
							else{
								v[0] = parseInt(v[0]) * 100 / v_px;
								values_px[i][0] = v[0] / 100 * v1;
							}
						}
						else{
							values_px[i][0] = v[0] * (v1 - v_px) / v_bl + "px";
						}
					});

					return values_px;
				}

				function calcData(rows, cols, values, values_px, data, rc){
					var pd = data;
					for(var i = 1; i <= rows; i++){
						var d1, d2;
						if(i < rows){
							if(values != null){
								var c1 = 0, c2 = 0;
								for(var x = 0; x < values.length; x++){
									if(x < i)
										c1 += values[x][0];
									c2 += values[x][0];
								}
								d1 = obj_this.element(values[i-1][0], true, true, true);
								d2 = obj_this.element(c2 - c1, true, true, true);
							}
							else{
								d1 = obj_this.element(1, true, true, true);
								d2 = obj_this.element(rows - i, true, true, true);
							}

							if(rc == 1){
								pd.lr.push(d1);
								pd.lr.push(d2);
							}
							else{
								pd.ud.push(d1);
								pd.ud.push(d2);
							}
							pd = d2;
						}
						else{
							d1 = pd;
						}


						var pd1 = d1;
						for(var j = 1; j < cols; j++){
							var d11, d12;
							if(values != null){
								var c1 = 0, c2 = 0;
								for(var x = 1; x < values[i-1].length; x++){
									if(x <= j)
										c1 += values[i-1][x];
									c2 += values[i-1][x];
								}
								d11 = obj_this.element(values[i-1][j], true, true, true);
								d12 = obj_this.element(c2 - c1, true, true, true);
							}
							else{
								d11 = obj_this.element(1, true, true, true);
								d12 = obj_this.element(cols - j, true, true, true);
							}

							if(rc == 1){
								pd1.ud.push(d11);
								pd1.ud.push(d12);
							}
							else{
								pd1.lr.push(d11);
								pd1.lr.push(d12);
							}
							pd1 = d12;

							if(rc == 0){
								d11.index = i + ":" + j;
								if(values_px != null){
									d11.p_vw = values_px[i-1][j];
									d11.p_vh = values_px[i-1][0];
								}
							}
							else{
								d11.index = j + ":" + i;
								if(values_px != null){
									d11.p_vw = values_px[i-1][0];
									d11.p_vh = values_px[i-1][j];
								}
							}

							if(node == null){
								d11.indexp = d11.index;
							}
							else{
								d11.indexp = node.indexp + ";" + d11.index;
							}

							d11.deep = data.deep + 1;

							setAttr(d11, attr);
							if(cols - j == 1){
								if(rc == 0){
									d12.index = i + ":" + cols;
									if(values_px != null){
										d12.p_vw = values_px[i-1][j];
										d12.p_vh = values_px[i-1][0];
									}
								}
								else{
									d12.index = cols + ":" + i;
									if(values_px != null){
										d12.p_vw = values_px[i-1][0];
										d12.p_vh = values_px[i-1][j];
									}
								}
								if(node == null){
									d12.indexp = d12.index;
								}
								else{
									d12.indexp = node.indexp + ";" + d12.index;
								}

								d12.deep = data.deep + 1;

								setAttr(d12, attr);
							}
						}


						if(cols == 1){
							if(rc == 0){
								d1.index = i + ":" + cols;
								if(values_px != null){
									d1.p_vw = values_px[i-1][1];
									d1.p_vh = values_px[i-1][0];
								}
							}
							else{
								d1.index = cols + ":" + i;
								if(values_px != null){
									d1.p_vw = values_px[i-1][0];
									d1.p_vh = values_px[i-1][1];
								}
							}
							if(node == null){
								d1.indexp = d1.index;
							}
							else{
								d1.indexp = node.indexp + ";" + d1.index;
							}

							d1.deep = data.deep + 1;

							setAttr(d1, attr);
						}
					}
				};

				var data = node;

				if(rows == 1 && cols == 1){
					if(data == null || data == undefined){
						data = obj_this.element(rows, false, true, true);
						data.deep = 1;
					}
					data.index = "1:1";
					setAttr(data, attr);
				}

				if(rc == 0){//rows first desgin
					if(data == null || data == undefined){
						data = obj_this.element(rows, true, true, true);
						data.deep = 1;
					}
					calcData(rows, cols, values, values_px_d, data, rc);
				}
				else if(rc == 1){//cols first desgin
					if(data == null || data == undefined){
						data = obj_this.element(cols, true, true, true);
						data.deep = 1;
					}
					calcData(cols, rows, values, values_px_d, data, rc);
				}

				if(node == null){
					obj_this.panel_data = data;
					obj_this.getData = function(){
						return obj_this.panel_data;
					}
				}

				data.getData = function(index){
					var nd = obj_this.__getDesignNode(index, (data.deep + 1), this);
					if(nd != null)
						nd.design = function(rows, cols, rc, values, attr){
							return obj_this.design(rows, cols, rc, values, attr, this);
						}
					return nd;
				}

				return data;
			},
			__getDesignNode : function(row_col_num, deep, data){
				if(row_col_num == data.index && deep == data.deep){
					return data;
				}

				for(var i = 0; i < data.lr.length; i++){
					var d = this.__getDesignNode(row_col_num, deep, data.lr[i]);
					if(d != undefined && d!= null)
						return d;
				}

				for(var i = 0; i < data.ud.length; i++){
					var d = this.__getDesignNode(row_col_num, deep, data.ud[i]);
					if(d != undefined && d!= null)
						return d;
				}

				return null;
			},
			getDesignNode : function(row_col_num, data){
				if(row_col_num == data.indexp){
					return data;
				}

				for(var i = 0; i < data.lr.length; i++){
					var d = this.getDesignNode(row_col_num, data.lr[i]);
					if(d != undefined && d!= null)
						return d;
				}

				for(var i = 0; i < data.ud.length; i++){
					var d = this.getDesignNode(row_col_num, data.ud[i]);
					if(d != undefined && d!= null)
						return d;
				}

				return null;
			},
			__design : function(rows, cols, datas, panel, istext, isline){
				var obj_this = this;
				panel.find('.xmlayout-asdfzxcv').remove();
				if($.xmlayout.__isNaN(rows)){
					throw "XMlayout info: 'rows' in function $.xmlayout __design() is not a number!";
				}
				if($.xmlayout.__isNaN(cols)){
					throw "XMlayout info: 'cols' in function $.xmlayout __design() is not a number!";
				}

				if(datas == undefined || datas == null){
					datas = new Array();
					for(var i = 0; i < rows; i++){
						var d = new Array();
						for(var j = 0; j < cols + 1; j++){
							d.push(1);
						}
						datas.push(d);
					}
				}

				var max = new Array();
				max.push({v:0, p:0});
				if(datas != null){
					if(!$.isArray(datas)){
						throw "XMlayout info: 'datas' in function $.xmlayout __design() is not a array!";
					}
					if(datas.length != rows){
						throw "XMlayout info: rows of 'datas' in function $.xmlayout __design() is not equal with '" + rows + "'!";
					}
					$.each(datas, function(i, v){
						if(!$.isArray(v)){
							throw "XMlayout info: 'datas[" + i + "]' in function $.xmlayout __design() is not a array!";
						}
						if(v.length != cols + 1){
							throw "XMlayout info: rows of 'datas[" + i + "]' in function $.xmlayout __design() is not equal with '" + (cols + 1)+ "'!";
						}

						var tv = 0, tp = 0;
						$.each(v, function(ii, vv){
							if(ii == 0){
								if(typeof vv == "number"){
									if(vv <= 0){
										throw "XMlayout info: rows of 'datas[" + i + "," + ii + "]' in function $.xmlayout __design() is " + vv + ", but it should be > 0 !";
									}
									max[0].v += vv;
								}
								else if(typeof vv == "string"){
									if($.trim(vv) == "auto")
										datas[i][ii] = 0;
									else
										max[0].p += parseInt(vv);
								}
							}
							else{
								if(typeof vv == "number"){
									if(vv <= 0){
										throw "XMlayout info: rows of 'datas[" + i + "," + ii + "]' in function $.xmlayout __design() is " + vv + ", but it should be > 0 !";
									}
									tv += vv;
								}
								else if(typeof vv == "string"){
									if($.trim(vv) == "auto"){
										throw "XMlayout info: rows of 'datas[" + i + "," + ii + "]' in function $.xmlayout __design() is " + vv + ", but it should not be 'auto' !";
									}
									else
										tp += parseInt(vv);
								}
							}
						});
						max.push({v:tv, p:tp});
					});
				}

				var midvs = new Array();
				var _midvs = new Array();
				panel.prop('mchildren', midvs);
				panel.prop('_mchildren', _midvs);
				var mhet = 0;
				$.each(datas, function(i, v){
					var md = $('<div class="xmlayout-asdfzxcv"></div>');
					if(typeof v[0] == "string")
						md.css({width : "100%", height : v[0]});
					else {
						var height = 0;
						if(max[0].v > 0 && v[0] > 0){
							height = Math.floor(v[0] * 100 / max[0].v);
							if(i != datas.length - 1){
								mhet += height;
							}
							else{
								height = 100 - mhet;
							}
						}

						if(height > 0 && max[0].p > 0)
							md.css({width : "100%", height : "calc(" + height + "% - " + (max[0].p * height / 100.0 + 0.01).toFixed(2) + "px)"});
						else if(height > 0)
							md.css({width : "100%", height : height + "%"});
					}

					var iwdh = 0;
					for(var ii = 1; ii < v.length; ii++){
						var imd = $('<div></div>');

						if(typeof v[ii] == "string")
							imd.css({width : v[ii], height : "100%", float : "left"});
						else{
							var width = 0;
							if(max[i+1].v > 0 && v[ii] > 0){
								width = Math.floor(v[ii] * 100 / max[i+1].v);
								if(ii != v.length - 1){
									iwdh += width;
								}
								else{
									width = 100 - iwdh;
								}
							}

							if(width == 0)
								imd.css({width : "auto", height : "100%", float : "left"});
							else if(width > 0 && max[i+1].p > 0)
								imd.css({width : "calc(" + width + "% - " + (max[i+1].p * width / 100.0 + 0.01).toFixed(2) + "px)", height : "100%", float : "left"});
							else if(width > 0)
								imd.css({width : width + "%", height : "100%", float : "left"});
						}


						if(isline)
							imd.css({boxSizing: "border-box", border: "1px solid #E8E8E8"});

						var midx = (i+1) + ":" + ii;
						_midvs[midx] = imd;
						if(panel.prop("midx") != undefined && panel.prop("midx") != null)
							midx = panel.prop("midx") + ";" + midx;
						imd.prop("midx", midx);
						if(istext){
							imd.css({overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap"});
							imd.append($('<span>S ' + midx + '</span>'));
							imd.attr("title", midx);
						}
						else
							imd.append($('<span>&nbsp;</span>'));
						if(panel.prop("mtagd") != undefined && panel.prop("mtagd") != null){
							imd.prop("mtagd", panel.prop("mtagd"));
						}
						else{
							imd.prop("mtagd", panel);
						}
						midvs[midx] = imd;
						imd.design_i = function(rows, cols, datas, istext, isline){
							$(this).text("");
							obj_this.__design(rows, cols, datas, $(this), istext, isline);
							var cr = $(this).prop("mtagd").find(".xmlayout-in");
							if(cr !=undefined && cr != null){
								for(var k = 0; k < cr.children().length; k++){
									var cv = cr.children().eq(k);
									if(cv.attr("xmidx") !=undefined && cv.attr("xmidx") != null){
										if($(this).prop('mchildren')[cv.attr("xmidx")] != undefined && $(this).prop('mchildren')[cv.attr("xmidx")] != null){
											$(this).prop('mchildren')[cv.attr("xmidx")].children().remove();
											$(this).prop('mchildren')[cv.attr("xmidx")].append(cv);
											k--;
										}
									}
								}
							}
						};
						imd.getPanels_i = function(midx){
							if(midx == undefined || midx == null)
								return $(this).prop('_mchildren');
							else
								return $(this).prop('_mchildren')[midx];
						};
						md.append(imd);
					}

					panel.append(md);
				});

			},
			resize : function(fun, params){
				this.resize_fun.push({
					id : 'id' + Math.random().toString().substr(2),
					fun : fun,
					params : params,
					ref_id : this.panel_tree.ref_id
				});
				$.xmlayout.resize_fun.push(this.resize_fun[this.resize_fun.length - 1]);
				return this.resize_fun[this.resize_fun.length - 1].id;
			},
			removeResize : function(rid){
				for(var i = 0; i < this.resize_fun.length; i++){
					if(this.resize_fun[i].id == rid){
						this.resize_fun.splice(i,1);
						break;
					}
				}
			},
			addRightPanelItem : function(panel, text, fun){
				if(panel.parent() == null  || panel.prop("role") != "targ_panel"){
					throw "XMlayout info: param [panel] is invalid in function $.xmlayout addRightPanelItem() !";
				}

				if(panel.parent().prop("mindex") == undefined){
					return;
				}

				var obj_this = this;
				var id = "id" + Math.random().toString().substr(2);
				var li = $('<li class="xm-list-group-item" value=' + id + ' style="position: relative;display: block;padding: 5px 15px;margin-bottom: -1px;background-color: #fff;border: 1px solid #ddd;"><span style="cursor:default; font-size:12px;">' + text + '</span></li>');
				$(panel.parent().prop("mindex")).find("ul").append(li);
				li.mousedown(function(e){
					fun(e);
				});
				li.mouseover(function(){
					$(this).css("background-color", obj_this.RPCOLOR);
				});
				li.mouseout(function(){
					$(this).css("background-color", "#fff");
				});
				return id;
			},
			removeRightPanelItem : function(panel, id){
				if(panel.parent() == null  || panel.prop("role") != "targ_panel"){
					throw "XMlayout info: param 'panel' is invalid in function $.xmlayout removeRightPanelItem() !";
				}

				if(panel.parent().prop("mindex") == undefined){
					return;
				}

				var lis = $(panel.parent().prop("mindex")).find("ul").find("li");
				for(var i = 0; i < lis.length; i++){
					if(lis.eq(i).attr("value") == id){
						lis.eq(i).remove();
						break;
					}
				}
			},
			disablePanelItem : function(panel, id, isinit){
				if(panel.parent() == null  || panel.prop("role") != "targ_panel"){
					throw "XMlayout info: param 'panel' is invalid in function $.xmlayout disablePanelItem() !";
				}

				if(panel.parent().prop("mindex") != undefined){
					var lis = $(panel.parent().prop("mindex")).find("ul").find("li");
					for(var i = 0; i < lis.length; i++){
						if(lis.eq(i).attr("value") == id){
							lis.eq(i).css("display", "none");
							if(isinit != undefined && isinit)
								lis.eq(i).prop("disable", true);
							break;
						}
					}
				}

				if(panel.parent().prop("mindex1") != undefined){
					var btns = $(panel.parent().prop("mindex1")).find("div");
					for(var i = 0; i < btns.length; i++){
						if(btns.eq(i).attr("value") == id){
							btns.eq(i).css("display", "none");
							if(isinit != undefined && isinit)
								btns.eq(i).prop("disable", true);
							break;
						}
					}
				}
			},
			useablePanelItem : function(panel, id, isinit){
				if(panel.parent() == null  || panel.prop("role") != "targ_panel"){
					throw "XMlayout info: param 'panel' is invalid in function useablePanelItem() !";
				}

				if(panel.parent().prop("mindex") != undefined){
					var lis = $(panel.parent().prop("mindex")).find("ul").find("li");
					for(var i = 0; i < lis.length; i++){
						if(lis.eq(i).attr("value") == id){
							if((isinit == undefined || !isinit) && lis.eq(i).prop("disable") != undefined && lis.eq(i).prop("disable"))
								continue;
							lis.eq(i).css("display", "block");
							break;
						}
					}
				}

				if(panel.parent().prop("mindex1") != undefined){
					var btns = $(panel.parent().prop("mindex1")).find("div");
					for(var i = 0; i < btns.length; i++){
						if(btns.eq(i).attr("value") == id){
							if((isinit == undefined || !isinit) && btns.eq(i).prop("disable") != undefined && btns.eq(i).prop("disable"))
								continue;
							btns.eq(i).css("display", "block");
							break;
						}
					}
				}

			}
		}
		return xmlayout;
	},

	addParam : function(datas){
		if(datas == null || datas == undefined || typeof datas != 'object'){
			throw "XMlayout info: param 'data' is not a object in function addParam() !";
		}
		var obj_this = this;
		$.each(datas, function(key, value){
			obj_this.xmparams[key] = value;
		});
	},

	getParam : function(key){
		return this.xmparams[key];
	},

	__play : function(){
		var obj_this = this;
		$(window).keydown(function(e){
			if(obj_this.play_panel.length <= 0)
				return;
			var code = e.which || e.keyCode;
			if(obj_this.__isNaN(code)){
				throw "XMlayout info: can't get the keyCode in function keydown()!";
			}
			if(code == 17)
				$(this).prop("ctrl", true);

			if($(this).prop("ctrl") && code == 81){
				if(!obj_this.anim_tf){
					$(document).prop("msct", $(document).scrollTop());
					$(document).prop("mscl", $(document).scrollLeft());
					$(document).scrollTop(0);
					$(document).scrollLeft(0);
					$("html").css("overflow", "hidden");

					obj_this.__playOpen();
				}
				else{
					obj_this.__playTurnoff();
					$(document).scrollTop($(document).prop("msct"));
					$(document).scrollLeft($(document).prop("mscl"));
					$("html").css("overflow", "auto");
				}
			}
		});
		$(window).keyup(function(e){
			if(obj_this.play_panel.length <= 0)
				return;
			var code = e.which || e.keyCode;
			if(obj_this.__isNaN(code)){
				throw "XMlayout info: can't get the keyCode in function keydown()!";
			}
			if(code == 17)
				$(this).prop("ctrl", false);
		});
	},
	__playOpen : function(){
		var obj_this = this;

		if(obj_this.play_panel.length == 0)
			return;

		obj_this.anim_tf = true;
		$(".xmlayout_anim").eq(0).css("display", "block");
		var pngs = obj_this.playbottom.find("center").children().eq(1).find("div>img");
		pngs.eq(0).css("visibility", "visible");
		pngs.eq(1).css("display", "block");
		pngs.eq(3).css("visibility", "visible");
		pngs.eq(2).css("display", "none");
		obj_this.playbottom.animate({bottom:"40px"}, "normal");
		obj_this.playbottom.find("center").children().eq(1).prop("display", true);

		for(var i = 0; i < obj_this.play_panel.length; i++){
			var pp = obj_this.play_panel[i].parent();
			if(pp.prop("zoom") == undefined || pp.prop("zoom") == null)
				pp = pp.parent();
			if(pp.prop("ispopped")){
				pp.css("position", "static");
			}
		}

		for(var i = 0; i < obj_this.panel_tree_list_i.length; i++){
			var pp = obj_this.panel_tree_list_i[i].panel;
			if(pp.css("display") == "none"){
				pp.prop("odisplay", "none");
				pp.css("display", "block");
			}
		}

		for(var i = 0; i < obj_this.play_panel.length; i++){
			var left = 5 + 100 * i;
			//var top = 5;
			var width = 90;
			var height = "calc(100% - 100px)";
			var top = 60;
			obj_this.play_panel[i].css({position: "fixed", zIndex: 10000001, left: left + "%", top: top + "px", width: width + "%", height: height});
			obj_this.play_panel[i].prop("mparent", obj_this.play_panel[i].parent());
			$('body').append(obj_this.play_panel[i]);
		}

		for(var i = 0; i < obj_this.resize_fun.length; i++){
			if(obj_this.resize_fun[i].params != null)
				obj_this.resize_fun[i].fun(obj_this.resize_fun[i].params);
			else
				obj_this.resize_fun[i].fun();
		}

		if(obj_this.play_panel[0].prop("mtitle") != undefined && obj_this.play_panel[0].prop("mtitle") != null){
			obj_this.playheader.find('div').eq(1).find('span').text(obj_this.play_panel[0].prop("mtitle"));
		}
		else{
			obj_this.playheader.find('div').eq(1).find('span').text("无标题");
		}

		obj_this.playheader.css("display", "block");
		$.dcdc0xdc = 0;
		$.dtdt0xdt = setInterval('if($.dcdc0xdc == 5){$.xmlayout.playbottom.children().css("opacity", 0.0);clearInterval($.dtdt0xdt);}else $.dcdc0xdc++;', 1000);
		obj_this.playbottom.mouseover(function(){
			$.xmlayout.playbottom.children().css("opacity", 1.0);
			$.dcdc0xdc = 0;
			if($.dtdt0xdt != undefined && $.dtdt0xdt != null)
				clearInterval($.dtdt0xdt);
		});
		obj_this.playbottom.mouseout(function(){
			if($.dtdt0xdt != undefined && $.dtdt0xdt != null)
				clearInterval($.dtdt0xdt);
			$.dtdt0xdt = setInterval('if($.dcdc0xdc == 5){$.xmlayout.playbottom.children().css("opacity", 0.0);clearInterval($.dtdt0xdt);}else $.dcdc0xdc++;', 1000);
		});
	},
	__playTurnoff : function(obj_this){
		var obj_this = this;
		obj_this.anim_tf = false;

		if(obj_this.anim_wd != null){
			clearInterval(obj_this.anim_wd);
			obj_this.anim_wd = null;
		}
		for(var i = 0; i < obj_this.play_panel.length; i++){
			obj_this.play_panel[i].prop("mparent").append(obj_this.play_panel[i]);
			obj_this.play_panel[i].css({position: "static", zIndex: 0, width: "100%", height: "100%"});
		}

		$(".xmlayout_anim").css("display", "none");

		for(var i = 0; i < obj_this.resize_fun.length; i++){
			if(obj_this.resize_fun[i].params != null)
				obj_this.resize_fun[i].fun(obj_this.resize_fun[i].params);
			else
				obj_this.resize_fun[i].fun();
		}

		for(var i = 0; i < obj_this.play_panel.length; i++){
			var pp = obj_this.play_panel[i].parent();
			if(pp.prop("zoom") == undefined || pp.prop("zoom") == null)
				pp = pp.parent();
			if(pp.prop("ispopped")){
				pp.css("position", "fixed");
			}
		}

		for(var i = 0; i < obj_this.panel_tree_list_i.length; i++){
			var pp = obj_this.panel_tree_list_i[i].panel;
			if(pp.prop("odisplay") == "none"){
				pp.prop("odisplay", "block");
				pp.css("display", "none");
			}
		}

		obj_this.playbottom.animate({bottom:"0px"}, "normal");
		obj_this.playbottom.children().css("opacity", 1.0);
		obj_this.playbottom.find("center").children().eq(1).prop("display", false);

		obj_this.playheader.css("display", "none");
		if($.dtdt0xdt != undefined && $.dtdt0xdt != null)
			clearInterval($.dtdt0xdt);
		obj_this.playbottom.children().css("display", "block");
		obj_this.playbottom.unbind('mouseover');
		obj_this.playbottom.unbind('mouseout');
	},
	__anim : function(tag){
		var obj_this = this;
		if(obj_this.play_panel.length > 1){
			var np = obj_this.playheader.prop("np");
			var ap = obj_this.playheader.prop("ap");
			if(tag == 0){
				np++;
				if(np > ap)
					np = 1;
				obj_this.playheader.prop("np", np);
				obj_this.playheader.find('span').text(np + "/" + ap);
				if(obj_this.play_panel[1].prop("mtitle") != undefined && obj_this.play_panel[1].prop("mtitle") != null){
					obj_this.playheader.find('div').eq(1).find('span').text(obj_this.play_panel[1].prop("mtitle"));
				}
				else{
					obj_this.playheader.find('div').eq(1).find('span').text("无标题");
				}
				for(var i = 0; i < 2; i++){
					var left = 5 + 100 * i;
					if(i == 1)
						obj_this.play_panel[i].css("left", left + "%");

					left -= 100;
					if(i == 1){
						obj_this.play_panel[i].animate({left:left + "%"}, "normal", null, function(){
							var p0 = obj_this.play_panel[0];
							for(var j = 1; j < obj_this.play_panel.length; j++){
								obj_this.play_panel[j-1] = obj_this.play_panel[j];
							}
							obj_this.play_panel[obj_this.play_panel.length - 1] = p0;
							obj_this.playfi = false;
						});
					}
					else{
						obj_this.play_panel[i].animate({left:left + "%"}, "normal");
					}
				}
			}
			else if(tag == 1){
				np--;
				if(np < 1)
					np = ap;
				obj_this.playheader.prop("np", np);
				obj_this.playheader.find('span').text(np + "/" + ap);
				if(obj_this.play_panel[obj_this.play_panel.length - 1].prop("mtitle") != undefined && obj_this.play_panel[obj_this.play_panel.length - 1].prop("mtitle") != null){
					obj_this.playheader.find('div').eq(1).find('span').text(obj_this.play_panel[obj_this.play_panel.length - 1].prop("mtitle"));
				}
				else{
					obj_this.playheader.find('div').eq(1).find('span').text("无标题");
				}
				for(var i = 0; i < 2; i++){
					var left = 5 - 100 * i;
					if(i == 1)
						obj_this.play_panel[obj_this.play_panel.length - 1].css("left", left + "%");

					left += 100;
					if(i == 1){
						obj_this.play_panel[obj_this.play_panel.length - 1].animate({left:left + "%"}, "normal", null, function(){
							var p0 = obj_this.play_panel[obj_this.play_panel.length - 1];
							for(var j = obj_this.play_panel.length - 1; j > 0; j--){
								obj_this.play_panel[j] = obj_this.play_panel[j-1];
							}
							obj_this.play_panel[0] = p0;
							obj_this.playfi = false;
						});
					}
					else{
						obj_this.play_panel[i].animate({left:left + "%"}, "normal");
					}
				}
			}
		}
		else{
			obj_this.playfi = false;
		}
	},
	__header : function(){
		var div = $('<div style="position: fixed; width: 350px; height: auto; left:calc(50% - 175px); left:-webkit-calc(50% - 175px); left:-moz-calc(50% - 175px); top:10px; z-index: 10000003;">'
				+ '<div style="width: 80px; height: 23px; background-color: white; border-top-left-radius: 5px; border-bottom-left-radius: 5px; float: left; border-right: 1px solid gray;"><center><span style="font-size:16px; font-weight:bold;"></span></center></div>'
				+ '<div style="width: 265px; height: 23px; background-color: white; border-top-right-radius: 5px; border-bottom-right-radius: 5px; float: left; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><span style="font-size:15px; margin-left: 5px;"></span></div>'
				+ '</div>');
		div.prop('np', 1);
		div.css("display", "none");
		return div;
	},
	__bottom : function(){
		var obj_this = this;
		var div = $('<div style="position: fixed; width: 168px; height: 0px; bottom:-0px;  z-index: 10000003; background-color:red; left:calc(50% - 84px); left:-moz-calc(50% - 84px); left:-webkit-calc(50% - 84px);">'
				+ '<center>'
				+ '<div style="width: 80px; height: 20px; background-color: #848484; border-radius: 4px; margin-top:-20px;"></div>'
				+ '<div style="width: 168px; height: 38px; background-color: #848484; border-radius: 4px;"><div style="margin-left:23px;"></div></div>'
			+ '</center>'
		+ '</div>');

		var pp = div.find("center").children().eq(0);
		pp.append(obj_this.__getPNGs(10));
		pp.find("img").css("opacity", 0.4);
		pp.find("img").mouseover(function(e){
			$(this).css("opacity", 1);
		});
		pp.find("img").mouseout(function(e){
			$(this).css("opacity", 0.4);
		});
		pp.find("img").click(function(e){
			var sib = div.find("center").children().eq(1);
			if(sib.prop("display")){
				obj_this.__playTurnoff();
				$(document).scrollTop($(document).prop("msct"));
				$(document).scrollLeft($(document).prop("mscl"));
				$("html").css("overflow", "auto");
				sib.prop("display", false);
			}
			else{
				$(document).prop("msct", $(document).scrollTop());
				$(document).prop("mscl", $(document).scrollLeft());
				$(document).scrollTop(0);
				$(document).scrollLeft(0);
				$("html").css("overflow", "hidden");
				obj_this.__playOpen();
				sib.prop("display", true);
			}
		});

		pp = div.find("center").children().eq(1).find("div");
		var img6 = obj_this.__getPNGs(6);
		img6.click(function(e){
			if(!obj_this.playfi){
				obj_this.playfi = true;
				obj_this.__anim(0);
			}
		});
		pp.append(img6);
		pp.append($("<span style=\"font-family:'Times New Roman';\">&nbsp;&nbsp;</span>"));
		var img7 = obj_this.__getPNGs(7);
		pp.append(img7);
		var img8 = obj_this.__getPNGs(8);
		pp.append(img8);
		pp.append($("<span style=\"font-family:'Times New Roman';\">&nbsp;&nbsp;</span>"));
		var img9 = obj_this.__getPNGs(9);
		img9.click(function(e){
			if(!obj_this.playfi){
				obj_this.playfi = true;
				obj_this.__anim(1);
			}
		});
		pp.append(img9);
		img8.css("display", "none");
		pp.find("img").css("margin-top", "2px");
		pp.find("img").css("opacity", 0.4);
		pp.children().css("float", "left");
		pp.find("img").mouseover(function(e){
			$(this).css("opacity", 1);
		});
		pp.find("img").mouseout(function(e){
			$(this).css("opacity", 0.4);
		});

		var div2 = $('<div class="xmlayout_anim" style="position:fixed; left:0px; top:0px; width:100%; height:100%; z-index:10000000; background-color:black; opacity:1.0;"></div>');
		var div1 = $('<div class="xmlayout_anim" style="position:fixed; left:0px; top:0px; width:100%; height:100%; z-index:10000002; opacity:0.0;"></div>');

		img7.click(function(e){
			if(obj_this.anim_wd == null){
				$(this).css("display", "none");
    			img6.css("visibility", "hidden");
    			img9.css("visibility", "hidden");
    			img8.css("display", "block");
    			div1.css("display", "block");
    			obj_this.anim_wd = setInterval("$.xmlayout.__anim(0)", obj_this.timer);
    			obj_this.playfn = true;
			}

		});
		img8.click(function(e){
			if(obj_this.anim_wd != null){
				$(this).css("display", "none");
    			img6.css("visibility", "visible");
    			img9.css("visibility", "visible");
    			img7.css("display", "block");
    			div1.css("display", "none");
    			clearInterval(obj_this.anim_wd);
    			obj_this.anim_wd = null;
    			obj_this.playfn = false;
			}
		});

		$(window.document.body).append(div2);
		$(window.document.body).append(div1);
		$(".xmlayout_anim").css("display", "none");

		$(window).keydown(function(e){
			if(obj_this.anim_tf){
				var code = 0;
				if(e.which != undefined && e.which != null)
					code = e.which;
				else if(e.keyCode != undefined && e.keyCode != null)
					code = e.keyCode;
				else{
					throw "can't get the keyCode in function keydown()!";
				}
				if(code == 37){
					if(!obj_this.playfi && !obj_this.playfn){
						obj_this.playfi = true;
						obj_this.__anim(0);
					}
				}
				else if(code == 39){
					if(!obj_this.playfi  && !obj_this.playfn){
						obj_this.playfi = true;
						obj_this.__anim(1);
					}
				}
				else if(code == 38 && obj_this.anim_wd == null){
					img7.css("display", "none");
        			img6.css("visibility", "hidden");
        			img9.css("visibility", "hidden");
        			img8.css("display", "block");
        			div1.css("display", "block");
        			obj_this.anim_wd = setInterval("$.xmlayout.__anim(0)", obj_this.timer);
        			obj_this.playfn = true;
				}
				else if(code == 40 && obj_this.anim_wd != null){
					img8.css("display", "none");
        			img6.css("visibility", "visible");
        			img9.css("visibility", "visible");
        			img7.css("display", "block");
        			div1.css("display", "none");
        			clearInterval(obj_this.anim_wd);
        			obj_this.anim_wd = null;
        			obj_this.playfn = false;
				}
			}
		});

		return div;
	},
	setPlay : function(panels){
		if(panels.length == undefined || panels.length == null){
			throw "XMlayout info: param 'panels' is not a array in function setPlay(panels)!";
			return;
		}

		this.play_panel.length = 0;
		for(var i = 0; i < panels.length; i++){
			if(panels[i].prop("role") != "targ_panel"){
				throw "XMlayout info: the panel whoes index is " + i + " in array param 'panels' is invalid !";
				continue;
			}
			this.play_panel.push(panels[i]);
		}

		if(this.play_panel.length > 0){
			this.playbottom.css("display", "block");
			this.playheader.prop("ap", this.play_panel.length);
			this.playheader.prop("np", 1);
			this.playheader.find("span").text("1/" + this.play_panel.length);
		}
		else{
			this.playbottom.css("display", "none");
		}

	},
	addPlay : function(panels){
		if(panels.length == undefined || panels.length == null){
			throw "XMlayout info: param 'panels' is not a array in function addPlay(panels)!";
		}

		for(var i = 0; i < panels.length; i++){
			if(panels[i].prop("role") != "targ_panel"){
				throw "XMlayout info: the panel whoes index is " + i + " in array param 'panels' is invalid !";
			}
			this.play_panel.push(panels[i]);
		}

		if(this.play_panel.length > 0){
			this.playbottom.css("display", "block");
			this.playheader.prop("ap", this.play_panel.length);
			this.playheader.prop("np", 1);
			this.playheader.find("span").text("1/" + this.play_panel.length);
		}
		else{
			this.playbottom.css("display", "none");
		}
	},
	__clearInvalidObjects : function(){
		var obj_this = this;

		for(var i = 0; i < obj_this.panel_ids.length; i++){
			if(!$("#" + obj_this.panel_ids[i])[0] || $("#" + obj_this.panel_ids[i]).prop("isDestory")){
				for(var j = 0; j < obj_this.panel_tree_list_i.length; j++){
					if(obj_this.panel_tree_list_i[j].ref_id == obj_this.panel_ids[i]){
						obj_this.panel_tree_list_i.splice(j, 1);
						j--;
					}
				}

				for(var j = 0; j < obj_this.play_panel.length; j++){
					if(obj_this.play_panel[j].prop('ref_id') == obj_this.panel_ids[i]){
						obj_this.play_panel.splice(j, 1);
						j--;
					}
				}

				for(var j = 0; j < obj_this.resize_fun.length; j++){
					if(obj_this.resize_fun[j].ref_id == obj_this.panel_ids[i]){
						obj_this.resize_fun.splice(j, 1);
						j--;
					}
				}
				obj_this.panel_ids.splice(i, 1);
				i--;
			}
		}

		if(obj_this.play_panel.length == 0){
			obj_this.playbottom.css("display", "none");
		}
	},
	__getPNGs : function(index){
		var obj_this = this;

		if(obj_this.__isNaN(index)){
			throw "XMlayout info: the param in function __getPNGs() is not a number!";
		}

		switch(index){
			case 1:
				return $('<img title=' + $.xmlayout.LANG.zi + ' src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAC8AAAAvCAYAAABzJ5OsAAACgUlEQVRoQ+2a4TEEQRCFnwiQgRBcBIgAESACRIAIEAEXASLgIiAEGSAC6tvaUXNr5mZ3pkfVXl1XXe2P3e1+/aanu7fn1mQj25J2JHHdkrQbUPsi6V3Sm6RZey2yvlbwNkCPJB1L2sjQ8ynpXtI015Ec8LB6EWE3w4fmFVblqr321jEEPOFwLemgt/bhDz5KOm/DK/l2X/CEBsBzwiMJovMA4XQiCUcWSh/wd21cp3RZ379pVyGqNwU+F3hI73eGd2xoViEoi8DnAseQFXh0RR2IgS8Bbg0efbeSzrr0h8CzOQFfIpbMOxyH3U3cNUI6fDXIKjXAk4UmfhrtGnkwyuM1wLMCpE9WoBHfCJXzuSRWvHdrgcfEnqvEvhGAhxqqHH9qgqeVwIFf5mmyiHUrqQkejMT+mzNCLqVDtJLa4JvU6Yx8GGQY3/Ha4Mk8mxixDpkaRSoUERPAU7noGC2lNvNgPceIdbz/F/NTwJN6+P60lP9gfoaRWKuaapctnU3pChK8Ap+izeD+inkDErNURJkfdbYZdZ4fdYUddW/DDqJLW8/aSuGXalfYL7pgZ4Tp1OmIwM/189ahU5v5uS8pSLdMmTXBczDRfGsvzfQAZ5iL7BvEfi3mn/y5UmhixplRaeapAZ4Mw97kXKuRpZpVOqdKWwZr5ntPiS0csATPaSGT6z+S+tTLXQEr8FHgsZjvepjjADWjK0PnoMFQ8ZWmmHfPsmy0EKVZqE8WJqtgz+Q00Bnk4AEHLOpAzAnyOC36bzpc5G1f5n0dLP+l8ayHko/OULhF8eeAd8ooGCwvv5xwIjzYT/wojIOlBLxvDEdYEfevj9AEDnbdvz5gOAuwb/QHFf2LKz2RYHgAAAAASUVORK5CYII=" style="width: 20px;"/>');
			case 2:
				return $('<img title=' + $.xmlayout.LANG.zo + ' src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAC8AAAAvCAYAAABzJ5OsAAACSElEQVRoQ+2a4VHDMAyFXycANmAEOgEwATABMAF0AmACYALoBMAE0AnoCGwATAD35eKeG+zacUIS96q7XH40lp9k+UmWO1I7sidpXxLvXUkHDrVvkj4kzSXNynej2UcNRgP0VNKZpO0EPV+SHiVNUw1JAY9XrzzeTbChGMKq3JTvaB11wBMOt5KOo7XX//BZ0qQMr+DoWPCEBsBTwiMIovIB4XQuCUNWSgz4hzKuQ7ra/v2uXAWv3hD4voAbwGxoVsEpq8D3DTxogA/8UIAbA+4lXVbd7wLP5gT80OSkuomr4KHD945Ypa5zYKGxTaNV8E//zON1AVe/hz5ZgUJs8GTO16baOxh/aDKxDR7groKqAzy1pqCUwICF5ymyiPVchNifG8+TDKgQc5GCOg34z4EyjM+ZMM8O4HMLGWPQGPBkLirG3GQC+Nzi3Th5Cnioh/NnbjID/I8Hdahc7tJYp4M34DtYgo3nO3Cycwqv57Nmm6x5PusMm3Vtww6hStvqazcmzPtNFWyyKN2piwQlfQ1ZqudzC52lkxQezIUyuZgoztpr0z3AGPoiR30FcsS8L3ZfydUx485oiMwDw7A3udcqZK16lcaooZUM0V3ioRnAbSGd6z8SOur1vQJe4L6Yr1rYlwHOULHBhTxvvmXZKCG6YCFYhflauQ00BnDxgAH/mQfgcUr0BR2u4v5Yz9s6SM3XLfd6SPnopESJlhTwRjkJg+XlSQknwoP9xENirC1NwNuTYQgrYv714erA4V3zrw88nATYnvQX+/FsK9OT5p8AAAAASUVORK5CYII=" style="width: 20px;"/>');
			case 3:
				return $('<img title=' + $.xmlayout.LANG.fs + ' src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAC0AAAAtCAYAAAA6GuKaAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyNpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNS1jMDE0IDc5LjE1MTQ4MSwgMjAxMy8wMy8xMy0xMjowOToxNSAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIChNYWNpbnRvc2gpIiB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOjg5MDMzMjFBNDhDQjExRTdBMTcyRDM1MDg2MDdFMjkxIiB4bXBNTTpEb2N1bWVudElEPSJ4bXAuZGlkOjg5MDMzMjFCNDhDQjExRTdBMTcyRDM1MDg2MDdFMjkxIj4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InhtcC5paWQ6ODkwMzMyMTg0OENCMTFFN0ExNzJEMzUwODYwN0UyOTEiIHN0UmVmOmRvY3VtZW50SUQ9InhtcC5kaWQ6ODkwMzMyMTk0OENCMTFFN0ExNzJEMzUwODYwN0UyOTEiLz4gPC9yZGY6RGVzY3JpcHRpb24+IDwvcmRmOlJERj4gPC94OnhtcG1ldGE+IDw/eHBhY2tldCBlbmQ9InIiPz4MqOVTAAAEiklEQVR42sRZO0wbQRA9d0aJEjqgiGyEASs0IAroIG7idDRIlJSgUFDSgZ3CdHRQGlFgKhAVXYyEZCyhYCSU4AawlSA6sFEi7uTA5s3J56zP9/V9WGlk+7y3827uzdvZ3QBjTHDY3sNiZ2dno5VKJSpJ0rt8Pv8a194ODg5Wu7u7f+P7z8nJySI+v8G+wn6Q30Ag0J5HurkNC19fXyfX1tYu4/E4PbUlGxkZYSsrK6xQKFxijCSN8/z83DK+1jXe7IKN7O3tbUWj0ZpVoHoWDocZHroGgFs0rh0cVjsGj4+PE+Pj4yJeKXMKWA0egRDrkQ+6Ahpg+2dmZgrkgADrgVaut/tQ4DzRpgCf/Y5AY4yPw8PDVTcja/TASFp2dHRUhe94W6Cz2ex0Z2enZNdxO/3hh83OzhJNGLX9/X0JH9O2QIMSsWAwKDkFZmQK0N3dXca329tb+f9MJkPAY5ZAE4eRHBUvKKCOqFbb2NiQ+yNoLJfLVXBpwAx0EAlx6sbr13v1Zo0SUvHV29vLEMRTtao0gYZuJtwCjARuAQpNNgSsUIO3qakpVpfDVtCY4SJ4JaLbGpxOp5nVtr6+rjkOVEzkJ6AG6MXFxW2vkg4zKCsWiy2RVv8mamjdj0mN/t5uAg3e9HV0dPz1SoeJKiimDAFrUYM3UO0J3fpk0FScLC0tpbwEfH9/b0oNRTX0jAoztNVGpPH6SlqUcEoRAnx3d9cC8Pz8XFM1jMYiCUQry6CRgENezHp6EabSlABcXFw0aGJGDcV2dnboliEhlUp9dnuKNgLMK8vj42ODGlZ8kN6jLQio4NJ+cJgHrJgih2bU4B8ULS3ghpxfgPlo0nfqb5UaHK9zAtDf+B1htSrY8YP2S4A+V18KcDtWKpWqgtWEowLdDuDl5WXHqxktOzw8lLcPqlaAHxwcsK6uLt8irPfAkMoHS5ymKFNDFWgKOJFIeLIU4zh9I6AYyZl1npubkwGRrlJ9TPsXasA0SVCE3aKC3jiyeqBeNdVprBcb4E5OTnxLOvVDNHQaxdKC2QrZrIDnAbu9L8KPVy+aFmjVbVh7KNQwA+w2WC2j6V6uPahqQjTLVqihjnhdfnwzFHdU5QVk0ChEVrU69fT0mNbBtA6k5PQaMKlVUz0NDe7DH09m1NDjNSUm0URvAnLDILf/Vy7KuguF07YZNfSa8jBU/NB6zm3AdTHItCxsseKNoIOonlDMwFKUqcSkpb6HCSjyG5NN+x6orZNKx/n5eV2wXgPllajO5S+GO0yIcEGLGn5EVKt+Rr4VDHeY6kk5EAqFKi8BVK31oIW8l6c+ztDcNc1kMjEAlfzUYA3lsr5rqhim92ncLLlZ7Fg1Cpjt/Wku4nHwqupFtaZnWDw8wPcnx2cuSnJ6nXQIkvMzF15VQJck1pOiF8kHqZVcPd1SHUpG4GALzmpuAJ6YmKhhUvPsHLHJUG2FUQskMfVf2eUzTRaoU64AVj6xbcd/wIWz8aHNzc0P5XJ5FN+j+Xw+JIriKwB+g98PY2Njf8DXMhbFRcgYnY1nYd+dOPwnwAASbPFBAW4BygAAAABJRU5ErkJggg==" style="width: 20px;"/>');
			case 4:
				return $('<img title=' + $.xmlayout.LANG.pp + ' src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAC0AAAAtCAYAAAA6GuKaAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyNpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNS1jMDE0IDc5LjE1MTQ4MSwgMjAxMy8wMy8xMy0xMjowOToxNSAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIChNYWNpbnRvc2gpIiB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOkY0MTU2M0NCNDhENjExRTdBMTcyRDM1MDg2MDdFMjkxIiB4bXBNTTpEb2N1bWVudElEPSJ4bXAuZGlkOkY0MTU2M0NDNDhENjExRTdBMTcyRDM1MDg2MDdFMjkxIj4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InhtcC5paWQ6RjQxNTYzQzk0OEQ2MTFFN0ExNzJEMzUwODYwN0UyOTEiIHN0UmVmOmRvY3VtZW50SUQ9InhtcC5kaWQ6RjQxNTYzQ0E0OEQ2MTFFN0ExNzJEMzUwODYwN0UyOTEiLz4gPC9yZGY6RGVzY3JpcHRpb24+IDwvcmRmOlJERj4gPC94OnhtcG1ldGE+IDw/eHBhY2tldCBlbmQ9InIiPz6bSXbNAAAEv0lEQVR42sxZPUwbWRB+tpAwSgIUSNDkDDouMUFISCmAjtDEVNAg7I7SKCmgAioIKUgHHXQWFDYd7qDK0kGDTXOHLZEAQsLQgCE5YcOFl282eLVedr0/b4NvpCfvru15386b+d7MPA/nnNkR+r3H41E/epXL5fowXh8eHgZw/3x7e/tpoVCoa2xsvGxra/uO/xx3dnam6+vrd/D9Z4x/LOgtD8LBaL64uJiNx+NfQqEQBxh6c9PR3NzMI5EIX19f/0L/Jz1O5n/w4O7urtwfWlOp1AqA3hoBg7V0r7WDXnRsbOx2b29vhfS6AloNHte+g4ODDyMjI3krFrUz6urq+PT0dB76yfI+R6B1xl9zc3Mpn89nCqCcZc1GU1MTlyQpRfOJgn4LV7j8XUD1xvz8/CXmDToCvbW1NdTd3V1w2x2svCwMVQCGIaP4MgLcB4oqiACwyigmwPv0CELXhwcHB3MiE5L/g2XkTxH3QSzlgOeFmaV9k5OTSdGlB5VxEvoU1QVOTxJ7qS1eYvpkMvlBdBKy7snJiQw6m81yK6xTbmBXJVWzRpZu7e3tzYsGFZaUq2V4eNixruL1zMxMXr0BKaDX1tZiolYmrr2+vi4BTb4tqrempoZj84lpQf8JtvjPBY7leoLA5i7EyQ/CqYAGxc25aWXESFlrO2EUolDIJwX0xMTEoSjoRCKhC7goWEk3mOSoCLqdUkarVtD7ngCZCWJGGDQSNlLVTq7xTlQZAbIiota+d5H3bHFxMSqiCPkJtyooGoQTLOTfUe/Z2dlLvYrGaumDl7Zcqg0MDDBsFrbKOy2O09PTl17UdX6jMswMPKiMYcmVe9SGDPpKfkP36XRavgbfUtDbrkk1+v7wAvlTm4WsIqg4GFiDhcNhGVBPTw9bXl4uqT3pHsUtAyWy0dFRBneSr50K8D6rQhVdq14Kvepc7xmCgvX395MSS8sKN2RLS0vyQD7iGPTNzU1tFRRc4brWCJyR4GUdx0E+n3cMGhi/ecHR351MbMX/7Ire3MVnxU+/3//NC/86cnNiOzq0IMu5ZvGT8HpBQRmny2zHYm6tDEBnvIFAYMftZf5dQgEMit2pCgaDEjGBXmBhr2fweVuKUUg8uEcSb0sHcfvq6uqDgL3XLclWBbgjbcmE7ZJXUs7Pz+V0V40rGo1SlveLl2Ox2CedbMoVMUpVrcjU1FQJ6JJ8mioCvNUPdUKPpeGVFkrGipiQMiiVi4cqcYr2hYWF2Pj4eLjoPwhQFgqFHAVMQ0MD6+joYMfHx2x/f9+RDspjNjY2lF0a1c8qgjCs7U+3wsKud0XdGLByXt2YLGnWwNFn/2+AiRQgHx80a9QdJtBK6rGBlSsMQJcpbd9ar/P/oqWlJVcJ0Frw2EMs9fKUrik2nEIl3QJEYKtrKg+U60OVAo5dWO5POzoJ2NzcDELB5WMCRkxdYe5+4TMXlFGPEpyRSMSVMxeFVYgOjXhctC3Q1dVVkCTJ+emW2TkiKGgFvn7rku/exuNxsXNEE8AlJ7aYbBbb/Fe7Zyv3p7ZfEeiOT2w9Rgm/UetA53l7IpF4s7u7+xrPA5lMxp/NZp/cF8tXoK5/qUSqrq5Og3d3kD9I0PG3SHX0U4ABAM74kh2xTlAgAAAAAElFTkSuQmCC" style="width: 20px;"/>');
			case 5:
				return $('<img title=' + $.xmlayout.LANG.bt + ' src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAC0AAAAtCAYAAAA6GuKaAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyNpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNS1jMDE0IDc5LjE1MTQ4MSwgMjAxMy8wMy8xMy0xMjowOToxNSAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIChNYWNpbnRvc2gpIiB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOkY0MTU2M0NGNDhENjExRTdBMTcyRDM1MDg2MDdFMjkxIiB4bXBNTTpEb2N1bWVudElEPSJ4bXAuZGlkOkY0MTU2M0QwNDhENjExRTdBMTcyRDM1MDg2MDdFMjkxIj4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InhtcC5paWQ6RjQxNTYzQ0Q0OEQ2MTFFN0ExNzJEMzUwODYwN0UyOTEiIHN0UmVmOmRvY3VtZW50SUQ9InhtcC5kaWQ6RjQxNTYzQ0U0OEQ2MTFFN0ExNzJEMzUwODYwN0UyOTEiLz4gPC9yZGY6RGVzY3JpcHRpb24+IDwvcmRmOlJERj4gPC94OnhtcG1ldGE+IDw/eHBhY2tldCBlbmQ9InIiPz4ofirfAAAE0UlEQVR42sxZPUwbSRRe56LE0kUBKkNxsRGYoOMksGgojRtMBQ2J3YF0J0B3BXTmKnAK00FnUcVQxKbhpzI0gQrTIIOUHKRIwLpD0OGN7mAtK2y+t1pLi9k1O7NzCU96sr3e2fnm7ZtvvjfjUlVVcmg/w0Pw7t3d3XZFUX4qFotPDg4O6nBNbm9v/9fj8fyN70fBYHAPn2/hfznqkUDb8evra8313z54/PDw8OPc3Jza09NDI7flXV1daiKR+Ii2cf05EquzNmiFL6XT6TIiaBuolft8vnIymVzSnysctBs+s7W1pVCkzAC4XC5u8AiAks1m43o/QkD7c7lcPhKJOAJnp104HM7D/E5B9wGwzJMKmHSq2+1mHiRSRkaf4co8YgU9tLq6Wqqvr2eKIgHFJFPJLi4uVDAH84DxjBLmzRBrpEPIsRIBYO1wbGxMNVosFuPN/RIwhMzwPTBhQT/4dmVkZOQROJeZQhsbG2/8xsBvUaxNexSNRleApa36j2rQbty0PD4+Xnd+fs7F+4gk0/VaJstyHYKXIVy1QE8tLy8H9vf3uUFaRZJn5aU2R0dHgcnJyT+tVsTW4+NjpZLHvNQ2PT19I6dnZma4+duAQQEVtprldHxqaupxJY8FaBLNiLZ4U8WA4TEGH69OjxakxItMJiMEqHHAZgB5ArK2tvZyY2OjRQOtP+DXhYWFHyRBxjPpjO0s2j9IpVK/aV/0G6IYBXNnUHcStAgzUKywWlurN2D1JjY3N6P0+RDeAZrznpycMEeG2mDySoVCQVpcXJQgqCzv7ezslHp7e6XR0VEJS7XU3NzM3B90+rPt7e0OGtXvNON5FRvp6VpWLpdvXaM2vAyFFfcPAv2axA0vLWEFVK+urlS7RvdSG97+kFaviT2e865+ZNQWM9v2/aAuyWF/zynSp01NTY4qECoM7NjZ2ZnKI8Kq0ugfAi07LZvIIWPvBD0xMeG40kE7mWY/d/Vh/E3RxuonPMqmjokhJNLk0AeWoAcHB4X0gUB9Jm1waqc6seMEzMxoMKICAz+lnN4B0av/Z7QFRpnococo74PX6xWmNWZnZ2/8h9WWiRLvEmIA/YFA75EWEKXq1tfXb/AwqiBHIspEt+y50GEHxNK7/v7+WzcMDw9rOoHVSAz19fVR1SGhEmIafGVwpGdQkUvVdSqu/aLdiFK/YKQj4zbAtzIzuqQtCOOSD1wFXHZpRQDYI02RqVgkEpFYUkZElWOWPsClvW3DG0xrA9DrrhasaF+MIujy8lK9D2bYkf2STCZbtBQyROlNIBCIVipxijRF/HsZRT6Xy0lUnOh4MkjZ6K1qPJvNKgIXAZGuIBP8Vtti8YGBgXsHGinyqtZenpu2dRsaGhzvOYtyMEYezOI27qCabT62IU2K9yTKRfByW81dU8NoQolEovSdAZdisVjIbJ+65v40ishvDlxPyRKYa4j3JCAMbpSFiXd7/hnVer/jMxfkeF6U5q5VDaGPPPryizzdiqPGU+6q8TgZp4RUFHq6Vb0dvARRXxYU6XI4HLY8R+Q5KKrlPjpxnZ+f/2TntLY6+iiCP4Gd6MTXZwdktbsEKLQOeG8qleqGBiZp6EW18iN08FOaVPD/gsFggc7GPR7PHtKANvzeO+nwqwADAO3OmCHzO2owAAAAAElFTkSuQmCC" style="width: 20px; display: none;"/>');
			case 11:
				return $('<img title=' + $.xmlayout.LANG.cp + ' src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAC8AAAAvCAYAAABzJ5OsAAAACXBIWXMAAA7DAAAOwwHHb6hkAAAKTWlDQ1BQaG90b3Nob3AgSUNDIHByb2ZpbGUAAHjanVN3WJP3Fj7f92UPVkLY8LGXbIEAIiOsCMgQWaIQkgBhhBASQMWFiApWFBURnEhVxILVCkidiOKgKLhnQYqIWotVXDjuH9yntX167+3t+9f7vOec5/zOec8PgBESJpHmomoAOVKFPDrYH49PSMTJvYACFUjgBCAQ5svCZwXFAADwA3l4fnSwP/wBr28AAgBw1S4kEsfh/4O6UCZXACCRAOAiEucLAZBSAMguVMgUAMgYALBTs2QKAJQAAGx5fEIiAKoNAOz0ST4FANipk9wXANiiHKkIAI0BAJkoRyQCQLsAYFWBUiwCwMIAoKxAIi4EwK4BgFm2MkcCgL0FAHaOWJAPQGAAgJlCLMwAIDgCAEMeE80DIEwDoDDSv+CpX3CFuEgBAMDLlc2XS9IzFLiV0Bp38vDg4iHiwmyxQmEXKRBmCeQinJebIxNI5wNMzgwAABr50cH+OD+Q5+bk4eZm52zv9MWi/mvwbyI+IfHf/ryMAgQAEE7P79pf5eXWA3DHAbB1v2upWwDaVgBo3/ldM9sJoFoK0Hr5i3k4/EAenqFQyDwdHAoLC+0lYqG9MOOLPv8z4W/gi372/EAe/tt68ABxmkCZrcCjg/1xYW52rlKO58sEQjFu9+cj/seFf/2OKdHiNLFcLBWK8ViJuFAiTcd5uVKRRCHJleIS6X8y8R+W/QmTdw0ArIZPwE62B7XLbMB+7gECiw5Y0nYAQH7zLYwaC5EAEGc0Mnn3AACTv/mPQCsBAM2XpOMAALzoGFyolBdMxggAAESggSqwQQcMwRSswA6cwR28wBcCYQZEQAwkwDwQQgbkgBwKoRiWQRlUwDrYBLWwAxqgEZrhELTBMTgN5+ASXIHrcBcGYBiewhi8hgkEQcgIE2EhOogRYo7YIs4IF5mOBCJhSDSSgKQg6YgUUSLFyHKkAqlCapFdSCPyLXIUOY1cQPqQ28ggMor8irxHMZSBslED1AJ1QLmoHxqKxqBz0XQ0D12AlqJr0Rq0Hj2AtqKn0UvodXQAfYqOY4DRMQ5mjNlhXIyHRWCJWBomxxZj5Vg1Vo81Yx1YN3YVG8CeYe8IJAKLgBPsCF6EEMJsgpCQR1hMWEOoJewjtBK6CFcJg4Qxwicik6hPtCV6EvnEeGI6sZBYRqwm7iEeIZ4lXicOE1+TSCQOyZLkTgohJZAySQtJa0jbSC2kU6Q+0hBpnEwm65Btyd7kCLKArCCXkbeQD5BPkvvJw+S3FDrFiOJMCaIkUqSUEko1ZT/lBKWfMkKZoKpRzame1AiqiDqfWkltoHZQL1OHqRM0dZolzZsWQ8ukLaPV0JppZ2n3aC/pdLoJ3YMeRZfQl9Jr6Afp5+mD9HcMDYYNg8dIYigZaxl7GacYtxkvmUymBdOXmchUMNcyG5lnmA+Yb1VYKvYqfBWRyhKVOpVWlX6V56pUVXNVP9V5qgtUq1UPq15WfaZGVbNQ46kJ1Bar1akdVbupNq7OUndSj1DPUV+jvl/9gvpjDbKGhUaghkijVGO3xhmNIRbGMmXxWELWclYD6yxrmE1iW7L57Ex2Bfsbdi97TFNDc6pmrGaRZp3mcc0BDsax4PA52ZxKziHODc57LQMtPy2x1mqtZq1+rTfaetq+2mLtcu0W7eva73VwnUCdLJ31Om0693UJuja6UbqFutt1z+o+02PreekJ9cr1Dund0Uf1bfSj9Rfq79bv0R83MDQINpAZbDE4Y/DMkGPoa5hpuNHwhOGoEctoupHEaKPRSaMnuCbuh2fjNXgXPmasbxxirDTeZdxrPGFiaTLbpMSkxeS+Kc2Ua5pmutG003TMzMgs3KzYrMnsjjnVnGueYb7ZvNv8jYWlRZzFSos2i8eW2pZ8ywWWTZb3rJhWPlZ5VvVW16xJ1lzrLOtt1ldsUBtXmwybOpvLtqitm63Edptt3xTiFI8p0in1U27aMez87ArsmuwG7Tn2YfYl9m32zx3MHBId1jt0O3xydHXMdmxwvOuk4TTDqcSpw+lXZxtnoXOd8zUXpkuQyxKXdpcXU22niqdun3rLleUa7rrStdP1o5u7m9yt2W3U3cw9xX2r+00umxvJXcM970H08PdY4nHM452nm6fC85DnL152Xlle+70eT7OcJp7WMG3I28Rb4L3Le2A6Pj1l+s7pAz7GPgKfep+Hvqa+It89viN+1n6Zfgf8nvs7+sv9j/i/4XnyFvFOBWABwQHlAb2BGoGzA2sDHwSZBKUHNQWNBbsGLww+FUIMCQ1ZH3KTb8AX8hv5YzPcZyya0RXKCJ0VWhv6MMwmTB7WEY6GzwjfEH5vpvlM6cy2CIjgR2yIuB9pGZkX+X0UKSoyqi7qUbRTdHF09yzWrORZ+2e9jvGPqYy5O9tqtnJ2Z6xqbFJsY+ybuIC4qriBeIf4RfGXEnQTJAntieTE2MQ9ieNzAudsmjOc5JpUlnRjruXcorkX5unOy553PFk1WZB8OIWYEpeyP+WDIEJQLxhP5aduTR0T8oSbhU9FvqKNolGxt7hKPJLmnVaV9jjdO31D+miGT0Z1xjMJT1IreZEZkrkj801WRNberM/ZcdktOZSclJyjUg1plrQr1zC3KLdPZisrkw3keeZtyhuTh8r35CP5c/PbFWyFTNGjtFKuUA4WTC+oK3hbGFt4uEi9SFrUM99m/ur5IwuCFny9kLBQuLCz2Lh4WfHgIr9FuxYji1MXdy4xXVK6ZHhp8NJ9y2jLspb9UOJYUlXyannc8o5Sg9KlpUMrglc0lamUycturvRauWMVYZVkVe9ql9VbVn8qF5VfrHCsqK74sEa45uJXTl/VfPV5bdra3kq3yu3rSOuk626s91m/r0q9akHV0IbwDa0b8Y3lG19tSt50oXpq9Y7NtM3KzQM1YTXtW8y2rNvyoTaj9nqdf13LVv2tq7e+2Sba1r/dd3vzDoMdFTve75TsvLUreFdrvUV99W7S7oLdjxpiG7q/5n7duEd3T8Wej3ulewf2Re/ranRvbNyvv7+yCW1SNo0eSDpw5ZuAb9qb7Zp3tXBaKg7CQeXBJ9+mfHvjUOihzsPcw83fmX+39QjrSHkr0jq/dawto22gPaG97+iMo50dXh1Hvrf/fu8x42N1xzWPV56gnSg98fnkgpPjp2Snnp1OPz3Umdx590z8mWtdUV29Z0PPnj8XdO5Mt1/3yfPe549d8Lxw9CL3Ytslt0utPa49R35w/eFIr1tv62X3y+1XPK509E3rO9Hv03/6asDVc9f41y5dn3m978bsG7duJt0cuCW69fh29u0XdwruTNxdeo94r/y+2v3qB/oP6n+0/rFlwG3g+GDAYM/DWQ/vDgmHnv6U/9OH4dJHzEfVI0YjjY+dHx8bDRq98mTOk+GnsqcTz8p+Vv9563Or59/94vtLz1j82PAL+YvPv655qfNy76uprzrHI8cfvM55PfGm/K3O233vuO+638e9H5ko/ED+UPPR+mPHp9BP9z7nfP78L/eE8/sl0p8zAAAAIGNIUk0AAHolAACAgwAA+f8AAIDpAAB1MAAA6mAAADqYAAAXb5JfxUYAAAPfSURBVHjazFotjOpAEF5ZCa7niiuuuJ4rjnOH4xyYl8NcwFx65nVdz3EOXHFFXAKuCJI9V1xxlSuRlcjvmYNQSv+AljfJJISw7Pdtv52dnSkBQG7h0+lUMwwDhmGg1WpB07SD67oOwzAwmUxat5oPALlq8GQyabXb7REhhP86UpwTQnij0Zh/fX093wV8v99/zwE4kUi73R5tNptK4eAppd0bgD5LpNfr/S0E/GazqRQEOvIkZrOZejPws9lMLRh0hMTHx8efq8EfyQQlO2+326OLwQ+Hw7c7Ac9MoFSpSJKESqWSi0C/33/PDH69XotFrriu69B1PQ8JHncmRL8oWCqKogAAgiCAaZoQRTETgZ+fHykR/MvLi1mGzn3fx952ux0sy4IkSYljHh8f7VjwR7G88M04Go1wzmzbhqIomeVz+PCbo4QGdLtdUEpv7qvVCknGGIOmaRECtVqNRcAvl0v5eNUFQQg92nuZ53nodDqxq08AkF6v9/d0xf8n45xDluWI9s9GGFEUsdvt/gvgjuOc7gN+AB8X12VZLkTvlFIsFotU0K7rntU9IYR/fn52ABCi63rpRz+lNBa07/t4fn7OFDZJ2g+LcNu2I6C32y263W6m8dVq1QNAiKqqpQIXBAFBEBxAB0GAwWAAQRDy/IcPgJC0k+3W3ul0DicrpTRvohbatKRsycznc4xGo6w5TWKuQ26ZcGWRzJWgD84Yw9XgVVWF67oYj8elXlTW67V4seZFUQxFjZiYXBh4AITknVQQBFBKQycw57z0KyIAQk4Sn9RIsd1uIzHaNM1SwT88PLgACMky8V7XcTYYDC4GclzTPPakuP/09DQGQIjv+0LcJeRU13H2/f0dyl0YYxHnnOdKyIIgiItMfDqdaoesUpZlJ03X97Bzedc+NTiA13X99bg8cU7X97BzwaTZbFoh8J7nhe6vkiTBsqxSALquG5GYbdux6fBisVAid9jX11f9VPtZSaxWq5DmT5sLmqYdbkLXeKPRmMeWPuI2bhqJkk7X0KpHwBuGkVhYjSNRwiHF9+ExsWKmaZqVVr85R6LI9OBULomFVlVV7ayF0z0Jy7IKW/XlcilnBu+6bq5iqyRJKOguzJMab7G1b8uytHvX59P6VImdh/l8rtyrM0Ip7V7dk/J9X/jdA6WQaDab1mlIvLqV2Wq1xkV3A+v1upOnJ5ur7+k4jqwoyvzGJHi9Xncuae1f1AEfj8ctSZLYtR3warXqZdF2Ie8eeJ5XMQyje/Q00sjwWq3GhsPh27k2TangT50xJjHGYJom9m+AGIYBx3HAGIPv+8It5/s3ACa6Av95eMXyAAAAAElFTkSuQmCC" style="width: 20px;"/>');
			case 6:
				return $('<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAC0AAAAtCAYAAAA6GuKaAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAAZdEVYdFNvZnR3YXJlAEFkb2JlIEltYWdlUmVhZHlxyWU8AAADI2lUWHRYTUw6Y29tLmFkb2JlLnhtcAAAAAAAPD94cGFja2V0IGJlZ2luPSLvu78iIGlkPSJXNU0wTXBDZWhpSHpyZVN6TlRjemtjOWQiPz4gPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iQWRvYmUgWE1QIENvcmUgNS41LWMwMTQgNzkuMTUxNDgxLCAyMDEzLzAzLzEzLTEyOjA5OjE1ICAgICAgICAiPiA8cmRmOlJERiB4bWxuczpyZGY9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkvMDIvMjItcmRmLXN5bnRheC1ucyMiPiA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIiB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iIHhtbG5zOnhtcE1NPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvbW0vIiB4bWxuczpzdFJlZj0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL3NUeXBlL1Jlc291cmNlUmVmIyIgeG1wOkNyZWF0b3JUb29sPSJBZG9iZSBQaG90b3Nob3AgQ0MgKE1hY2ludG9zaCkiIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6NDYzOEVCRDk0OEQ4MTFFN0ExNzJEMzUwODYwN0UyOTEiIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6NDYzOEVCREE0OEQ4MTFFN0ExNzJEMzUwODYwN0UyOTEiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDo0NjM4RUJENzQ4RDgxMUU3QTE3MkQzNTA4NjA3RTI5MSIgc3RSZWY6ZG9jdW1lbnRJRD0ieG1wLmRpZDo0NjM4RUJEODQ4RDgxMUU3QTE3MkQzNTA4NjA3RTI5MSIvPiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gPD94cGFja2V0IGVuZD0iciI/PuuBSUoAAAVUSURBVGhDzZk7TCNHGMeHVEhJTpR0h3Q8rKMBIRCichAQp4MC4QpokJCCBAgh0cFBYTpXVBQWLqAEKqh4SIiXlFtAikhBQi6ENwKbN0qOve+/3tnMrmfXuz4b8kk/e3Z2Hv/95rmzOaqqMq92cnLC5ufn2ezsLFtaWiqNxWI/UHQF4SPeEt8Sb4hr4o74RPxG/OLz+RYDgcCvLS0trLq6mqIYg4acnBwt7MqQwQ3Pz8/s6uqK9fT0sAIyyjpM/IEi0gD5hsvKygrm5uak9TkhjQQQycMPDw8sHA5DbCFVFiX+0Sv/WlBOlDxfqCiKqX4npJEi+/v7jDySS4XDs096ZSmh5pbG2/BIDI+OjubKNFiRRnIWFxchuIgKVIQKsokSDAaL0LIyPRxpJEB3IPuJwGCyFp5N4n6/P3B8fCzVBaSRoVCI8rJmwnV3yDBPNLM0Y+DL9CVFYDST1SKjXsBr8UQDtNaqD5hmid3dXcydxZQhZingtYjRFFssCgZGAJ2/vLwcs8RLDTq3KJFIxDSrGIHBwUG6z0aExP8baAYbFgem9nN0dISFA1Mb5kstIeZZj3OtLXblHBwcuK3jkbpJoUk0lmayKSFRxrATdXh4qF5fX0vv2eSZ5N5mCJCX31HkZ0uijCATQC2r3t7eSkU7eP5zb2/vO020PiePCjfdNllakJPUu7s7TfTNzY00jYioJT8/f/TxkXowrT7YE2LraErshFjQ0NCQ6Z4Mnp62tOr9/b0h2q57OPAJWwtG6kslN1NSX1+v7u3tua4Ygmla9Sza2uqdnZ3Qy7rESI5TF5mZmTGa103FEEzNahIN0vA0ukjXNxTAG4dhJFb71zq8xfr7+9nl5SUjL+sxqe309JTl5eXpV/+ZrHw3Rg6ogOiSxGXCZIVVVVWx7e1tNjIywnJzsWi6s7OzM6ngr7QSiMY7XZJxj4+Pj7Pl5WVWUmJ6tpR2fn6uCeblyMzpnsz09G8h+nuErNba2qpVjH+xy7ip6OLiwvAwbzlZC2Kz5sX0Mr6Tim5ra2NjY2Mp+6L1AXC9sLAgzSd7WDcOkNgbiMabickmJiZYTU2N1i24yTxljcN1bW2t1v8zbcIDXkP0TSJstp2dHVZXV8c6Ojq0GcCLVyoqKtjW1pZ+lTDZQ3sxIf8tRP+VCMstGo2yhoYGbUB6scrKSpPHxYfmAtLsHli9WYRAKVKoYCNMU5+6trZmrGhuFpfNzU1tYREXF543Ho9L86QAeuUrohN9fX3a1tLtirixsZH2iig6Dfh8vp8x0l3tPayZi4uL1ampqaSKrek4EM5Fc2+ns4zTG1YpCwaDFPa2yxOhwSqNlwHh3NNuW8nCn3j5ZuQtCpv309lkfX3dU/cAvPWoa4S0QUxNltU3FxkYzGl0j3/D4XDizQU/+hHYpJAg66yurnr19CQcbIjGBb2m4xjXeBvPJHaDc2VlJemeTdrHSCRifhsH09PTdE87zrVmeDHsHo4c+oHrNIkGNJNgs/zRmumV+agoivyECaCbZPMsz86TDsSoBxSJGoHpAvtbnPyTZeXU1Em05N5TKBSSn5paIyAc3z/ItPPpNLxjC8pyWd5Te3t7s1UbRxoJ9K1lgIjrBb0UcVqqf5Rp4kgjOTiJ9/v9ReQdJZMed0ChFTqpD1uRRloZGBjgX7c8zeMeHhTlfrDOEnYkRYhfBkSwUWlqasrKd0RakY2Fww1JEXaiOThLCwQC/Ivt73rlKbF4HfmGqe8WiB+DUtXNkUY6wQvGETEtrayxsfE9CcCLRISErdH/3wSf5/GPa8RHaJfW1d3d/Z42TEnlukdlXwAR0zYFK7vJ6AAAAABJRU5ErkJggg==" style="width: 35px;"/>');
			case 7:
				return $('<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAC0AAAAtCAYAAAA6GuKaAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyNpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNS1jMDE0IDc5LjE1MTQ4MSwgMjAxMy8wMy8xMy0xMjowOToxNSAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIChNYWNpbnRvc2gpIiB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOjgwRDc2NzFBNDhENzExRTdBMTcyRDM1MDg2MDdFMjkxIiB4bXBNTTpEb2N1bWVudElEPSJ4bXAuZGlkOjgwRDc2NzFCNDhENzExRTdBMTcyRDM1MDg2MDdFMjkxIj4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InhtcC5paWQ6ODBENzY3MTg0OEQ3MTFFN0ExNzJEMzUwODYwN0UyOTEiIHN0UmVmOmRvY3VtZW50SUQ9InhtcC5kaWQ6ODBENzY3MTk0OEQ3MTFFN0ExNzJEMzUwODYwN0UyOTEiLz4gPC9yZGY6RGVzY3JpcHRpb24+IDwvcmRmOlJERj4gPC94OnhtcG1ldGE+IDw/eHBhY2tldCBlbmQ9InIiPz6Mh756AAAEoklEQVR42sxZO0zbYBA+og6eKkZYSiTygHYgUosAsUQIRBhALIhkADHwiAQDCwtLSIoEW0bEVOhQJBYkFh5LBAgFhiYLBSrUolYVj4mQ8Ahqi3tn2fTHdYj9x4b+0sV2bJ+///P953uAKIrwkNze3mr+f3Z2BnNzc9DZ2fmyqKhoCADeocRx/wduUyiivKXjOJ232+1DwWDw5cLCAuR77kNi+Ia9vT0IBAJ2BBFB+SKDMyp0XwQnYD8+PuYHnYtRRUg5PsSBD3uP8pMTrFpIz/tQKOS4urrKiYU9pn1dgKempgRUHkbJmgRWLaQ3EovFhILN4/r6mth1osKkRWDVkoxGo05u0GQOXq+3GRWdPxJgRc79fr+PCMvlGDRPHB4e4r3QgXJjNij0Lnquu2lvb+/IBVzTJHA0WAHYoNwg4w1aa+4f0Hihk/GzTy2piYkJ14Og8QLyEgmrweg0EUUSS0tL97yKRD1JMkkOAsIGFUrS2Nho6STxSxph7ftuBw3fweuH0+m0OD09LbpcLquAZ4eHhx33QCP9xPIHXqWZTEaSg4MDsampySrgH5RPvvTj8XjK0Sx+8SokphXgl5eX4szMjKmsyyb7G51EuQQ6HqcADCYKUaqApu3FxYWIcYR4dHQkdnV1mc32JEWXtuXlZQIdABMGMiJtiY3i4mJAO4fFxUWoqKgAk0ZgZWUFSOGrQhlgzYOYJhMhIcZx1YsnJydiX1+fKWz39PQQXhjk8J2GQGezWWm7trYmVlVVFQS6pKRkyIY7b5RXyr5iswfprampgfX1dRgZGeE2PXxrrwm0mz2pgLdqCIIA4+PjsLGxAW63W/d9DC43gS5Tz8ZKtpWH19bWws7OjjQBg+NFkRwvPy8EDNr0vQmr91kStM7v7+9Db28vbG1t6XqcrVDAPENtgpWVlbC5uQnoGfS8recEOg1PPNC7wOjoKOCXVM+EM89we/HYbLPmsr29DfX19UbWUYaY/vYU5pFKpWBwcFACbNBrfSOmP6PUWQVOWWwsqNXVVWhtbeVV+5mY/milGbCAid3+/v68gNXehh34Rfxow5+YVX6Zfe3z8/NQWloKs7OzhryL2mwwDIjZWlpaPuGJ71aBPj09hba2Nuju7s5rtzrI+44Z1i5QBZPiVCuiPErBzI6npZoMJYyYOJZTZmAW6N3dXVPSLiXyVDIXDLjK79KtaDRaUI7Igg6Hw7ylgnwyF4vF/uaIxDZGXFI2zvMgAp1IJMTq6mrLsnG0Zec/JQTZtiM8SsfGxgyxykHMW6rLaFaYmpubhUcs6+ou/4ZCISFnWYwyXYxzXf9TLc/n87nyVk2pp6KnamryItOsmiKBDUSkrqK6XHHSXZ/ONwGOCRLgDiLQUCdAXpi+fJ0AlS81Q9Iej6eFXXiGei4007q6OlN7LlqTY/5Ler1ep5ZJGOojkgJUJMju0KruFplhZGBgQDC1+UndWfzcc/UR1ewyx1IfEc3BoXzt9LQIDXVKqUE5OTkJZWVlSsf2KyezdF8Ew2I7kWFam/mh2Sp9cb/fT4VGqq3d9cZRjpjFey4fx+XzQwj0VTAYhEL6438EGAAFV2yYgEXdLAAAAABJRU5ErkJggg==" style="width: 35px;"/>');
			case 8:
				return $('<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAC0AAAAtCAYAAAA6GuKaAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyNpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNS1jMDE0IDc5LjE1MTQ4MSwgMjAxMy8wMy8xMy0xMjowOToxNSAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIChNYWNpbnRvc2gpIiB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOjQ2MzhFQkRENDhEODExRTdBMTcyRDM1MDg2MDdFMjkxIiB4bXBNTTpEb2N1bWVudElEPSJ4bXAuZGlkOjQ2MzhFQkRFNDhEODExRTdBMTcyRDM1MDg2MDdFMjkxIj4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InhtcC5paWQ6NDYzOEVCREI0OEQ4MTFFN0ExNzJEMzUwODYwN0UyOTEiIHN0UmVmOmRvY3VtZW50SUQ9InhtcC5kaWQ6NDYzOEVCREM0OEQ4MTFFN0ExNzJEMzUwODYwN0UyOTEiLz4gPC9yZGY6RGVzY3JpcHRpb24+IDwvcmRmOlJERj4gPC94OnhtcG1ldGE+IDw/eHBhY2tldCBlbmQ9InIiPz4fAltyAAADtElEQVR42tRZP0waURi/O0i8pI1l1KEWIyqpHTR2YANZoJMuBkaHNnFkdDPSxUlH4+rgERdlcypuToImbVOHKrSJuknVKqYg/X30nuW/9+5O7nzJL1yA977f++773vv+iOVyWdA7stmssLW19TqZTAZ3dnbG8ZUXeCmK4nOs+wLPv4Ar4CfwDdgbHR39FIlEvobDYQHP+gQTaV5sbm66vV5vHNO/0xJagc2wZ5oXd2MsLy9zy+f68+7ursfn861B4J8mRPSA1lkDdw8UoYnD3d2dNtI3NzdyNBpdgIBCNUmDhKtRIM37/X759PTUmKZpV8fHx4NYMGMSuYdAcgbxRu/lc5OGc4VUZyp3ECQvvLKywq9pRVGmMfnWKAmdJkRyp+fn57WTzmQyQZrIBJpou1zEITcI5TWYSgNhOALZcN4Cks1APIagxNaavr6+lnHop21CmIH41JwqNaSXlpYWbEaYIT41NdVIGjvxqOelHUkTLw8zk3vSsVhs3UTvfwys4zb+TzqdTg/gy6JNtcxQAgboupcoaNrY2HiPD4fWIEt3dGZsENcPq6ur/zSNiC3Lu/NSqWQKOOXmXC6XICK2GOnv7/8M2xV4YutisWhYdSTT4XDwznkjJRKJgGDR0JOAYM6E8+Dg4K2eBYxkPAbHuPPs7Gy4U1oyaQw7kee9emKk+0Q1fu1mjqGVTKFQMMURu7q6eKddSIzwU3FE4usk5ow4LaJV2xaax6WETPhKDxk9pYeamFj/xi+lnp6eXKfqJfVJczvS9MZbjByRPrSCdLXGOc3vUELcsWdHTbcZe1IoFEpZ4U30+tuYQLuREmm3vb29OdyMfVyH5cWFKeS7u7lO3B+Au/KKZmZmFq0I7OtLFPT5QKa0WInlifT29vaAmhmYXXQxPXOhKuu9YwQCgXWbp1sK7hQqhgrVVSW7Z+ODVN9rKPVGIpH4I9bnjODj2NhY82LNycmJDEPP2EzLxEdOpVLNSZPq8eOQ3Wp59WXfprcV/hQ0o8xrtGoKBGdnZ7XXp/HnaQuJV+rTVL+j04KrExCLxcJGOgE6HZau2neMMJlsfRvjwcBGUZSO91xadQC4WnI4w2W1b1h4pOPuVu0ryqzyb0of8fz8XJibm/Ng4Zo+okGTqPQRMccTjUYFLe047uanWscWIMBNmgGOdJI+Uue7ET4I9e2JVrE3g6g3QVX74kIymRzZ39+fyOfzrDdOdZRnarJMTvWbUiTWG8ebSrlcri+Tk5MCORtvBZb4/hVgAIOSul6DsD0aAAAAAElFTkSuQmCC" style="width: 35px;"/>');
			case 9:
				return $('<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAC0AAAAtCAYAAAA6GuKaAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAAZdEVYdFNvZnR3YXJlAEFkb2JlIEltYWdlUmVhZHlxyWU8AAADI2lUWHRYTUw6Y29tLmFkb2JlLnhtcAAAAAAAPD94cGFja2V0IGJlZ2luPSLvu78iIGlkPSJXNU0wTXBDZWhpSHpyZVN6TlRjemtjOWQiPz4gPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iQWRvYmUgWE1QIENvcmUgNS41LWMwMTQgNzkuMTUxNDgxLCAyMDEzLzAzLzEzLTEyOjA5OjE1ICAgICAgICAiPiA8cmRmOlJERiB4bWxuczpyZGY9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkvMDIvMjItcmRmLXN5bnRheC1ucyMiPiA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIiB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iIHhtbG5zOnhtcE1NPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvbW0vIiB4bWxuczpzdFJlZj0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL3NUeXBlL1Jlc291cmNlUmVmIyIgeG1wOkNyZWF0b3JUb29sPSJBZG9iZSBQaG90b3Nob3AgQ0MgKE1hY2ludG9zaCkiIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6NDYzOEVCRDk0OEQ4MTFFN0ExNzJEMzUwODYwN0UyOTEiIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6NDYzOEVCREE0OEQ4MTFFN0ExNzJEMzUwODYwN0UyOTEiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDo0NjM4RUJENzQ4RDgxMUU3QTE3MkQzNTA4NjA3RTI5MSIgc3RSZWY6ZG9jdW1lbnRJRD0ieG1wLmRpZDo0NjM4RUJEODQ4RDgxMUU3QTE3MkQzNTA4NjA3RTI5MSIvPiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gPD94cGFja2V0IGVuZD0iciI/PuuBSUoAAAV0SURBVGhDzZk7SCRZFIavRsLuDo34yrZhfDGCDxbEzF5RtjdSA6EjNREEB1RMTETXDbrBwMDQoNFgxMhQQVEE0VZx2mRRwV238dFmvrV1d6bm/Ncqrcfp6mrXmtkDX3fVua+/Tp26VXVLKIryYtbW1kR3d/e70tLS90KIMLFGHBHnhKL+H2VkZMCP8vdNTU3vwuGwiMfjso/Pnz9b+k0F6zSj7/js7EwMDg56ScAw8ScBcQoJk/8OQbthv9/vXVpaMoxlhjsoi8OO0dHRQhpskvhHHfy/gn4mm5ubC3d2dtgxXyw6Go1mUee/EQl1MFvSjDpAv8P9/f1Z3PhmWKeeqampIuowqhvAFXCgRNTn8xUhBTktGqxTg3L3F+rwwjyAy2A8//b2NqsJsE7Q3t7eQo3vdZ2xqBFiy16C2hfGbaG0dJ7TwWCwTm1o7ozlNUXrwPh1BwcHFuGGHTAzM4Mc1uZZR7gkGpzX1NQU393dGTQadtRZ4qOp4bfmYyAQMMwqBtGVlZWY1syN3IykU4YpA6yi6daKG4dlHjYLxv7KyorBpy/j/K9AggJaqKWJ/MEO2Qeng15eXiqrq6tsmYt8oDvys2jaeUvOf02VkgLR19fXCj0wseUu8cnr9b6VAYZoekoLoiCdSN/c3EgikQhbxyVCdIcWAg8qZH/rClIC0VdXV1L07e2tsr6+ztbjqK+vZ/0OidFMInCrLmMKbdHSA4IBnbKkws1nD20pWkpxcbHB7/QsezyeMqRGl97ppLE+PTTRiUTCUcS1s3R8fKz09fWxdVKAFw75RsEVJuXi4kIOrEVbEw02NjbYNhqaaLTFQeNirq6ufip3ELRwJv2UEGlZZmamoM7VPaNVVFSIzc1NdS+1of7y8rIYGxsTJSUlj1OavUm9x4T5aGzRclqfHlq07+/vlYeHB4WEJ22rj7T+TMViMaW1tZVtp+MIkf6eSBo5p2ZuT3cwsbW1pe6lNkQ4Pz9fjI+Pi4WFBVFeXq6WWOwHiH6DLQenJW3DqV9cXLQcEBcgva+2tlbQHVe0tbWpHoO9gejLx23nxh0g5zs/Pxd1dXWWMv0+dwBo19XVJSYmJlSPwa4g+ppraGe4EM2m9aH9Y+CcnBy5bWc4AK0NticnJ0Vubq78T2JSdIyLkp3Z1UcZBGPgdGxvb0+mRUdHh9y3GSMG0XuP269jEJyXl6fupTaaNcTAwIDMf5rjVe+zMVmwl1lQUOD8EtcZl1IQjBnAqc3Pz4vs7GwxMjKiep5NnzIm2xKdnZ2GZw+q/LSdDG6ePj09ZeuaQdv9/X2loaGBLU8FBblMYC2NLKYvSIVZtCbYyQEPDQ09bTupbyLm8/kyZE6R+pBW4KQj/R0tHo+zdV4DRksoGKRHf+RMb28v3lw+mSpIuIPQIn1ycmIpe0H0nCLfXLBELEXLDXoH01WQJBMA0Xi05MpcFD3V09PzeGHiB5CDfRs3A1GHh4dJyzh/uqAfU18JinIRnVmjaESbHnKwUK6v/H/hd3rDetL6tAHC4TBWmFxf1k2TaFVVVRZNq7xoQGlSTBXTWstzEbmWp/9KgMVIg2ANv99vWTX9BshV09nZWYs+iwNgJZ6O0NH6tEtg3BbMyZw+1glwYdLdx0+Nv/aXADzf/6otgXGwTg0kfyAQ+CrfXFSiNIMVpfpMxzrNhEIhzCqYDh193QJpztlIh2ESnIWVf06DHtbJge8fdIG68h2RbhyFSAf9tMZ9a9FgnXbgaqaIaF9s/1IHTxe0GyaxXtyaceHbiTSTgR+nJhuoD+eRSERMT0+Lubm5st3d3Z/J9RNRSvxIfEfgLR8X1Q2BR99dYsvj8SzRBf5HY2MjzhyeMMmdjgnxBXICSJ/VttInAAAAAElFTkSuQmCC" style="width: 35px;"/>');
			case 10:
				return $('<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAF0AAAATCAIAAAB9QZaIAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAFuSURBVFhH7dfPasJAEAbwebSCF70kF8lB8giV6kGQkEQQLKQQSQzRmJqm/x60HzrYJW687c7F3334dgY3O9Lvgw793FXX9Zthh8OBwxRSuVe9c2maxvf9Jys8zyuKQja3g757WDvcheu6bdsK5nbQl85ut+M6i9brtVQut62gT53VasVFFs1mM6lcbluhn8t2u+Uii8IwlMrlthWE23XrdDrh4nGdFYPBYL/fS+Vy2wr66JEkyWg04mrDcLgoimRzO3rnAlVVLRaLF8MQUZYlR55J5aruzQVQ+WoY3iAOU0jlXhH2KC2sg5PJhH9who3HY3xxZXM7CJ86LWuHu3Ac53g8CuZ20LtOmqZcZ1Ecx1K53LZCPxc86Vxk0XQ6lcrlthWEf6638FhykUXL5VIql9tWEG7XLaw6uHhcZwVWiTzPpXK5bQVhWdDabDbD4ZCrDcPhgiCQze3onQvghZ/P58+GISLLMo48k8r9V1V/JQTUcER8rgkAAAAASUVORK5CYII=" style="width:75px;"/>');
			default:
				return null;
		}
	},
	__isNaN : function(num){
		if(num == null || num == undefined)
			return true;
		num = num.toString().replace(/\s+/g, "");
		if(num.length == 0)
			return false;
		return isNaN(num);
	},
	__isPx : function(px){
		px = px.toString();
		var patt1 = /^[1-9][0-9]*px/;
		if(px.match(patt1)){
			return true;
		}
		return false;
	},
	__getLang : function(lang){
		if(lang != "chs" && lang != "en"){
			throw "XMlayout info: param 'lang' is invalid in $.xmlayout function __getLang()!";
		}
		if(lang == "chs"){
			var obj = {
				zi : "放大",
				zo : "缩小",
				fs : "全屏",
				rs : "还原",
				pp : "弹出",
				bt : "嵌入",
				ff : "全屏",
				cp : "换位"
			}
			return obj;
		}
		else if(lang == "en"){
			var obj = {
				zi : "zoom-in",
				zo : "zoom-out",
				fs : "fullpanel",
				rs : "resize",
				pp : "popped",
				bt : "berth",
				ff : "fullscreen",
				cp : "transfer"
			}
			return obj;
		}
		return null;
	},
	div_resize : function(div){
		if(div.prop("drag_resize") != null){
    		div.prop("drag_resize", true);
    		return;
    	}
    	div.prop("drag_resize", true);

        if(div.prop("ismr01") == null){
            div.prop({ismr01 : false});
        }
        div.mousemove(function(e){
        	if(!div.prop("drag_resize"))
        		return;

	        var e = e || window.event;
	        var posx = e.clientX +  $(window).scrollLeft();
	        var posy = e.clientY +  $(window).scrollTop();
	        var l = div.offset().left;
	        var t = div.offset().top;
	        var h = div.height();
	        var w = div.width();
	        var ol = l+w-20;
	        var or = l+w+20;
	        var ot = t+h-20;
	        var ob = t+h+20;
	        if(posx>ol && posx<or && posy >ot && posy<ob && !div.prop("ismr02")){
	            $(this).css("cursor","nw-resize");
	            div.prop({ismr01 : true, r_type : 1});
	        }
	        else if(posx>ol && posx<or && posy>(t + 50) && !div.prop("ismr02")){
	        	$(this).css("cursor","e-resize");
	            div.prop({ismr01 : true, r_type : 2});
	        }
	        else if(posy >ot && posy<ob && !div.prop("ismr02")){
	        	$(this).css("cursor","s-resize");
	            div.prop({ismr01 : true, r_type : 3});
	        }
	        else{
	            $(this).css("cursor","default");
	            div.prop("ismr01", false);
	        }
        });
        div.mousedown(function(e){
	        if(!div.prop("ismr01"))
	            return;

	        div.prop({ismr02 : true});

	        var posx = e.clientX +  $(window).scrollLeft();
	        var posy = e.clientY +  $(window).scrollTop();
	        var h = div.height();
	        var w = div.width();

	        $(document).mousemove(function(e){
                var e = e || window.event;

                var currX = e.clientX + $(window).scrollLeft();
                var currY = e.clientY + $(window).scrollTop();
                if(div.prop("r_type") == 1){
                	var nw = w + (currX - posx);
                	var nh = h + (currY - posy);
                	if(nw < 80 || nh < 80)
                		return;

                	div.width(nw);
                	div.height(nh);
                }
                else if(div.prop("r_type") == 2){
                	var nw = w + (currX - posx);
                	if(nw < 80)
                		return;

                	div.width(nw);
                }
                else if(div.prop("r_type") == 3){
                	var nh = h + (currY - posy);
                	if(nh < 80)
                		return;

                	div.height(nh);
                }
            });

            $(document).mouseup(function(){
            	div.prop({ismr01 : false, ismr02 : false});
                div.trigger("resize");
                $(this).unbind("mousemove");
                $(this).unbind("mouseup");
            });
        });
    },
    div_drag : function(div, type){
    	if(div.prop("drag_event") != null){
    		div.prop("drag_event", true);
    		return;
    	}
    	div.prop("drag_event", true);

        if(div.prop("ismr01") == null){
            div.prop({ismr01 : false});
        }
        div.css({position:"position", MozUserSelect: "none", KhtmlUserSelect: "none", userSelect: "none"});
        div.mousedown(function(e){ //e鼠标事件
        	if(!div.prop("drag_event"))
        		return;

            if(div.prop("ismr01"))
                return;

            var offset = $(this).offset();//DIV在页面的位置
            var x = e.pageX;//获得鼠标指针离DIV元素左边界的距离
            var y = e.pageY;//获得鼠标指针离DIV元素上边界的距离

            div.trigger("dragstart", [e]);

            $(document).mousemove(function(ev){ //绑定鼠标的移动事件，因为光标在DIV元素外面也要有效果，所以要用doucment的事件，而不用DIV元素的事件
                div.stop();//加上这个之后

                var _x = ev.pageX - x + parseInt(div.css("left"));//获得X轴方向移动的值
                var _y = ev.pageY - y + parseInt(div.css("top"));//获得Y轴方向移动的值
                x = ev.pageX;
                y = ev.pageY;

                if(type == "axis-x"){
                    div.animate({left:_x+"px"}, 0);
                }
                else if(type == "axis-y"){
                    div.animate({top:_y+"px"}, 0);
                }
                else{
                    div.animate({left:_x+"px",top:_y+"px"}, 0);
                }

                div.trigger("drag", [ev]);
            });

            $(document).mouseup(function(ev){
                if(div.prop("ismr01"))
                    return;

                 div.trigger("dragstop", [ev]);
                $(this).unbind("mousemove");
                $(this).unbind("mouseup");
            });
        });
    },
    div_undrag : function(div){
        div.prop("drag_event", false);
    },
    div_unresize : function(div){
        div.prop("drag_resize", false);
    },
	init : function(){
		var obj_this = this;
		$(document).unbind("contextmenu");
		$(document).bind("contextmenu",function(e){
			window.event.returnValue = false;
			return false;
		});
		$(".xmlayout-in").css("display", "none");

		if($(".xmlayout_anim").length > 0)
				return;
		var mbody = $(document.body);
		obj_this.__play();
		obj_this.playbottom = obj_this.__bottom();
		obj_this.playheader = obj_this.__header();
		mbody.append(obj_this.playheader);
		mbody.append(obj_this.playbottom);
		mbody.prop("playbottom", obj_this.playbottom);
		mbody.prop("playheader", obj_this.playheader);
		obj_this.playbottom.css("display", "none");
	}
}
$(document).ready(function(){
	$.xmlayout.init();
});
