var io = require('./io-client');
var _ = require('underscore');

var socket;
var database = [];

var filter = {};
var firstDate, lastDate, count, pools;

function setFilter (f) {
    filter = f;
    applyFilter();
}

function addToPool (result, groupBy) {
    var id = groupBy(result);
    var name = id;

    if (!pools[id]) {
        pools[id] = {
            status: {
                ok: 0,
                ko: 0,
                skipped: 0,
                unknown: 0
            },
            _results: [],
            name: name
        };
    }

    var pool = pools[id];

    pool._results.push(result);
    if (_.has(pool.status, result.status)) {
        ++pool.status[result.status];
    }
}

function percentize (object) {
    var sum = 0;
    var keys = [];

    _.each(object, function (value, key) {
        if (!/_percent$/.exec(key)) {
            sum += value;
            keys.push(key);
        }
    });

    _.each(keys, function (key) {
        object[key + '_percent'] = (100 * object[key] / sum).toFixed(2);
    });

}

function applyFilter () {
    count = 0;
    firstDate = lastDate = undefined;
    pools = {};

    function groupBy (result) {
        return result.identifierHierarchy.join(' :: ');
    }

    _.each(database, function (result) {

        if (filter.startedAfter && result.startedAt < filter.startedAfter) {
            return;
        }

        if (filter.startedBefore && result.startedAt > filter.startedBefore) {
            return;
        }

        if (!result.identifierHierarchy) {
            return;
        }

        firstDate = firstDate ? Math.min(firstDate, result.startedAt) : result.startedAt;
        lastDate = lastDate ? Math.max(lastDate, result.startedAt) : result.startedAt;

        addToPool(result, groupBy);

        ++count;
    });

    _.each(pools, function (pool) {
        percentize(pool.status);
    });
}

function connect () {
    socket = io.connect();

    socket.on('database updated', function (db) {
        database = db;
        applyFilter();
        emit('database updated');
    });

    socket.on('database fragment', function (result) {
        database.push(result);
        applyFilter();
        emit('database updated');
    });
}

var callbacks = {};

function on (eventName, callback) {
    (callbacks[eventName] = callbacks[eventName] || []).push(callback);
}

function emit (eventName, data) {
    if (callbacks[eventName]) {
        for (var i = 0, len = callbacks[eventName].length; i < len; ++i) {
            callbacks[eventName][i](data);
        }
    }
}

exports.connect = connect;
exports.on = on;
exports.emit = emit;
exports.setFilter = setFilter;

exports.getCount = function () {
    return count;
};

exports.getFirstDate = function () {
    return firstDate;
};

exports.getLastDate = function () {
    return lastDate;
};

exports.getPools = function () {
    return pools;
};
