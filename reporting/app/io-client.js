var socket;
var ioClient = window.io;
delete window.io;

module.exports = {
    getSocket: function () {
        if (!socket) {
            socket = ioClient.connect();
        }

        return socket;
    }
};
