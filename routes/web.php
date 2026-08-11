<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlogCategoryController;
use App\Http\Controllers\CompanyApplicationController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CompanyAuthController;
use App\Http\Controllers\CmsController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseEnrollmentController;
use App\Http\Controllers\JobBoardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ToolController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Dashboard\EmailLogsController;
use App\Http\Controllers\Dashboard\EmailTemplatesController;
use App\Http\Controllers\Dashboard\SmsLogsController;
use Illuminate\Support\Facades\Route;

// Public landing page + registration
Route::get('/', [RegistrationController::class, 'landing'])->name('landing');
Route::view('/about', 'about')->name('about');
Route::get('/courses', [PageController::class, 'courses'])->name('courses');
Route::get('/enroll/callback', [CourseEnrollmentController::class, 'callback'])->name('courses.enroll.callback');
Route::post('/courses/{course}/register', [CourseEnrollmentController::class, 'store'])->name('courses.enroll.store');

// Applicant portal (login with Serial No + PIN, then complete the application)
Route::get('/application/login', [CourseEnrollmentController::class, 'loginForm'])->name('application.login');
Route::post('/application/login', [CourseEnrollmentController::class, 'login'])->name('application.login.attempt');
Route::post('/application/logout', [CourseEnrollmentController::class, 'logout'])->name('application.logout');
Route::get('/application', [CourseEnrollmentController::class, 'complete'])->name('application.complete');
Route::post('/application', [CourseEnrollmentController::class, 'submit'])->name('application.submit');
Route::post('/application/tuition', [CourseEnrollmentController::class, 'tuitionInit'])->name('application.tuition');
Route::get('/application/tuition/callback', [CourseEnrollmentController::class, 'tuitionCallback'])->name('application.tuition.callback');
Route::get('/courses/{course}', [PageController::class, 'showCourse'])->name('courses.show');
Route::get('/blog', [PageController::class, 'blog'])->name('blog');
Route::get('/blog/{post:slug}', [PageController::class, 'showPost'])->name('blog.show');
Route::view('/contact', 'contact')->name('contact');
Route::post('/contact', [ContactController::class, 'submit'])->name('contact.submit');

// Job board
Route::get('/jobs', [JobBoardController::class, 'index'])->name('jobs');
Route::get('/jobs/{opening}', [JobBoardController::class, 'show'])->name('jobs.show');
Route::post('/jobs/{opening}/apply', [JobBoardController::class, 'apply'])->name('jobs.apply');

// Company signup
Route::get('/companies/register', [CompanyAuthController::class, 'show'])->name('companies.register');
Route::post('/companies/register', [CompanyAuthController::class, 'register'])->name('companies.register.store');

// Company portal
Route::middleware(['auth', 'role:company'])->prefix('company')->name('company.')->group(function () {
    Route::get('/', [CompanyController::class, 'index'])->name('home');
    Route::post('/jobs', [CompanyController::class, 'store'])->name('jobs.store');
    Route::get('/jobs/{opening}/edit', [CompanyController::class, 'edit'])->name('jobs.edit');
    Route::put('/jobs/{opening}', [CompanyController::class, 'update'])->name('jobs.update');
    Route::delete('/jobs/{opening}', [CompanyController::class, 'destroy'])->name('jobs.destroy');
    Route::get('/jobs/{opening}/applications', [CompanyApplicationController::class, 'index'])->name('applications');
    Route::patch('/applications/{application}/status', [CompanyApplicationController::class, 'updateStatus'])->name('applications.status');
    Route::get('/applications/{application}/cv', [CompanyApplicationController::class, 'downloadCv'])->name('applications.cv');
    Route::get('/documents/{document}', [CompanyApplicationController::class, 'downloadDocument'])->name('applications.document');
});
Route::post('/register', [RegistrationController::class, 'store'])->name('register.store');
Route::get('/thank-you', [RegistrationController::class, 'thanks'])->name('register.thanks');

// Auth
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Profile (any authenticated user)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::delete('/profile/avatar', [ProfileController::class, 'removeAvatar'])->name('profile.avatar.remove');
});

