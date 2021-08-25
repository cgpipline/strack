var kanbanview = $.extend({}, $.fn.datagrid.defaults.view, {
    render: function (target, container, frozen) {

    },
    bindEvents: function (target) {

    },
    onBeforeRender: function (target, rows) {
        var state = $.data(target, 'datagrid');
        var opts = state.options;

        //console.log(state);
    }
});

$.extend(kanbanview, {
    refreshGroupTitle: function (target, groupIndex) {

    },
    insertRow: function (target, index, row) {

    },
    updateRow: function (target, index, row) {

    },
    deleteRow: function (target, index) {

    }
});
