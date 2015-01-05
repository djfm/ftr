var _ = require('underscore');

var FilterModel = require('../models/filter_model');
var queryString = require('../lib/query-string');
var stickitHelpers = require('../lib/stickit.helpers');
var View = require('../view');

var dataProvider = require('../data-provider');

module.exports = View.extend({
    template: require('./templates/filter'),
    initialize: function initializeFilter () {
        this.model = this.filter = new FilterModel();

        var startedBefore = queryString.get('startedBefore');
        var startedAfter = queryString.get('startedAfter');

        if (startedBefore) {
            this.filter.set('startedBefore', startedBefore);
        }

        if (startedAfter) {
            this.filter.set('startedAfter', startedAfter);
        }

        this.autoupdate = true;

        var that = this;
        dataProvider.on('change', function onDataProviderChange () {
            if (dataProvider.autoupdate()) {
                that.filter.set('startedAfter', dataProvider.getFirstDate());
                that.filter.set('startedBefore', dataProvider.getLastDate());
            }
            that.filter.set('drillDown', dataProvider.getFilter().drillDown);
            that.filter.set('groupBy', dataProvider.getGroupBy());
        });

    },
    bindings: {
        '#started-after': stickitHelpers.dateAsTimestamp('startedAfter'),
        '#started-before': stickitHelpers.dateAsTimestamp('startedBefore'),
        '#drill-down': {
            observe: 'drillDown',
            updateModel: false,
            update: function ($el, drillDown) {
                _.each(drillDown, function (filter) {
                    if (filter.type === 'name') {
                        var parts = [];
                        for (var i = 0; i < filter.level; ++i) {
                            parts.push('*');
                        }
                        parts.push(filter.value);
                        filter.displayName = parts.join(' :: ');
                    } else if (filter.type === 'tag') {
                        filter.displayName = filter.pool + ' [' + filter.tag + ' = ' + filter.value + ']';
                    }
                });
                $el.html(this.renderTemplate({drillDown: drillDown}, require('./templates/filter_drillDown')));
            }
        },
        '#group-by': {
            observe: 'groupBy',
            updateModel: false,
            update: function ($el, groupBy) {
                $el.html(this.renderTemplate({groupBy: groupBy}, require('./templates/filter_groupBy')));
            }
        }
    },
    events: {
        'click #filter-button': function filterResults () {
            dataProvider.autoupdate(false);
            dataProvider.updateFilter(this.filter.toJSON());
        },
        'click .remove-filter': function removeFilter (event) {
            var filter = this.$(event.target).data('filter');
            dataProvider.removeDrillDownFilter(filter);
        },
        'click .remove-group-by': function removeGroupBy (event) {
            var by = this.$(event.target).data('by');
            dataProvider.removeGroupBy(by);
        }
    }
});
