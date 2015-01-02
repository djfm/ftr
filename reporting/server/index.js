var express = require('express');
var fs = require('fs');
var path = require('path');
var q = require('q');
var _ = require('underscore');

var argv = require('minimist')(process.argv.slice(2));
var port = argv.port || 3000;

var folder = argv._[0] || '.';

var app = express();
var server = require('http').Server(app);
var io = require('socket.io')(server);

var database, databaseLoading;

function loadDatabase () {
    if (database) {
        return q(database);
    } else if (databaseLoading) {
        return databaseLoading;
    }else {
        databaseLoading = q.defer();
        var streamFile = path.join(folder, 'test-history', 'history.json.stream');
        fs.readFile(streamFile, function (err, data) {
            if (err) {
                databaseLoading.reject(err);
            } else {
                database = _.map(data.toString().split("\n"), function (line) {
                    return JSON.parse(line);
                });
                databaseLoading.resolve(database);
            }
            databaseLoading = undefined;
        });
        return databaseLoading;
    }
}

loadDatabase();

io.on('connection', function (socket) {
    loadDatabase().then(function (database) {
        socket.emit('database updated', database);
    }).fail(function (reason) {
        socket.emit('database update failed', reason.toString());
    });
});

app.use(express.static(path.join(__dirname, '..', 'public')));
app.use('/app/styles', express.static(path.join(__dirname, '..', 'app', 'styles')));

server.listen(port, function () {
    console.log('Reporting viewer listening on port %d', port);
});
