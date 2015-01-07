var express = require('express');
var fs = require('fs');
var path = require('path');
var q = require('q');
var Tail = require('tail').Tail;
var _ = require('underscore');
var gm = require('gm');
var watch = require('watch');

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
                        res.sendFile(thumbnailFileName);
                    } else {
                        gm(filePath)
                        .options({imageMagick: true})
                        .resize(width, height)
                        .write(thumbnailFileName, function (err) {
                            if (err) {
                                res.status(500).send(err.toString());
                            } else {
                                res.sendFile(thumbnailFileName);
                            }
                        });
                    }
                });
            } else {
                res.sendFile(filePath);
            }
        } else {
            res.status(404).send('File not found.');
        }
    });
});

app.get('/live', function (req, res) {
    var relPath = path.normalize(path.sep + req.param('path')); // this trims all '..' for safety
    var filePath = path.normalize(path.join(folder, relPath));

    res.sendFile(filePath);
});

var liveFolder = path.join(folder, 'test-results');

if (!fs.existsSync(liveFolder)) {
    fs.mkdirSync(liveFolder);
}

watch.watchTree(liveFolder, {
    ignoreDotFiles: true,
    ingoreUnreadableDir: true
}, function (file, stat, prevStat) {
    if ('object' === typeof file) {
        // ignore, called on setup
    } else if (!prevStat) {
        // new file
        if (/\.jpg$/.exec(file)) {
            var rel = path.relative(folder, file);
            var data = {
                name: path.basename(file, '.jpg'),
                url: '/live?path=' + encodeURIComponent(rel),
                group: path.dirname(path.relative(path.join(folder, 'test-results'), path.dirname(file)))
            };
            io.sockets.emit('new live screenshot', data);
        }
    } else if (stat.nlink === 0) {
        // file removed
    } else {
        // file changed
    }
});

server.listen(port, function () {
    console.log('Reporting viewer listening on port %d', port);
});
