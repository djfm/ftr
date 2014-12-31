var express = require('express');
var fs = require('fs');
var path = require('path');

var argv = require('minimist')(process.argv.slice(2));
var port = argv.port || 3000;

var folder = argv._[0] || '.';

var app = express();
var server = require('http').Server(app);
var io = require('socket.io')(server);

app.use(express.static(path.join(__dirname, '..', 'public')));
app.use('/app/styles', express.static(path.join(__dirname, '..', 'app', 'styles')));


server.listen(port, function () {
    console.log('Reporting viewer listening on port %d', port);
});
