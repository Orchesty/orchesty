const gulp = require('gulp');
const uglify = require('gulp-uglify');
const sass = require('gulp-sass');
const contact = require('gulp-concat');
const sourcemaps = require('gulp-sourcemaps');
const jshint = require('gulp-jshint');
const cleanCSS = require('gulp-clean-css');
const del = require('del');
const browserSync = require('browser-sync').create();
const prettyError = require('gulp-prettyerror');
const htmlmin = require('gulp-htmlmin');
const debug = require('gulp-debug');

const createMetalsmith = require('./metalsmith')

// Static server
gulp.task('browser-sync', function() {
  browserSync.init({
    server: {
      baseDir: "build",
      index: "index.html"
    }
  });
});

// Build web from markdown
gulp.task('markdown', async () => {
  return new Promise((resolve )=> {
    createMetalsmith().build(function(err) {
      if (err) {
        console.error(err)
      }

      browserSync.reload();
      resolve()
    });
  })
});

gulp.task('compile-scss', () => {
  return gulp
    .src('./assets/scss/main.scss')
    .pipe(prettyError())
    .pipe(sourcemaps.init())
    .pipe(sass())
    .pipe(sourcemaps.write())
    .pipe(gulp.dest('build/css'))
    .pipe(browserSync.reload({
      stream: true
    }))
});

// MINIFY CSS
gulp.task('minify-css', () => {
  return gulp
    .src('./build/css/**/*.css')
    .pipe(cleanCSS())
    .pipe(gulp.dest('./build/css'));
});

// CONCAT JAVASCRIPT FOR FRONTEND MODULE
gulp.task('concat-js', () => {
  return gulp
    .src([
      './node_modules/materialize-css/dist/js/materialize.js',
      './assets/js/**/*.js'
    ])
    .pipe(sourcemaps.init())
    .pipe(contact('main.js', {newLine: ';'}))
    .pipe(sourcemaps.write())
    .pipe(gulp.dest('./build/js'))
    .pipe(browserSync.reload({
      stream: true
    }));
});

//  MINIFY JAVASCRIPT
gulp.task('minify-js', () => {
  return gulp
    .src('./build/js/**/*.js')
    .pipe(uglify())
    .pipe(gulp.dest('./build/js'));
});

//  MINIFY HTML
gulp.task('minify-html', () => {
  return gulp
    .src('./build/**/*.html')
    .pipe(prettyError())
    .pipe(htmlmin({ collapseWhitespace: true }))
    .pipe(gulp.dest('./build'));
});


// JAVASCRIPT CODE CONTROL
gulp.task('jshint', () => {
  return gulp
    .src([
      './assets/js/**/*.js',
    ])
    .pipe(jshint())
    .pipe(jshint.reporter('default'))
});

// COPY IMAGES
gulp.task('copy-images', () => {
  return gulp
    .src('./assets/img/*')
    .pipe(gulp.dest('./build/img'));
});


// CLEAN DIST FOLDER
gulp.task('clean', () => {
  return del(
    [
      // CSS
      './build/*',
    ],
    {force: true, dot: true}
  );
});

// WATCH
gulp.task('watch', () => {
  gulp.watch('./assets/scss/**/*.scss', gulp.series('compile-scss'));
  gulp.watch('./assets/js/**/*.js', gulp.series('jshint', 'concat-js'));
  gulp.watch(['./src/**/*', './handlebars/**/*'], gulp.series('markdown'));
});

// BUILD PROD
gulp.task('build:prod', gulp.series(
  'clean',
  'jshint',
  gulp.parallel(
    'compile-scss',
    'copy-images',
    'concat-js',
    'markdown'
  ),
  gulp.parallel(
    'minify-css',
    'minify-js',
    'minify-html',
  ),
));

gulp.task('build:dev', gulp.series(
  'clean',
  'jshint',
  gulp.parallel(
    'compile-scss',
    'copy-images',
    'concat-js',
    'markdown'
  ),
));

gulp.task('default', gulp.series(
  'build:dev',
  gulp.parallel(
    'browser-sync',
    'watch'
  )
))

