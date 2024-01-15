const { src, dest } = require('gulp');
const sass = require('gulp-sass')(require('sass'));

function convertScssToCss() {
    return src('./assets/style.scss')
        .pipe(sass().on('error', sass.logError))
        .pipe(dest('./assets'))
}

exports.default = convertScssToCss