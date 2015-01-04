var express = require('express');
var fs = require('fs');
var path = require('path');
var q = require('q');
var Tail = require('tail').Tail;
var _ = require('underscore');

var argv = require('minimist')(process.argv.slice(2));
var port = argv.port || 3000;

var folder = argv._[0] || '.';

var app = express();
var server = require('http').Server(app);
var io = require('socket.io')(server);

var database, databaseLoading, tail;

var streamFile = path.join(folder, 'test-history', 'history.json.stream');

function loadDatabase () {
    if (database) {
        return q(database);
    } else if (databaseLoading) {
        return databaseLoading;
    } else {
        var d = q.defer();
        databaseLoading = d.promise;

        if (!fs.existsSync(path.dirname(streamFile))) {
            fs.mkdirSync(path.dirname(streamFile));
        }

        if (!fs.existsSync(streamFile)) {
            fs.writeFileSync(streamFile, '');
        }

        fs.readFile(streamFile, function (err, data) {
            if (err) {
                d.reject(err);
            } else {
                database = [];
                _.each(data.toString().split("\n"), function (line) {
                    try {
                        database.push(JSON.parse(line));
                    } catch (e){
                        // ignore invalid line
                    }
                });

                if (!tail) {
                    tail = new Tail(streamFile);
                    tail.on('line', function (line) {
                        var newResult = JSON.parse(line);
                        database.push(newResult);
                        io.sockets.emit('database fragment', newResult);
                    });
                }

                d.resolve(database);
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
