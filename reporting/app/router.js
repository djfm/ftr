var $           = require('jquery');
var Backbone    = require('backbone');

var application = require('./application');

Backbone.$ = $;

module.exports = Backbone.Router.extend({

    routes: {
        '': 'home',
        'results/:historyId': 'result',
        'live': 'live'
    },

    home: function () {
        $('body').html(application.homeView.render().el);
    },

    result: function (historyId) {
        var ResultView = require('./views/result');
        var resultView = new ResultView();
        var dataProvider = require('../data-provider');

        var result = dataProvider.getResult(historyId);

        function render () {
            resultView.setResult(result);
            $('body').html(resultView.render().el);
        }

        if (result) {
            render();
        } else {
            dataProvider.once('change', function () {
                result = dataProvider.getResult(historyId);
                render();
            });
        }

    },

    live: function () {
        var LiveView = require('./views/live');

        if (!this.liveView) {
            this.liveView = new LiveView();
        }

        $('body').html(this.liveView.render().el);
    }
});
