var View = require('../view');

var $ = require('jquery');

module.exports = View.extend({
    template: require('./templates/result'),
    initialize: function initializeResultView () {
        this.model = {};
    },
    setResult: function setResult (result) {
        this.model = result;
    },
    afterRender: function afterRenderResult () {
        var that = this;

        $.get('/screenshots', {
            root: this.model.artefacts
        }).then(function (data) {
            if (data.length > 0) {
                that.$('#screenshots').html(that.renderTemplate({
                    screenshots: data,
                    lastScreenshot: data[data.length - 1]
                }, require('./templates/screenshots')));
                $('div.fullsize').zoom();
            } else {
                that.$('#screenshots').html('No screenshots, sorry!');
            }
        });

        $.get('/metadata', {
            root: this.model.artefacts
        }).then(function (metadata) {
            that.$('#metadata').html(that.renderTemplate(metadata, require('./templates/metadata')));
        });
    },
    events: {
        'click .thumbnail': function (event) {
            var target = $(event.target), src = target.attr('data-src');
            $('div.thumbnail.selected').removeClass('selected');
            target.closest('div.thumbnail').addClass('selected');
            $('img.fullsize').attr('src', src);
            $('div.fullsize').zoom();
        }
    }
});
