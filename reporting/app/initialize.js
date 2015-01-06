var $ = require('jquery');

window.jQuery = $;
require('zoom/jquery.zoom');
delete window.jQuery;

var Backbone = require('backbone');
require('backbone.stickit');

var application = require('./application');

require('./io-client'); delete window.io;
var dataProvider = require('./data-provider');

$(function initializeApplication () {
    application.initialize();
    dataProvider.connect();
    Backbone.history.start();
});
