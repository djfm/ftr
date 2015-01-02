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
        dataProvider.on('database updated', function () {
            if (that.autoupdate) {
                that.filter.set('startedAfter', dataProvider.getFirstDate());
                that.filter.set('startedBefore', dataProvider.getLastDate());
                dataProvider.emit('change');
            }
        });

    },
    bindings: {
        '#started-after': stickitHelpers.dateAsTimestamp('startedAfter'),
        '#started-before': stickitHelpers.dateAsTimestamp('startedBefore')
    },
    events: {
        'click #filter-button': function filterResults () {
            this.autoupdate = false;
            dataProvider.setFilter(this.filter.toJSON());
            dataProvider.emit('change');
        }
    }
});
