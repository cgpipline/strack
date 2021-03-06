var gulp = require('gulp'),
    minifyCss = require('gulp-minify-css'),
    plumber = require('gulp-plumber'),
    babel = require('gulp-babel'),
    concat = require('gulp-concat'),
    uglify = require('gulp-uglify'),
    clearnHtml = require("gulp-cleanhtml"),
    imagemin = require('gulp-imagemin'),
    rename = require('gulp-rename'),
    del = require('del'),
    copy = require('gulp-contrib-copy');

/******************************************************
 * 压缩 JS 文件
 ******************************************************/
//压缩js--主要js
var js_main_src = 'Public/js/src',
    js_main_dist = 'Public/js/build';

gulp.task('mainjs', function (done) {
     gulp.src(js_main_src + '/*.js')
        .pipe(plumber())
        .pipe(uglify({
            mangle: true,//类型：Boolean 默认：true 是否修改变量名
            compress: true//类型：Boolean 默认：true 是否完全压缩
        }))
        .pipe(rename({suffix: '.min'}))//rename压缩后的文件名
        .pipe(gulp.dest(js_main_dist));
     done();
});

//压缩js--login登录页面
var js_login_src = 'Public/js/src/login',
    js_login_dist = 'Public/js/build/login';

gulp.task('loginjs', function (done) {
    gulp.src(js_login_src + '/*.js')
        .pipe(plumber())
        .pipe(uglify())
        .pipe(rename({suffix: '.min'}))//rename压缩后的文件名
        .pipe(gulp.dest(js_login_dist));
    done();
});

//压缩js--admin后台页面
var js_admin_src = 'Public/js/src/admin',
    js_admin_dist = 'Public/js/build/admin';

gulp.task('adminjs', function (done) {
    gulp.src(js_admin_src + '/*.js')
        .pipe(plumber())
        .pipe(uglify())
        .pipe(rename({suffix: '.min'}))//rename压缩后的文件名
        .pipe(gulp.dest(js_admin_dist));
    done();
});

//压缩js--plan计划页面
var js_plan_src = 'Public/js/src/scheduler',
    js_plan_dist = 'Public/js/build/scheduler';

gulp.task('planjs', function (done) {
    gulp.src(js_plan_src + '/*.js')
        .pipe(plumber())
        .pipe(uglify())
        .pipe(rename({suffix: '.min'}))//rename压缩后的文件名
        .pipe(gulp.dest(js_plan_dist));
    done();
});

//压缩js--home前台页面
var js_home_src = 'Public/js/src/home',
    js_home_dist = 'Public/js/build/home';

gulp.task('homejs', function (done) {
    gulp.src(js_home_src + '/*.js')
        .pipe(plumber())
        .pipe(uglify())
        .pipe(rename({suffix: '.min'}))//rename压缩后的文件名
        .pipe(gulp.dest(js_home_dist));
    done();
});

/******************************************************
 * 压缩 CSS 文件
 ******************************************************/

// 压缩css--主要文件
var css_main_src = 'Public/css/src',
    css_main_dist = 'Public/css/build';

gulp.task('maincss', function (done) {
    gulp.src(css_main_src + '/*.css')
        .pipe(minifyCss())
        .pipe(rename({suffix: '.min'}))//rename压缩后的文件名
        .pipe(gulp.dest(css_main_dist))
    done();
});

// 压缩css--压缩ui文件
gulp.task('uicss', function (done) {
    gulp.src('Public/themes/black/strack.ui.css')
        .pipe(minifyCss())
        .pipe(rename({suffix: '.min'}))//rename压缩后的文件名
        .pipe(gulp.dest('Public/themes/black'));
    gulp.src('Public/themes/white/strack.ui.css')
        .pipe(minifyCss())
        .pipe(rename({suffix: '.min'}))//rename压缩后的文件名
        .pipe(gulp.dest('Public/themes/white'));
    done();
});

//清理build目录
gulp.task('clear_build', async function () {
    await del(['Public/js/build/*']);
    await del(['Public/css/build/*']);
});

//默认命令，在cmd中输入gulp后，执行的就是这个命令
gulp.task('default', gulp.series('clear_build', 'mainjs', 'loginjs', 'adminjs', 'planjs', 'homejs', 'maincss', 'uicss'));


// // 压缩全部html
// gulp.task('html', function (done) {
//     gulp.src(src+'/**/*.+(html|tpl)')
//         .pipe(clearnHtml())
//         .pipe(gulp.dest(dist));
//     done();
// });
//
// // 压缩全部image
// gulp.task('image', function (done) {
//     gulp.src([src+'/**/*.+(jpg|jpeg|png|gif|bmp)'])
//         .pipe(imagemin())
//         .pipe(gulp.dest(dist));
//     done();
// });
//
// // 其他不编译的文件直接copy
// gulp.task('copy', function (done) {
//     gulp.src(src+'/**/*.!(jpg|jpeg|png|gif|bmp|scss|js|html|tpl)')
//         .pipe(copy())
//         .pipe(gulp.dest(dist));
//     done();
// });
