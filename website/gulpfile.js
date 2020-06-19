const browserSync = require('browser-sync').create();
const cleanCSS = require('gulp-clean-css');
const contact = require('gulp-concat');
const del = require('del');
const gulp = require('gulp');
const htmlmin = require('gulp-htmlmin');
const imagemin = require('gulp-imagemin');
const jshint = require('gulp-jshint');
const prettyError = require('gulp-prettyerror');
const sass = require('gulp-sass');
const sourcemaps = require('gulp-sourcemaps');
const uglify = require('gulp-uglify-es').default;

const createMetalsmith = require('./metalsmith')

// Static server
gulp.task('browser-sync', function () {
  browserSync.init({
    server: {
      baseDir: "build",
      index: "index.html"
    }
  });
});

// Build web from markdown
gulp.task('markdown', async () => {
  return new Promise((resolve) => {
    createMetalsmith().build(function (err) {
      if (err) {
        console.error(err)
      }

      browserSync.reload();
      resolve()
    });
  })
});

gulp.task('add-fonts', () => {
  return gulp
    .src(['assets/fonts/Roboto/*.*'])
    .pipe(gulp.dest('build/fonts/'));
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
      './node_modules/lunr/lunr.js',
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
    .pipe(htmlmin({collapseWhitespace: true}))
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
    .pipe(imagemin())
    .pipe(gulp.dest('./build/img'));
});
gulp.task('copy-uploads', () => {
  return gulp
    .src('./assets/uploads/*/*')
    .pipe(imagemin())
    .pipe(gulp.dest('./build/uploads'));
});

// COPY ICONS
gulp.task('copy-icons', () => {
  return gulp
    .src('./assets/ico/*')
    .pipe(gulp.dest('./build/ico'));
});

// COPY INDEX
gulp.task('copy-index', () => {
  return gulp
    .src('./build/docs/cs/index.html')
    .pipe(gulp.dest('./build/'));
});

// CLEAN DIST FOLDER
gulp.task('clean', () => {
  return del(
    [
      './build/*',
    ],
    {force: true, dot: true}
  );
});

// WATCH
gulp.task('watch', () => {
  gulp.watch('./assets/scss/**/*.scss', gulp.series('compile-scss'));
  gulp.watch('./assets/uploads/**/*.*', gulp.series('copy-uploads'));
  gulp.watch('./assets/img/**/*.*', gulp.series('copy-images'));
  gulp.watch('./assets/js/**/*.js', gulp.series('jshint', 'concat-js'));
  gulp.watch(['./src/**/*', './handlebars/**/*'], gulp.series('markdown'));
});

// BUILD PROD
gulp.task('build:prod', gulp.series(
  'clean',
  'jshint',
  gulp.parallel(
    'add-fonts',
    'compile-scss',
    'copy-images',
    'copy-uploads',
    'copy-icons',
    'concat-js',
    'markdown'
  ),
  gulp.parallel(
    'minify-css',
    'minify-js',
    'minify-html',
  ),
  'copy-index',
));

gulp.task('build:dev', gulp.series(
  'clean',
  'jshint',
  gulp.parallel(
    'add-fonts',
    'compile-scss',
    'copy-images',
    'copy-uploads',
    'copy-icons',
    'concat-js',
    'markdown'
  ),
  'copy-index',
));

gulp.task('default', gulp.series(
  'build:dev',
  gulp.parallel(
    'browser-sync',
    'watch'
  )
))

