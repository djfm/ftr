var View = require('../view');

var dataProvider = require('../data-provider');

module.exports = View.extend({
    template: require('./templates/result'),
    initialize: function initializeResultView () {
        this.model = {};
    },
    setResult: function setResult (result) {
        this.model = result;
    }
});
