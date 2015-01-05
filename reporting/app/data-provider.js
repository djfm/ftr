var io = require('./io-client');
var _ = require('underscore');

var socket;
var database = [];

var filter = {
    drillDown: []
};

var groupBy = {

};

var firstDate, lastDate, count, pools;

function updateFilter (f) {
    filter = _.extend(filter, f);
    applyFilter();
}

function addDrillDownFilter (f) {
    filter.drillDown.push(f);
    applyFilter();
}

function addGroupBy (by) {
    if (!_.has(groupBy, by.pool)) {
        groupBy[by.pool] = [];
    }
    if (_.indexOf(groupBy[by.pool], by.tag) === -1) {
        groupBy[by.pool].push(by.tag);
        applyFilter();
    }
}

function removeGroupBy (by) {
    if (!_.has(groupBy, by.pool)) {
        return;
    }
    var pos = _.indexOf(groupBy[by.pool], by.tag);

    if (pos === -1) {
        return;
    }

    groupBy[by.pool].splice(pos, 1);

    if (groupBy[by.pool].length === 0) {
        delete groupBy[by.pool];
    }

    applyFilter();
}

function removeDrillDownFilter (toRemove) {
    filter.drillDown = _.reject(filter.drillDown, function (currentFilter) {
        return _.matches(currentFilter)(toRemove);
    });
    applyFilter();
}

function addToPool (result, id, name) {
    if (!pools[id]) {
        pools[id] = {
            name: name,
            identifierHierarchy: result.identifierHierarchy,
            status: {
                ok: 0,
                ko: 0,
                skipped: 0,
                unknown: 0
            },
            tags: {

            },
            results: []
        };
    }

    var pool = pools[id];

    pool.results.push(result);

    if (_.has(pool.status, result.status)) {
        ++pool.status[result.status];
    } else {
        ++pool.status.unknown;
    }

    _.each(result.tags, function (value, tag) {

        if (typeof value === 'object') {
            return;
        }

        if (!_.has(pool.tags, tag)) {
            pool.tags[tag] = {};
        }
        pool.tags[tag][value] = true;
    });
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

    object._count = sum;

}

function sortTags (pool) {
    pool.tags = _.map(pool.tags, function (values, tag) {
        return {tag: tag, values: _.keys(values).sort()};
    }).sort(function (a, b) {
        return a.tag > b.tag;
    });
}

function applyFilter () {
    count = 0;
    firstDate = lastDate = undefined;
    pools = {};

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

        var name = result.identifierHierarchy.join(' :: ');
        var id = result.identifierHierarchy.join(' :: ');

        if (groupBy[name]) {
            _.each(groupBy[name], function (tag) {
                id += ' ' + result.tags[tag];
            });
        }

        for (var i = 0, len = filter.drillDown.length; i < len; ++i) {
            var condition = filter.drillDown[i];
            if (condition.type === 'name') {
                if (result.identifierHierarchy[condition.level] !== condition.value) {
                    return;
                }
            } else if (condition.type === 'tag') {
                if (condition.pool !== name) {
                    continue;
                }
                if (result.tags[condition.tag] !== condition.value) {
                    return;
                }
            }
        }

        firstDate = firstDate ? Math.min(firstDate, result.startedAt) : result.startedAt;
        lastDate = lastDate ? Math.max(lastDate, result.startedAt) : result.startedAt;

        addToPool(result, id, name);

        ++count;
    });

    _.each(pools, function (pool) {
        percentize(pool.status);
        sortTags(pool);
    });

    pools = _.map(pools, function (pool) {
        return pool;
    }).sort(function (a, b) {
        return a.status.ok_percent - b.status.ok_percent;
    });

    emit('change');
}

function connect () {
    socket = io.connect();

    socket.on('database updated', function (db) {
        database = db;
        applyFilter();
    });

    socket.on('database fragment', function (result) {
        database.push(result);
        applyFilter();
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
exports.updateFilter = updateFilter;

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

exports.getFilter = function () {
    var f = _.clone(filter);
    f.drillDown = _.map(filter.drillDown, _.clone);
    return f;
};

var autoupdate = true;

exports.autoupdate = function (auto) {
    if (auto === undefined) {
        return autoupdate;
    } else {
        autoupdate = auto ? true : false;
    }
};

exports.addDrillDownFilter = addDrillDownFilter;
exports.removeDrillDownFilter = removeDrillDownFilter;
exports.addGroupBy = addGroupBy;
exports.removeGroupBy = removeGroupBy;

exports.getGroupBy = function () {
    var gb = _.clone(groupBy);
    _.each(gb, function (tags, pool) {
        gb[pool] = _.clone(tags);
    });
    return gb;
};
