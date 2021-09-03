/*!
 * Gitee-Frontend.js v0.17.1
 * (c) 2019 Liu Chao
 * Released under the MIT License.
 */
(function () {
    'use strict';


    var classCallCheck = function (instance, Constructor) {
        if (!(instance instanceof Constructor)) {
            throw new TypeError("Cannot call a class as a function");
        }
    };

    var createClass = function () {
        function defineProperties(target, props) {
            for (var i = 0; i < props.length; i++) {
                var descriptor = props[i];
                descriptor.enumerable = descriptor.enumerable || false;
                descriptor.configurable = true;
                if ("value" in descriptor) descriptor.writable = true;
                Object.defineProperty(target, descriptor.key, descriptor);
            }
        }

        return function (Constructor, protoProps, staticProps) {
            if (protoProps) defineProperties(Constructor.prototype, protoProps);
            if (staticProps) defineProperties(Constructor, staticProps);
            return Constructor;
        };
    }();

    var inherits = function (subClass, superClass) {
        if (typeof superClass !== "function" && superClass !== null) {
            throw new TypeError("Super expression must either be null or a function, not " + typeof superClass);
        }

        subClass.prototype = Object.create(superClass && superClass.prototype, {
            constructor: {
                value: subClass,
                enumerable: false,
                writable: true,
                configurable: true
            }
        });
        if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass;
    };

    var possibleConstructorReturn = function (self, call) {
        if (!self) {
            throw new ReferenceError("this hasn't been initialised - super() hasn't been called");
        }

        return call && (typeof call === "object" || typeof call === "function") ? call : self;
    };

    var defaults$1 = {
        readonly: false,
        key: 'state',
        name: 'board',
        message: {
            loading: 'loading ...',
            stateDisabled: 'Current issue cannot switch to this state',
            allComplete: 'Showing all issues',
            empty: 'There are no issues here',
            btnSetFirst: 'Move it to the first',
            btnSetLast: 'Move it to the last'
        },
        className: {
            iconComment: 'icon comments outline',
            iconAngleLeft: 'icon angle double left',
            iconAngleRight: 'icon angle double right',
            iconIssue: 'note outline',
            card: 'ui link card',
            action: 'ui button',
            actions: 'ui mini basic icon buttons',
            avatar: 'ui avatar image'
        },
        actions: function actions(config) {
            if (!config.plugins.Sortable) {
                return [];
            }
            return [{
                id: 'btn-set-first',
                class: config.className.action,
                icon: config.className.iconAngleLeft,
                title: config.message.btnSetFirst,
                callback: function callback(boards, board) {
                    var state = board.state.toString();
                    var states = boards.sortable.toArray();
                    var i = states.indexOf(state);

                    if (i >= 0) {
                        states.splice(i, 1);
                        states.splice(0, 0, state);
                        boards.sortable.sort(states);
                        config.onSorted(states);
                        boards.load();
                    }
                }
            }, {
                id: 'btn-set-last',
                class: config.className.action,
                icon: config.className.iconAngleRight,
                title: config.message.btnSetLast,
                callback: function callback(boards, board) {
                    var state = board.state.toString();
                    var states = boards.sortable.toArray();
                    var i = states.indexOf(state);

                    if (i >= 0) {
                        states.splice(i, 1);
                        states.push(state);
                        boards.sortable.sort(states);
                        config.onSorted(states);
                        boards.load();
                    }
                }
            }];
        },

        data: [{
            order: 1,
            name: 'Backlog',
            state: 'open',
            color: '#ffa726',
            issuesCount: 0
        }, {
            order: 2,
            name: 'Done',
            state: 'closed',
            color: '#2baf2b',
            issuesCount: 0
        }],
        plugins: {},
        types: [],
        user: {
            id: 0,
            admin: false
        },

        /**
         * 在任务列表加载完后的回调
         * @callback BoardLoadCallback
         * @param {Array} issues 任务列表
         * @param {Number} count 任务总数
         */

        /**
         * 在开始加载任务列表时的回调
         * @param {Object,Board} board 板块对象
         * @param {BoardLoadCallback} callback 用于接收任务列表的回调函数
         */
        onLoad: function onLoad(board, callback) {
            $.ajax({
                url: 'issues',
                data: {
                    state: board.state,
                    page: board.page
                }
            }).done(function () {
                callback([], 0);
            }).fail(function () {
                callback([], 0);
            });
        },
        /**
         * 在更新任务时的回调
         * @param {Object} issue 任务
         * @param {String,Number} oldState 更新前的状态
         */
        onUpdate: function onUpdate(issue, oldState) {
            var _this = this;

            $.ajax({
                type: 'PUT',
                url: issue.url,
                data: {
                    state: issue.state
                }
            }).done(function (res) {
                _this.updateIssue(res);
            }).fail(function () {
                _this.setIssueState(issue.id, oldState);
            });
        },

        /**
         * 在渲染任务卡片时的回调
         * @param {Object} issue 任务
         * @param {JQuery} $el 任务卡片
         */
        onRender: function onRender(issue, $el) {
            $el.addClass('issue-state-' + issue.state);
            return $el;
        },

        /**
         * 在板块被排序后的回调
         * @param {Array} states 状态列表
         */
        onSorted: function onSorted(states) {
            window.console.log(states);
        }
    };

    var Config = function Config(config) {
        classCallCheck(this, Config);

        $.extend(this, defaults$1, config);
        if (config.className) {
            this.className = $.extend({}, defaults$1.className, config.className);
        }
        if (config.message) {
            this.message = $.extend({}, defaults$1.message, config.message);
        }
    };

    var htmlSafe = function () {
        var $el = $('<div/>');

        return function (text) {
            return $el.text(text).html();
        };
    }();

    var Renderer = function () {
        function Renderer(config) {
            classCallCheck(this, Renderer);

            this.config = config;
        }

        createClass(Renderer, [{
            key: 'getAvatarUrl',
            value: function getAvatarUrl(name, avatarUrl) {
                var Avatar = this.config.plugins.LetterAvatar;
                if (!avatarUrl || avatarUrl.indexOf('no_portrait.png') === 0) {
                    if (Avatar) {
                        return Avatar(name);
                    }
                }
                return avatarUrl;
            }
        }, {
            key: 'getCardId',
            value: function getCardId(issue) {
                if (this.config.getIusseId) {
                    return this.config.getIusseId(issue);
                }
                return 'issue-card-' + issue.id;
            }
        }]);
        return Renderer;
    }();

    /* eslint-disable indent */

    function getUserUrl(user) {
        return user.html_url || user.path;
    }

    function renderCardUserLabel(issue) {
        if (!issue.author) {
            return '';
        }
        if (typeof issue.author.is_member !== 'undefined') {
            if (!issue.author.is_member) {
                return '<span class="user-label blue">[访客]</span>';
            }
        }
        if (issue.author.outsourced) {
            return '<span class="user-label red">[外包]</span>';
        }
        return '';
    }

    function renderCardLabels(issue) {
        var html = '';

        if (!issue.labels) {
            return '';
        }

        issue.labels.forEach(function (label) {
            html += ['<span class="label" style="background-color: #', label.color, '; color: #fff">', htmlSafe(label.name), '</span>'].join('');
        });
        return '<div class="labels">' + html + '</div>';
    }

    var CardRenderer = function (_Renderer) {
        inherits(CardRenderer, _Renderer);

        function CardRenderer() {
            classCallCheck(this, CardRenderer);
            return possibleConstructorReturn(this, (CardRenderer.__proto__ || Object.getPrototypeOf(CardRenderer)).apply(this, arguments));
        }

        createClass(CardRenderer, [{
            key: 'render',
            value: function render$$1(issue) {
                var user = this.config.user;
                var readonly = this.config.readonly;

                var draggable = '';
                var cardClass = this.config.className.card;

                if (!readonly && (user.admin || user.id === issue.author.id)) {
                    draggable = 'draggable="true"';
                    cardClass += ' card-draggable';
                }

                var status_color = '#464a4b';
                if(issue.base_status_id > 0){
                    status_color = '#'+this.config.paramConfig.status_config[issue.base_status_id]['color'];
                }

                console.log(this.config.paramConfig.formula_config)
                var progress_param = Strack.calculation_progress_bar(
                    0,
                    Strack.translate_timespinner_val(issue['base_plan_duration']),
                    0
                );

                // 判断截止时间紧急程度
                var time_color = '#e1e1e1';
                if(issue.base_end_time){
                    if(this.config.paramConfig.time_config.current_time < issue.base_end_time ){
                        if(issue.base_end_time < this.config.paramConfig.time_config.urgent_time) {
                            // 即将超时
                            time_color = '#faad14';
                        }else {
                            // 正常时间
                            time_color = '#1890ff';
                        }
                    }else {
                        // 已经超时
                        time_color = '#f5222d';
                    }
                }

                // 生成timelog按钮
                var random_id = 'grid_kanban_tg_'+Math.floor(Math.random() * 10000 + 1);

                var timelog_param = {};
                if(this.config.paramConfig.timelog_config.active_timelog && this.config.paramConfig.timelog_config.active_timelog[issue.id]){
                    timelog_param = {
                        id : this.config.paramConfig.timelog_config.active_timelog[issue.id],
                        color : 'red'
                    };
                }else {
                    timelog_param = {
                        id : 0,
                        color : 'green'
                    };
                }

                var timelog_dom = Strack.generate_kanban_timelog_bnt({
                    user_id: this.config.paramConfig.timelog_config.my_user_id,
                    random_id: random_id,
                    item_id : issue.id,
                    timelog : timelog_param,
                    module_id: this.config.gridParam.grid_param.module_id
                }, issue[this.config.paramConfig.formula_config['assignee_field']['field']]);

                return [
                    '<li class="', cardClass, '" ', draggable, 'id="', this.config.name, '-issue-', issue.id, '" data-id="', issue.id, '">',
                    '<div class="kanban-card-title" style="overflow: hidden">',
                    '<div class="aign-left"><div class="kanban-card-status" style="background-color: '+status_color+'"></div></div>',
                    '<div class="issue-name text-ellipsis aign-left">'+issue.title+'</div>',
                    '<div class="issue-assignee text-ellipsis aign-left">',
                    '<div>分派人：'+Strack.generate_grid_user(issue[this.config.paramConfig.formula_config['reviewed_by']['field']])+'</div>',
                    '<div>执行人：'+Strack.generate_grid_user(issue[this.config.paramConfig.formula_config['assignee_field']['field']])+'</div>',
                    '</div>',
                    '</div>',
                    '<div class="kanban-card-bnt" style="overflow: hidden">',
                    '<div class="aign-left">',
                    timelog_dom,
                    '</div>',
                    '<div class="kanban-card-end aign-right">',
                    '<div class="card-end-time" style="background-color: '+time_color+'">'+issue.base_end_time+'</div>',
                    '</div>',
                    '</div>',
                    // '<div class="kanban-card-time">',
                    // '<div class="" style="overflow: hidden;margin-bottom: 5px;">' ,
                    // '<div class="card-item aign-left">预估工时</div>',
                    // '<div class="card-progress aign-left">' ,
                    // '<div class="card-progress-est color-progress-est" style="width: calc('+progress_param.est_progress.per+' - 22px);" title="预估工时：'+progress_param.est_progress.show_val+'">'+progress_param.est_progress.show_val+'</div>',
                    // '</div>',
                    // '</div>',
                    // '<div class="card-item aign-left">实际/计划工时</div>',
                    // '<div class="card-progress aign-left">' ,
                    // '<div class="card-progress-plan" style="width: calc('+progress_param.actual_plan_progress.per+' - 10px)">' ,
                    // '<div class="progress-item color-progress-plan aign-left" style="width: calc('+progress_param.actual_plan_progress.left.per+');padding-left: '+progress_param.actual_plan_progress.left.padding+'" title="实际工时：'+progress_param.actual_plan_progress.left.show_val+'">'+progress_param.actual_plan_progress.left.show_val+'</div>',
                    // '<div class="progress-pre" style="left: calc('+progress_param.actual_plan_progress.left.per+');">',
                    // progress_param.actual_plan_progress.left.show_per ,
                    // '</div>',
                    // '<div class="progress-item color-progress-'+progress_param.actual_plan_progress.right.css_name+' aign-left" style="width: calc('+progress_param.actual_plan_progress.right.per+');padding-right: '+progress_param.actual_plan_progress.right.padding+'" title="'+progress_param.actual_plan_progress.right.over_name+'：'+progress_param.actual_plan_progress.right.show_val+'">'+progress_param.actual_plan_progress.right.show_val+'</div>',
                    // '</div>',
                    // '</div>',
                    '</div>',
                    '</li>'
                ].join('');

                // return ['<li class="', cardClass, '" ', draggable, 'id="', this.config.name, '-issue-', issue.id, '" data-id="', issue.id, '">',
                //     '<div class="content card-item-click" data-issueid="', issue.id, '">',
                //     '<a href="javascript:;" class="ui small header card-header" title="',
                //     htmlSafe(issue.title), '">', htmlSafe(issue.title),
                //     '</a>',
                //     '</div>',
                //     '<div class="extra content">', this.renderAssignee(issue), this.renderState(issue),
                //     '<span class="card-number">',
                //     issue.created,
                //     '</span>',
                //     '<span class="card-author">by ',
                //     htmlSafe(issue.author.name),
                //     '</span>',
                //     renderCardUserLabel(issue), this.renderComments(issue), renderCardLabels(issue),
                //     '</div>',
                //     '</li>'].join('');

            }
        }, {
            key: 'renderState',
            value: function renderState(issue) {
                var state = issue.state_data;
                if (!state || this.config.key === 'state') {
                    return '';
                }
                return ['<div class="state">', '<i title="', state.name, '" class="', state.icon, '"', 'style="color: ', state.color, '"></i>', '</div>'].join('');
            }
        }, {
            key: 'renderAssignee',
            value: function renderAssignee(issue) {
                var user = issue.assignee;

                if (!user || !user.id || this.config.key !== 'state') {
                    return '';
                }
                return ['<div class="assignee">', '<a target="_blank" href="', getUserUrl(user), '" title="', htmlSafe(user.name), '">', '<img src="', this.getAvatarUrl(user.name, user.avatar_url), '" alt="', htmlSafe(user.name), '" class="', this.config.className.avatar, '">', '</a>', '</div>'].join('');
            }
        }, {
            key: 'renderComments',
            value: function renderComments(issue) {
                var className = this.config.className.iconComment;

                if (!issue.comments) {
                    return '';
                }
                return '<span class="card-comments"><i class="' + className + '"></i>' + (issue.comments + '</span>');
            }
        }]);
        return CardRenderer;
    }(Renderer);

    /* eslint-disable no-plusplus */

    function setColorAlpha(color, alpha) {
        var reg = /^#([\da-fA-F]{2})([\da-fA-F]{2})([\da-fA-F]{2})$/;
        var matches = reg.exec(color);
        var r = parseInt(matches[1], 16);
        var g = parseInt(matches[2], 16);
        var b = parseInt(matches[3], 16);
        return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
    }

    function renderBoardIcon(board) {
        var iconStyle = '';

        if (board.color) {
            iconStyle = 'style="color: ' + board.color + '"';
        }
        if (board.icon) {
            return '<i class="iconfont ' + board.icon + '" ' + iconStyle + '></i>';
        }
        return '';
    }

    var BoardRenderer = function (_Renderer) {
        inherits(BoardRenderer, _Renderer);

        function BoardRenderer() {
            classCallCheck(this, BoardRenderer);
            return possibleConstructorReturn(this, (BoardRenderer.__proto__ || Object.getPrototypeOf(BoardRenderer)).apply(this, arguments));
        }

        createClass(BoardRenderer, [{
            key: 'render',
            value: function render$$1(board) {
                return ['<div class="board" data-state="', board.state, '">', '<div class="board-inner">', this.renderHeader(board), '<div class="board-list-wrapper">', '<ul class="board-list"></ul>', '<div class="ui inverted dimmer">', '<div class="ui loader"></div>', '</div>', '<div class="board-blur-message">', '<p><i class="icon ban"></i></p>', '<p>', this.config.message.stateDisabled, '</p>', '</div>', '</div><a href="javascript:;" class="board-list-bottom" data-state="', board.state, '"><span><i class="icon-uniE672"></i></span><span>添加任务</span></a>', '</div>', '</div>'].join('');
            }
        }, {
            key: 'renderHeader',
            value: function renderHeader(board) {
                var headerStyle = '';
                var headerClass = 'board-header';

                if (board.color) {
                    if (board.colorTarget === 'background') {
                        headerStyle = 'background-color: ' + setColorAlpha(board.color, 0.04);
                    } else {
                        headerClass += ' has-border';
                        headerStyle = 'border-color: ' + board.color;
                    }
                }
                return ['<div class="', headerClass, '" style="', headerStyle, '">', '<h3 class="board-title">', this.renderActions(), '<div class="right floated issues-count-badge">', '<i class="', this.config.className.iconIssue, '" />', '<span class="issues-count">', board.issuesCount, '</span>', '</div>', this.renderAvatar(board), renderBoardIcon(board), htmlSafe(board.name), '</h3>', '</div>'].join('');
            }
        }, {
            key: 'renderActions',
            value: function renderActions() {
                var config = this.config;
                var actions = config.actions;

                if (typeof actions === 'function') {
                    actions = actions(config);
                }
                return ['<div class="right floated board-actions ', config.className.actions, '">', actions.map(function (a) {
                    return ['<button type="button" class="board-action ', a.class, '" title="', a.title, '" data-id="', a.id, '">', '<i class="', a.icon, '" />', '</button>'].join('');
                }).join(''), '</div>'].join('');
            }
        }, {
            key: 'renderAvatar',
            value: function renderAvatar(board) {
                if (!board.avatarUrl) {
                    return '';
                }
                return ['<img class="', this.config.className.avatar, ' board-avatar" alt="', board.state, '" src="', this.getAvatarUrl(board.name, board.avatarUrl), '">'].join('');
            }
        }, {
            key: 'renderTip',
            value: function renderTip(board) {
                var msg = this.config.message;

                if (board.loadable) {
                    return ['<li class="board-tip">', '<span class="ui active mini inline loader" /> ', msg.loading, '</li>'].join('');
                }
                if (board.completed && board.issues.length > 0) {
                    return ['<li class="board-tip">', msg.allComplete, '</li>'].join('');
                }
                return ['<li class="board-tip">', msg.empty, '</li>'].join('');
            }
        }]);
        return BoardRenderer;
    }(Renderer);

    var Board = function () {
        function Board(data, config) {
            var _this2 = this;

            classCallCheck(this, Board);

            this.page = 0;
            this.loadable = true;
            this.loading = false;
            this.completed = false;
            this.issues = [];

            this.name = data.name;
            this.icon = data.icon;
            this.state = data.state;
            this.color = data.color;
            this.colorTarget = data.colorTarget;
            this.issuesCount = data.issuesCount || 0;
            this.avatarUrl = data.avatarUrl;

            this.config = config;

            this.renderer = new BoardRenderer(config);
            this.cardRenderer = new CardRenderer(config);

            this.$tip = null;
            this.$el = $(this.renderer.render(this));
            this.$dimmer = this.$el.find('.ui.dimmer');
            this.$issues = this.$el.find('.board-list');
            this.$issuesCount = this.$el.find('.issues-count');

            var that = this;
            this.$el.find('.board-list-bottom').on('click', function () {
                var state = $(this).data('state');
                that.clickAddIssue(state);
            });

            this.$issues.on('scroll', function () {
                _this2.autoload();
            });
        }

        createClass(Board, [{
            key: 'add',
            value: function add(issue) {
                this.issues.push(issue);
                this.issuesCount += 1;
                this.$issuesCount.text(this.issuesCount);
            }
        }, {
            key: 'find',
            value: function find(id) {
                for (var i = 0; i < this.issues.length; ++i) {
                    var issue = this.issues[i];
                    if (issue.id === id) {
                        return i;
                    }
                }
                return -1;
            }
        }, {
            key: 'get',
            value: function get$$1(id) {
                var i = this.find(id);

                if (i >= 0) {
                    return this.issues[i];
                }
                return null;
            }
        }, {
            key: 'remove',
            value: function remove(id) {
                var i = this.find(id);

                if (i >= 0) {
                    this.issuesCount -= 1;
                    this.$issuesCount.text(this.issuesCount);
                    return this.issues.splice(i, 1);
                }
                return null;
            }
        }, {
            key: 'clear',
            value: function clear() {
                this.page = 0;
                this.issues = [];
                this.issuesCount = 0;
                this.loading = false;
                this.loadable = true;
                this.completed = false;

                this.$issuesCount.text(0);
                this.$issues.empty();
                this.$tip = null;
            }
        }, {
            key: 'autoload',
            value: function autoload() {
                if (this.completed) {
                    return;
                }
                if (!this.$tip || this.$tip.position().top < this.$issues.height()) {
                    this.load();
                }
            }
        }, {
            key: 'load',
            value: function load() {
                if (this.loading) {
                    return false;
                }
                this.page += 1;
                this.loading = true;
                if (this.page === 1) {
                    this.$dimmer.addClass('active');
                }
                this.config.onLoad(this, this.onLoadDone.bind(this));
                return true;
            }
        },{
            key: 'onLoadDone',
            value: function onLoadDone(issues, count) {
                this.issuesCount = count;
                this.$issuesCount.text(this.issuesCount);
                this.appendIssues(issues);
                if (this.issuesCount > 0) {
                    if (issues.length < 1 || this.issuesCount === issues.length) {
                        this.loadable = false;
                        this.completed = true;
                    } else {
                        this.loadable = true;
                        this.completed = false;
                    }
                } else {
                    this.loadable = false;
                    this.completed = true;
                }
                if (this.page === 1) {
                    this.$dimmer.removeClass('active');
                }
                this.loading = false;
                this.updateTip();
                this.autoload();
            }

            /* 进行初次加载 */

        }, {
            key: 'firstload',
            value: function firstload() {
                if (this.page > 0) {
                    return false;
                }
                return this.load();
            }

            /**
             * 更新提示
             */

        }, {
            key: 'updateTip',
            value: function updateTip() {
                if (this.$tip) {
                    this.$tip.remove();
                }
                this.$tip = $(this.renderer.renderTip(this));
                this.$issues.append(this.$tip);
            }
        }, {
            key: 'createCard',
            value: function createCard(issue) {
                var $card = $(this.cardRenderer.render(issue));
                var onSelect = this.config.onSelect;

                if (onSelect) {
                    $card.on('click', function (e) {
                        var $target = $(e.target);

                        if (!$target.is('a')) {
                            $target = $target.parent();
                        }
                        if (!$target.parent().hasClass('card-header') && $target.is('a') && $target.attr('href')) {
                            return;
                        }
                        onSelect(issue, e);
                    });
                }
                return this.config.onRender(issue, $card, this.config);
            }
        }, {
            key: 'appendIssue',
            value: function appendIssue(issue) {
                this.issues.push(issue);
                this.$issues.append(this.createCard(issue));
            }
        }, {
            key: 'prependIssue',
            value: function prependIssue(issue) {
                this.issuesCount += 1;
                this.issues.splice(0, 0, issue);
                this.$issues.prepend(this.createCard(issue));
                this.$issuesCount.text(this.issuesCount);
            }
        }, {
            key: 'updateIssue',
            value: function updateIssue(issue) {
                var $issue = $('#' + this.config.name + '-issue-' + issue.id);

                if ($issue.length < 1) {
                    this.prependIssue(issue);
                    return;
                }
                $issue.before(this.createCard(issue)).remove();
            }

            /**
             * 直接追加多个任务，不更新任务总数
             * @param {Array} issues 任务列表
             */

        }, {
            key: 'appendIssues',
            value: function appendIssues(issues) {
                var _this3 = this;

                issues.filter(function (issue) {
                    return _this3.issues.every(function (boardIssue) {
                        return boardIssue.id !== issue.id;
                    });
                }).forEach(function (issue) {
                    issue[_this3.config.Key] = _this3.state;
                    _this3.appendIssue(issue);
                });
            }
        }, {
            key: 'clickAddIssue',
            value: function clickAddIssue(state) {
                // 弹出添加item弹框
                var param = this.config.gridParam.grid_param;


                var select_field = {};
                switch (this.config.gridParam.group_raw_param.field_type) {
                    case 'custom':
                        select_field['field'] = this.config.gridParam.group_raw_param.module_code + '-' + this.config.gridParam.group_raw_param.field_value_map;
                        break;
                    case 'built_in':
                        select_field['field'] = this.config.gridParam.group_raw_param.module + '-' + this.config.gridParam.group_raw_param.field_value_map;

                        break;
                }
                select_field['value'] = state;
                var select_amp = {};
                select_amp[select_field['field']] = state;


                Strack.item_operate_dialog(this.config.gridParam.grid_param['module_name'],
                    {
                        mode: "create",
                        field_list_type: ['edit'],
                        module_id: param["module_id"],
                        project_id: param["project_id"],
                        page: param["page"],
                        schema_page: param["page"],
                        type: "add_panel",
                        select_fields: {
                            config: [select_field],
                            map: select_amp
                        }
                    },
                    function () {
                        var kanban_that = $(".datagrid-view-kanban").boards().data('boards');
                        kanban_that.reload(state);
                    }
                );
            }
        }]);
        return Board;
    }();

    var Boards = function () {
        /**
         * 创建一个看板
         * @param {JQuery} $el 看板容器元素
         * @param {BoardsSettings} options 配置
         */
        function Boards($el, options) {
            classCallCheck(this, Boards);

            var config = new Config(options);

            this.$el = $el;
            this.config = config;

            this.boards = {};
            this.gridParam = {};
            this.paramConfig = {};
            this.$hoverIssue = null;
            this.$selectedIssue = null;
            this.timerForScrollLoad = null;

            if(options.drag_card){
                this.bindDrag();
            }
            this.bindScroll();
            this.initPlugins();
            this.clickCardShowSidebar();
            this.setData(config.data);
        }

        createClass(Boards, [{
            key: 'initPlugins',
            value: function initPlugins() {
                this.initSortable();
            }

            // 初始化拖拽排序功能

        }, {
            key: 'initSortable',
            value: function initSortable() {
                var that = this;
                var Sortable = that.config.plugins.Sortable;

                // 如果没有注入 SortableJS 依赖，则不启用这个功能
                if (!Sortable) {
                    return false;
                }
                that.$el.addClass('boards-sortable');
                that.sortable = Sortable.create(that.$el[0], {
                    handle: '.board-title',
                    dataIdAttr: 'data-state',
                    onUpdate: function onUpdate() {
                        that.config.onSorted(that.sortable.toArray());
                    }
                });
                that.$el.on('click', '.board-action', function (e) {
                    var $target = $(this);
                    var id = $target.data('id');
                    var state = $target.parents('.board').data('state');
                    var actions = that.config.actions;

                    if (typeof actions === 'function') {
                        actions = actions(that.config);
                    }
                    actions.some(function (action) {
                        if (action.id === id && action.callback) {
                            action.callback(that, that.boards[state], e);
                            return true;
                        }
                        return false;
                    });
                });
                return true;
            }
        }, {
            key: 'bindScroll',
            value: function bindScroll() {
                var that = this;
                var timerForScrollLoad = null;

                function onScrollLoad() {
                    if (timerForScrollLoad) {
                        clearTimeout(timerForScrollLoad);
                    }
                    timerForScrollLoad = setTimeout(function () {
                        timerForScrollLoad = null;
                        that.load();
                    }, 200);
                }

                $(window).on('resize', onScrollLoad);
                this.$el.on('scroll', onScrollLoad);
            }
        }, {
            key: 'bindDrag',
            value: function bindDrag() {
                var that = this;

                if (that.config.readonly) {
                    return;
                }
                that.$el.on('dragstart', '.card', function (e) {
                    var issueId = $(this).data('id');
                    var issue = that.getIssue(issueId);

                    if (issue) {
                        that.setFocus(issue.type, issue.state);
                    }
                    that.$selectedIssue = $(this);
                    e.originalEvent.dataTransfer.setData('text/plain', issueId);
                    e.stopPropagation();
                });
                that.$el.on('dragend', '.card', function () {
                    that.clearFocus();
                });
                that.$el.on('drop', '.board-list', function (e) {
                    e.preventDefault();
                    e.stopPropagation();

                    if (that.$hoverIssue) {
                        that.$hoverIssue.removeClass('card-dragover');
                    }
                    if (!that.$selectedIssue) {
                        return false;
                    }
                    if (that.config.readonly) {
                        return false;
                    }

                    var $issue = that.$selectedIssue;
                    var issueId = $issue.data('id');
                    var $board = $(this).parents('.board');
                    var state = $board.data('state');
                    var oldState = $issue.parents('.board').data('state');
                    var nextIssueId = that.$hoverIssue ? that.$hoverIssue.data('id') : null;

                    that.setIssueState(issueId, state, nextIssueId, function (issue) {
                        that.config.onUpdate(issue, oldState, nextIssueId);
                    });
                    return true;
                });
                that.$el.on('dragover', '.board-list', function (e) {
                    var key = that.config.key;
                    var $target = $(e.target);

                    if (!that.$selectedIssue) {
                        return;
                    }

                    e.preventDefault();
                    while ($target.length > 0 && !$target.hasClass('card')) {
                        $target = $target.parent();
                    }
                    if ($target.length < 1) {
                        if (that.$hoverIssue) {
                            that.$hoverIssue.removeClass('card-dragover');
                        }
                        that.$hoverIssue = null;
                        return;
                    }
                    if (that.$hoverIssue) {
                        that.$hoverIssue.removeClass('card-dragover');
                    }
                    that.$hoverIssue = $target;
                    var hoverIssue = that.getIssue($target.data('id'));
                    var selectedIssue = that.getIssue(that.$selectedIssue.data('id'));
                    if (hoverIssue && selectedIssue && hoverIssue[key] !== selectedIssue[key]) {
                        that.$hoverIssue.addClass('card-dragover');
                    }
                });
            }

        }, {
            key: 'clickCardShowSidebar',
            value: function clickCardShowSidebar() {
                var that = this;
                var issue_id = 0;

                if (that.config.readonly) {
                    return;
                }

                that.$el.on('click', '.card', function (e) {
                    that.config.gridParam.grid_param["item_id"] = $(e.currentTarget).data('id');
                    Strack.open_datagrid_slider(that.config.gridParam.grid_param);
                });
            }
        },
            /**
             * 设置焦点板块
             * 根据指定的类型和状态，排除无关状态的板块
             * @param {String, Number} typeId 任务类型 id
             * @param {String, Number} stateId 任务状态 id
             */
            {
                key: 'setFocus',
                value: function setFocus(typeId, stateId) {
                    var _this = this;

                    var stateIds = [];
                    var issueType = null;

                    if (this.config.key !== 'state') {
                        return;
                    }
                    this.config.types.some(function (t) {
                        if (t.id === typeId) {
                            issueType = t;
                            return true;
                        }
                        return false;
                    });
                    if (issueType) {
                        stateIds = issueType.states.map(function (state) {
                            return state.id.toString();
                        });
                    }
                    Object.keys(this.boards).forEach(function (id) {
                        if (id !== stateId.toString() && stateIds.indexOf(id) === -1) {
                            _this.boards[id].$el.addClass('board-blur');
                        }
                    });
                }

                /* 清除板块的焦点效果 */

            }, {
                key: 'clearFocus',
                value: function clearFocus() {
                    var _this2 = this;

                    Object.keys(this.boards).forEach(function (key) {
                        _this2.boards[key].$el.removeClass('board-blur');
                    });
                }
            }, {
                key: 'getIssueCard',
                value: function getIssueCard(id) {
                    return $('#' + this.config.name + '-issue-' + id);
                }
            }, {
                key: 'getIssue',
                value: function getIssue(id) {
                    var $issue = this.getIssueCard(id);
                    var $board = $issue.parents('.board');
                    var state = $board.data('state');
                    var board$$1 = this.boards[state];
                    if (board$$1) {
                        return board$$1.get(id);
                    }
                    return null;
                }
            }, {
                key: 'removeIssue',
                value: function removeIssue(id) {
                    var issue = null;
                    var $issue = this.getIssueCard(id);

                    if ($issue.length < 1) {
                        return issue;
                    }

                    var state = $issue.parents('.board').data('state');
                    if (state) {
                        var board$$1 = this.boards[state];
                        if (board$$1) {
                            issue = board$$1.remove(id);
                        }
                    }

                    $issue.remove();
                    return issue;
                }
            }, {
                key: 'updateIssue',
                value: function updateIssue(issue) {
                    var board$$1 = this.boards[issue[this.config.key]];

                    if (board$$1) {
                        board$$1.updateIssue(issue);
                        return true;
                    }
                    return false;
                }
            }, {
                key: 'prependIssue',
                value: function prependIssue(issue) {
                    var board$$1 = this.boards[issue[this.config.key]];

                    if (board$$1) {
                        board$$1.prependIssue(issue);
                        return true;
                    }
                    return false;
                }
            }, {
                key: 'setIssueState',
                value: function setIssueState(issueId, state, nextIssueId, callback) {
                    var $issue = this.getIssueCard(issueId);
                    var $nextIssue = nextIssueId ? this.getIssueCard(nextIssueId) : null;

                    if ($issue.length < 1) {
                        return null;
                    }

                    var user = this.config.user;
                    var $oldBoard = $issue.parents('.board');
                    var oldState = $oldBoard.data('state');
                    var oldBoard = this.boards[oldState];
                    var newBoard = this.boards[state];

                    if (oldBoard.state === state) {
                        return null;
                    }

                    // 如果新的板块不接受该状态的 issue
                    if (newBoard.exclude && newBoard.exclude.indexOf(oldState) >= 0) {
                        return null;
                    }

                    var issue = oldBoard.get(issueId);
                    // 如果当前用户既不具备管理权限，也不是 issue 作者，则禁止操作
                    if (!user.admin && issue.author.id !== user.id) {
                        return null;
                    }

                    issue[this.config.key] = state;
                    newBoard.add(issue);
                    oldBoard.remove(issue.id);
                    $issue.hide(256, function () {
                        // 如果有指定下一个 issue，则将当前 issue 插入到它前面
                        if ($nextIssue) {
                            $nextIssue.before($issue);
                        } else {
                            newBoard.$issues.prepend($issue);
                        }
                        $issue.show(256, function () {
                            if (callback) {
                                callback(issue);
                            }
                        });
                    });
                    newBoard.updateTip();
                    oldBoard.updateTip();
                    oldBoard.autoload();

                    return issue;
                }
            }, {
                key: 'load',
                value: function load() {
                    var _this3 = this;
                    var count = 0;
                    var bound = this.$el.offset();

                    // 设置边界框（可见区域）的尺寸
                    bound.width = this.$el.width();
                    bound.height = this.$el.height();

                    Object.keys(this.boards).forEach(function (state) {
                        var board$$1 = _this3.boards[state];
                        var offset = board$$1.$el.offset();
                        // 如果当前板块在可见区域内
                        if (offset.top + board$$1.$el.height() > bound.top && offset.left + board$$1.$el.width() > bound.left && offset.top < bound.top + bound.height && offset.left < bound.left + bound.width) {
                            if (board$$1.firstload()) {
                                count += 1;
                            }
                        }
                    });
                    return count;
                }
            },
            {
                key: 'resetQueryConfig',
                value: function resetQueryConfig(filter) {
                    var filter_param = JSON.parse(filter['filter_data']);
                    this.config.filter_param.filter = filter_param['filter'];
                    return this;
                }
            },{
                key: 'reload',
                value: function reload(state_id) {
                    if(state_id){
                        // 重载指定看板
                        this.boards[state_id].clear();
                        this.boards[state_id].load();
                    }else {
                        // 清除所有data
                        this.clearData();

                        // 重载所有看板
                        for(var $obj in this.boards){
                            this.boards[$obj].load();
                        }
                    }
                }
            }, {
                key: 'setData',
                value: function setData(data) {
                    var _this4 = this;

                    this.boards = {};
                    this.$el.addClass('boards-list').empty();

                    data.forEach(function (boardData) {
                        var board$$1 = new Board(boardData, _this4.config);
                        _this4.boards[boardData.state] = board$$1;
                        _this4.$el.append(board$$1.$el);
                    });
                }
            }, {
                key: 'clearData',
                value: function clearData() {
                    var _this5 = this;

                    Object.keys(this.boards).forEach(function (state) {
                        _this5.boards[state].clear();
                    });
                }
            }]);
        return Boards;
    }();

    $.fn.boards = function (options) {
        var that = this.data('boards');
        var settings = $.extend({}, $.fn.boards.settings, options);

        if (!that) {
            that = new Boards(this, settings);
            this.data('boards', that);
            that.load();
        }
        return this;
    };

    $.fn.boards.settings = defaults$1;

}());
