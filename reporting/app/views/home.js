var View = require('../view');

var FilterView = require('./filter');
var ResultsView = require('./results');

module.exports = View.extend({
    template: require('./templates/home'),
    afterRender: function () {
        this.filterView = this.filterView || new FilterView();
        this.resultsView = this.resultsView || new ResultsView();

        this.filterView.setElement(this.$('#filter-view'));
        this.resultsView.setElement(this.$('#results-view'));

        this.filterView.render();
        this.resultsView.render();
    }
});
