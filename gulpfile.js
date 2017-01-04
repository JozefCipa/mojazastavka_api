var gulp = require('gulp');
var browserify = require('browserify');
var babelify = require('babelify');
var source = require('vinyl-source-stream');
var sass = require('gulp-ruby-sass');
var notify = require('gulp-notify');
var cleanCSS = require('gulp-clean-css');
var livereload = require('gulp-livereload');

var vendors = ['react', 'react-dom'];

//SASS
gulp.task('build:sass', () => {
        sass('resources/assets/sass/*.sass')
        	.on('error', sass.logError)
	        .pipe(cleanCSS())
			.pipe(notify({
				message: "SASS compiled."
			}))
	        .pipe(gulp.dest('public/dist/css'))
	        .pipe(livereload());                                   
});

gulp.task('watch:sass', () => {
	livereload.listen();

	gulp.watch('./resources/assets/sass/*.sass', ['build:sass']);
});

//React, ES6, JSX
gulp.task('build:react', () => {
	browserify({
		entries: ['./resources/assets/js/app.jsx'],
		extensions: ['.js', '.jsx'],
		debug: true
	})
		.external(vendors) // Specify all vendors as external source
		.transform('babelify', {presets: ['es2015', 'react']})
		.bundle()
		.pipe(source('bundle.js'))
		.pipe(notify({
			message: "React compiled."
		}))
		.pipe(gulp.dest('./public/dist/js/'))
		.pipe(livereload());
});

gulp.task('watch:react', () => {
	livereload.listen();
	gulp.watch(['./resources/assets/js/**/*.jsx', './resources/assets/js/**/*.js'], ['build:react']);
});

gulp.task('build:vendor', () => {
	const b = browserify({
		debug: true
	});

	// require all libs specified in vendors array
	vendors.forEach(function (lib) {
		b.require(lib);
	});

	b.bundle()
		.pipe(source('vendor.js'))
		.pipe(notify({
			message: "Vendors compiled."
		}))
		.pipe(gulp.dest('./public/dist/js/'));
});