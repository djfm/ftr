var $ = require('jquery');
var Backbone = require('backbone');

var application = require('./application');


$(function initializeApplication () {
    application.initialize();
    Backbone.history.start();
});
