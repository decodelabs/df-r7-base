module.exports = function (data) {
    var autoprefixer = require('autoprefixer');
    var postcss = require('postcss');

    var output = postcss([autoprefixer(data.settings)]).process(data.css, {
        from: data.path,
        to: data.path,
        map: data.map
    });

    return {
        css: output.css,
        map: output.map
    };
}
