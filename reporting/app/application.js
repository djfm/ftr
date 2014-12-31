var application = {
    initialize: function () {
        var Router = require('./router');
        var HomeView = require('./views/home');
        
        this.router = new Router();

        this.homeView = new HomeView();
    }
};

module.exports = application;
