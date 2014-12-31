var $           = require('jquery');
var Backbone    = require('backbone');

var application = require('./application');

Backbone.$ = $;

module.exports = Backbone.Router.extend({

    routes: {
        '': 'home'
    },

    home: function () {
        $('body').html(application.homeView.render().el);
    }
});
