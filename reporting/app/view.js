var Backbone = require('backbone');

module.exports = Backbone.View.extend({
    initialize: function initialize () {

    },
    render: function render () {

        this.$el.html(this.renderTemplate(this.getRenderData()));

        return this;
    },
    getRenderData: function getRenderData () {
        return this.model;
    },
    renderTemplate: function renderTemplate (data, template) {
        template = template || this.template;
        return template(data);
    }
});
