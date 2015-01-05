var Backbone = require('backbone');
var moment = require('moment');

module.exports = Backbone.Model.extend({
    initialize: function () {
    },
    defaults: {
        drillDown: [],
        startedAfter: null,
        startedBefore: null
    }
});
