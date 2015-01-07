var View = require('../view');
var _ = require('underscore');

var socket = require('../io-client').getSocket();


module.exports = View.extend({
    template: require('./templates/live'),
    initialize: function () {
        var that = this;
        socket.on('new live screenshot', function (data) {
            that.handleScreenshot(data);
        });

        this.groups = {};
    },
    handleScreenshot: function (data) {
        if (!this.$el.is(':visible')) {
            return;
        }

        data.time = Date.now();
        this.groups[data.group] = data;

        this.updateGroup(data.group);
    },
    updateGroup: function (groupName) {
        var group = this.groups[groupName];

        var ui = this.renderTemplate(group, require('./templates/live_group'));

        var anchor = this.$('.live-group[data-name="' + groupName + '"]');
        if (anchor.length === 0) {
            this.$('#live-groups').append(ui);
        } else {
            this.$(anchor.get(0)).replaceWith(ui);
        }
    }
});
