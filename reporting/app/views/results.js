var ResultsModel = require('../models/results_model');
var View = require('../view');

var dataProvider = require('../data-provider');

module.exports = View.extend({
    template: require('./templates/results'),
    initialize: function initializeResultsView () {
        this.model = this.results = new ResultsModel();
        dataProvider.on('change', this.change.bind(this));
    },
    change: function () {

        this.results.set('count', dataProvider.getCount());
        this.results.set('pools', dataProvider.getPools());

        this.render();
    },
    events: {
        'click .add-filter': 'addFilter'
    },
    addFilter: function (event) {
        var filter = this.$(event.target).data('filter');
        dataProvider.addDrillDownFilter(filter);
    }
});
