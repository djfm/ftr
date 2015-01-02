exports.config =
files:
    javascripts:
        joinTo: 'app.js'
    stylesheets:
        joinTo: 'app.css'
    templates:
        joinTo: 'app.js'

modules:
    nameCleaner: (path) ->
      path
        .replace /^app\/(?:externals\/)?/, ''
        .replace /-\d+(?:\.\d+)+/, ''
        .replace /moment-with-locales\.js$/, 'moment.js'
