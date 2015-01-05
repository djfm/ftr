var Backbone = require('backbone');
var _ = require('underscore');

var viewHelpers = require('./lib/view.helpers');

module.exports = Backbone.View.extend({
    initialize: function initialize () {

    },
    render: function render () {
        this.$el.html(this.renderTemplate(this.getRenderData()));
        if (this.model) {
            this.stickit();
        }
        _.defer(this.afterRender.bind(this));
        return this;
    },
    afterRender: function () {

    },
    getRenderData: function getRenderData () {
        return this.model && this.model.toJSON ? this.model.toJSON() : this.model;
    },
    renderTemplate: function renderTemplate (data, template) {
        template = template || this.template;
        return template(_.defaults(data, viewHelpers));
    },
    events: {
        'click .click-to-expand': function expandOrMinizize (event) {
            this.$(event.target).toggleClass('expanded');
        }
    }
});
