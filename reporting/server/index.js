var express = require('express');
var fs = require('fs');
var path = require('path');
var q = require('q');
var Tail = require('tail').Tail;
var _ = require('underscore');
var gm = require('gm');
var watchr = require('watchr');

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

                    watchr.watch({
                        paths: [streamFile],
                        listeners: {
                            change: function (changeType, file, stat) {
                                if (stat.size === 0) {
                                    database = [];
                                    io.sockets.emit('database updated', database);
                                }
                            }
                        }
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

app.get('/screenshots', function (req, res) {
    var root = req.param('root');
    var screenshotsDir = path.join(folder, 'test-history', root, 'screenshots');
    fs.readdir(screenshotsDir, function (err, entries) {
        var data = [];
        _.each(entries, function (entry) {

            if (/_\d+x\d+\.\w+$/.exec(entry)) {
                return;
            }

            var fullsize = '/artefacts?path=' + encodeURIComponent(path.join(root, 'screenshots', entry));

            data.push({
                name: path.basename(entry),
                fullsize: fullsize,
                thumbnail: fullsize + '&thumbnail=1'
            });
        });
        res.send(data);
    });
});

app.get('/metadata', function (req, res) {
    var root = req.param('root');
    var metadataDir = path.join(folder, 'test-history', root);

    fs.readdir(metadataDir, function (err, entries) {
        if (err) {
            res.status(404).end();
        } else {
            var data = {files: []};
            _.each(entries, function (entry) {
                if (entry === 'screenshots') {
                    return;
                }

                if (entry === 'metadata.json') {
                    data.metadata = JSON.parse(fs.readFileSync(path.join(metadataDir, entry)));
                    return;
                }

                data.files.push({
                    name: entry,
                    url: '/artefacts?path=' + encodeURIComponent(path.join(root, entry))
                });
            });
            res.send(data);
        }
    });
});

app.get('/artefacts', function (req, res) {
    var relPath = path.normalize(path.sep + req.param('path')); // this trims all '..' for safety
    var filePath = path.normalize(path.join(folder, 'test-history', relPath));

    fs.exists(filePath, function (exists) {
        if (exists) {
            if (req.param('thumbnail')) {
                var width = 200, height = 200;
                var thumbnailFileName = path.join(
                    path.dirname(filePath),
                    path.basename(filePath, path.extname(filePath)) + '_' + width + 'x' + height + path.extname(filePath)
                );
                fs.exists(thumbnailFileName, function (yes) {
                    if (yes) {
                        res.sendFile(path.resolve(thumbnailFileName));
                    } else {
                        gm(filePath)
                        .options({imageMagick: true})
                        .resize(width, height)
                        .write(thumbnailFileName, function (err) {
                            if (err) {
                                res.status(500).send(err.toString());
                            } else {
                                res.sendFile(path.resolve(thumbnailFileName));
                            }
                        });
                    }
                });
            } else {
                res.sendFile(path.resolve(filePath));
            }
        } else {
            res.status(404).send('File not found.');
        }
    });
});

app.get('/live', function (req, res) {
    var relPath = path.normalize(path.sep + req.param('path')); // this trims all '..' for safety
    var filePath = path.normalize(path.join(folder, relPath));
    res.sendFile(path.resolve(filePath));
});

var liveFolder = path.join(folder, 'test-results');

if (!fs.existsSync(liveFolder)) {
    fs.mkdirSync(liveFolder);
}

watchr.watch({
    paths: [liveFolder],
    listeners: {
        change: function (changeType, file, stat) {
            if (stat.nlink !== 0 && /\.jpg$/.exec(file)) {
                var rel = path.relative(folder, file);
                var data = {
                    name: path.basename(file, '.jpg'),
                    url: '/live?path=' + encodeURIComponent(rel),
                    group: path.dirname(path.relative(path.join(folder, 'test-results'), path.dirname(file)))
                };
                io.sockets.emit('new live screenshot', data);
            }
        }
    }
});

server.listen(port, function () {
    console.log('Reporting viewer listening on port %d', port);
});
