var _ = require('underscore');

function parse () {
    var params = {};

    if ('undefined' === typeof(window)) {
        return {};
    }

    _.each(window.location.search.substring(1).split('&'), function (pair) {
        var kv = pair.split('=');
        if (kv.length === 2) {
            params[kv[0]] = kv[1];
        }
    });

    return params;
}

function get (key, defaultValue) {
    var params = parse();
    if (_.has(params, key)) {
        return params[key];
    } else {
        return defaultValue;
    }
}

exports.get = get;
exports.parse = parse;