// Admin dashboard (admins and supers only)
Route::middleware(['auth', 'role:admin|super'])->prefix('dashboard')->group(function () {
    Route::get('/', [DashboardController::class, 'home'])->name('dashboard');
    Route::get('/registrations', [DashboardController::class, 'index'])->name('dashboard.registrations');
    Route::get('/export', [DashboardController::class, 'export'])->name('dashboard.export');
    Route::get('/registrations/{registration}', [DashboardController::class, 'show'])->name('dashboard.show');
    Route::post('/registrations/{registration}/resend-email', [DashboardController::class, 'resendEmail'])->name('dashboard.registrations.resend-email');

    Route::get('/sms-logs', [SmsLogsController::class, 'index'])->name('dashboard.sms-logs');
    Route::post('/sms-logs/{smsLog}/retry', [SmsLogsController::class, 'retry'])->name('dashboard.sms-logs.retry');

    Route::get('/email-templates', [EmailTemplatesController::class, 'index'])->name('dashboard.email-templates');
    Route::get('/email-templates/{key}/edit', [EmailTemplatesController::class, 'edit'])->name('dashboard.email-templates.edit');
    Route::get('/email-templates/{key}/preview', [EmailTemplatesController::class, 'preview'])->name('dashboard.email-templates.preview');
    Route::put('/email-templates/{key}', [EmailTemplatesController::class, 'update'])->name('dashboard.email-templates.update');
    Route::delete('/email-templates/{key}', [EmailTemplatesController::class, 'reset'])->name('dashboard.email-templates.reset');
    Route::post('/email-templates/{key}/test', [EmailTemplatesController::class, 'test'])->name('dashboard.email-templates.test');

    Route::get('/email-logs', [EmailLogsController::class, 'index'])->name('dashboard.email-logs');
    Route::get('/email-logs/export', [EmailLogsController::class, 'export'])->name('dashboard.email-logs.export');
    Route::get('/email-logs/{emailLog}', [EmailLogsController::class, 'show'])->name('dashboard.email-logs.show');
    Route::post('/email-logs/{emailLog}/resend', [EmailLogsController::class, 'resend'])->name('dashboard.email-logs.resend');

    Route::get('/schedules', [ScheduleController::class, 'index'])->name('dashboard.schedules');
    Route::post('/schedules', [ScheduleController::class, 'store'])->name('dashboard.schedules.store');
    Route::get('/schedules/{schedule}/edit', [ScheduleController::class, 'edit'])->name('dashboard.schedules.edit');
    Route::put('/schedules/{schedule}', [ScheduleController::class, 'update'])->name('dashboard.schedules.update');
    Route::delete('/schedules/{schedule}', [ScheduleController::class, 'destroy'])->name('dashboard.schedules.destroy');

    Route::get('/schedules/export', [ScheduleController::class, 'export'])->name('dashboard.schedules.export');

    Route::get('/tools', [ToolController::class, 'index'])->name('dashboard.tools');
    Route::post('/tools', [ToolController::class, 'store'])->name('dashboard.tools.store');
    Route::get('/tools/export', [ToolController::class, 'export'])->name('dashboard.tools.export');
    Route::get('/tools/{tool}/edit', [ToolController::class, 'edit'])->name('dashboard.tools.edit');
    Route::put('/tools/{tool}', [ToolController::class, 'update'])->name('dashboard.tools.update');
    Route::delete('/tools/{tool}', [ToolController::class, 'destroy'])->name('dashboard.tools.destroy');

    Route::get('/courses', [CourseController::class, 'index'])->name('dashboard.courses');
    Route::post('/courses', [CourseController::class, 'store'])->name('dashboard.courses.store');
    Route::get('/courses/export', [CourseController::class, 'export'])->name('dashboard.courses.export');
    Route::get('/courses/{course}/edit', [CourseController::class, 'edit'])->name('dashboard.courses.edit');
    Route::put('/courses/{course}', [CourseController::class, 'update'])->name('dashboard.courses.update');
    Route::delete('/courses/{course}', [CourseController::class, 'destroy'])->name('dashboard.courses.destroy');

    Route::get('/cms', [CmsController::class, 'index'])->name('dashboard.cms');
    Route::get('/cms/{page}', [CmsController::class, 'edit'])->name('dashboard.cms.edit');
    Route::put('/cms/{page}', [CmsController::class, 'update'])->name('dashboard.cms.update');

    Route::get('/course-registrations', [CourseEnrollmentController::class, 'adminIndex'])->name('dashboard.course-registrations');
    Route::get('/course-registrations/export', [CourseEnrollmentController::class, 'export'])->name('dashboard.course-registrations.export');

    Route::get('/blog', [PostController::class, 'index'])->name('dashboard.blog');
    Route::post('/blog', [PostController::class, 'store'])->name('dashboard.blog.store');
    Route::get('/blog/export', [PostController::class, 'export'])->name('dashboard.blog.export');
    Route::get('/blog/{post}/edit', [PostController::class, 'edit'])->name('dashboard.blog.edit');
    Route::put('/blog/{post}', [PostController::class, 'update'])->name('dashboard.blog.update');
    Route::delete('/blog/{post}', [PostController::class, 'destroy'])->name('dashboard.blog.destroy');
    Route::post('/blog-categories', [BlogCategoryController::class, 'store'])->name('dashboard.blog.categories.store');
    Route::delete('/blog-categories/{category}', [BlogCategoryController::class, 'destroy'])->name('dashboard.blog.categories.destroy');

    Route::middleware('permission:manage users')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('dashboard.users');
        Route::post('/users', [UserController::class, 'store'])->name('dashboard.users.store');
        Route::get('/users/export', [UserController::class, 'export'])->name('dashboard.users.export');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('dashboard.users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('dashboard.users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('dashboard.users.destroy');
    });

    Route::middleware('permission:manage roles')->group(function () {
        Route::get('/roles', [RoleController::class, 'index'])->name('dashboard.roles');
        Route::post('/roles', [RoleController::class, 'store'])->name('dashboard.roles.store');
        Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('dashboard.roles.edit');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->name('dashboard.roles.update');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('dashboard.roles.destroy');
        Route::post('/permissions', [RoleController::class, 'storePermission'])->name('dashboard.permissions.store');
        Route::delete('/permissions/{permission}', [RoleController::class, 'destroyPermission'])->name('dashboard.permissions.destroy');
    });
});
