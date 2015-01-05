var Backbone = require('backbone');

module.exports = Backbone.Model.extend({
    initialize: function () {
    },
    defaults: {
        drillDown: [],
        groupBy: [],
        startedAfter: null,
        startedBefore: null
    }
});
