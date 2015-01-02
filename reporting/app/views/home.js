var View = require('../view');

var FilterView = require('./filter');
var ResultsView = require('./results');

module.exports = View.extend({
    template: require('./templates/home'),
    afterRender: function () {
        new FilterView({el: this.$('#filter-view')}).render();
        new ResultsView({el: this.$('#results-view')}).render();
    }
});
