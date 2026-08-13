<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlogCategoryController;
use App\Http\Controllers\CmsController;
use App\Http\Controllers\CompanyApplicationController;
use App\Http\Controllers\CompanyAuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyPlanController;
use App\Http\Controllers\CompanyTalentController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseEnrollmentController;
use App\Http\Controllers\Dashboard\BulkEmailController;
use App\Http\Controllers\Dashboard\EditorImageController;
use App\Http\Controllers\Dashboard\EmailLogsController;
use App\Http\Controllers\Dashboard\EmailTemplatesController;
use App\Http\Controllers\Dashboard\PlanPurchaseController;
use App\Http\Controllers\Dashboard\RecruiterPlanController;
use App\Http\Controllers\Dashboard\SmsLogsController;
use App\Http\Controllers\Dashboard\SurveyManagerController;
use App\Http\Controllers\Dashboard\SurveyQuestionsController;
use App\Http\Controllers\Dashboard\SurveysController;
use App\Http\Controllers\Dashboard\TalentPoolController as DashboardTalentPoolController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JobBoardController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SessionVideoController;
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\TalentPoolController;
use App\Http\Controllers\ToolController;
use App\Http\Controllers\UserController;
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
Route::get('/videos', [PageController::class, 'videos'])->name('videos');
Route::get('/blog', [PageController::class, 'blog'])->name('blog');
Route::get('/blog/{post:slug}', [PageController::class, 'showPost'])->name('blog.show');
Route::view('/contact', 'contact')->name('contact');
Route::post('/contact', [ContactController::class, 'submit'])->name('contact.submit');

// Pulse Check — public session check-in surveys. No login: the link is handed
// out in the room, so `thanks` is registered before the {type} catch-all.
Route::get('/check-in', [SurveyController::class, 'index'])->name('surveys.index');
Route::get('/check-in/thanks', [SurveyController::class, 'thanks'])->name('surveys.thanks');
Route::get('/check-in/{survey}', [SurveyController::class, 'show'])->name('surveys.show');
Route::post('/check-in/{survey}', [SurveyController::class, 'store'])->name('surveys.store');

// Job board
Route::get('/jobs', [JobBoardController::class, 'index'])->name('jobs');
Route::get('/jobs/{opening}', [JobBoardController::class, 'show'])->name('jobs.show');
Route::post('/jobs/{opening}/apply', [JobBoardController::class, 'apply'])->name('jobs.apply');

// Talent pool — drop a CV without applying to a specific job
Route::get('/talent-pool', [TalentPoolController::class, 'create'])->name('talent.create');
Route::post('/talent-pool', [TalentPoolController::class, 'store'])->name('talent.store');

// Company signup
Route::get('/companies/register', [CompanyAuthController::class, 'show'])->name('companies.register');
Route::post('/companies/register', [CompanyAuthController::class, 'register'])->name('companies.register.store');

// Paystack sends the recruiter's browser back here after paying for credits.
// Outside the auth group on purpose: verifying a payment must not depend on
// the session still being alive.
Route::get('/company/plans/callback', [CompanyPlanController::class, 'callback'])->name('company.plans.callback');

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

    // Talent pool + the credits that open it
    Route::get('/talent', [CompanyTalentController::class, 'index'])->name('talent');
    Route::post('/talent/{profile}/unlock', [CompanyTalentController::class, 'unlock'])->name('talent.unlock');
    Route::get('/talent/{profile}/cv', [CompanyTalentController::class, 'downloadCv'])->name('talent.cv');

    Route::get('/plans', [CompanyPlanController::class, 'index'])->name('plans');
    Route::post('/plans/{plan}/checkout', [CompanyPlanController::class, 'checkout'])->name('plans.checkout');
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

    // Shared by every Quill editor (admin dashboard + company portal), so it
    // sits here rather than inside the admin-only group.
    Route::post('/editor/image', [EditorImageController::class, 'store'])->name('editor.image');
});

// Admin dashboard (admins and supers only)
Route::middleware(['auth', 'role:admin|super'])->prefix('dashboard')->group(function () {
    Route::get('/', [DashboardController::class, 'home'])->name('dashboard');
    Route::get('/registrations', [DashboardController::class, 'index'])->name('dashboard.registrations');
    Route::get('/export', [DashboardController::class, 'export'])->name('dashboard.export');
    Route::get('/registrations/bulk-email', [BulkEmailController::class, 'create'])->name('dashboard.registrations.bulk-email');
    Route::post('/registrations/bulk-email', [BulkEmailController::class, 'send'])->name('dashboard.registrations.bulk-email.send');
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

    Route::get('/surveys', [SurveysController::class, 'index'])->name('dashboard.surveys');
    Route::get('/surveys/export', [SurveysController::class, 'export'])->name('dashboard.surveys.export');
    Route::get('/surveys/poster', [SurveysController::class, 'poster'])->name('dashboard.surveys.poster');
    Route::get('/surveys/manage', [SurveyManagerController::class, 'index'])->name('dashboard.surveys.manage');
    Route::post('/surveys/manage', [SurveyManagerController::class, 'store'])->name('dashboard.surveys.manage.store');
    Route::get('/surveys/manage/{survey}/edit', [SurveyManagerController::class, 'edit'])->name('dashboard.surveys.manage.edit');
    Route::put('/surveys/manage/{survey}', [SurveyManagerController::class, 'update'])->name('dashboard.surveys.manage.update');
    Route::post('/surveys/manage/{survey}/duplicate', [SurveyManagerController::class, 'duplicate'])->name('dashboard.surveys.manage.duplicate');
    Route::delete('/surveys/manage/{survey}', [SurveyManagerController::class, 'destroy'])->name('dashboard.surveys.manage.destroy');

    Route::get('/surveys/questions', [SurveyQuestionsController::class, 'index'])->name('dashboard.surveys.questions');
    Route::post('/surveys/questions', [SurveyQuestionsController::class, 'store'])->name('dashboard.surveys.questions.store');
    Route::get('/surveys/questions/{question}/edit', [SurveyQuestionsController::class, 'edit'])->name('dashboard.surveys.questions.edit');
    Route::put('/surveys/questions/{question}', [SurveyQuestionsController::class, 'update'])->name('dashboard.surveys.questions.update');
    Route::delete('/surveys/questions/{question}', [SurveyQuestionsController::class, 'destroy'])->name('dashboard.surveys.questions.destroy');
    Route::get('/surveys/responses/{response}', [SurveysController::class, 'show'])->name('dashboard.surveys.response');
    Route::delete('/surveys/responses/{response}', [SurveysController::class, 'destroy'])->name('dashboard.surveys.response.destroy');

    Route::get('/tools', [ToolController::class, 'index'])->name('dashboard.tools');
    Route::post('/tools', [ToolController::class, 'store'])->name('dashboard.tools.store');
    Route::get('/tools/export', [ToolController::class, 'export'])->name('dashboard.tools.export');
    Route::get('/tools/{tool}/edit', [ToolController::class, 'edit'])->name('dashboard.tools.edit');
    Route::put('/tools/{tool}', [ToolController::class, 'update'])->name('dashboard.tools.update');
    Route::delete('/tools/{tool}', [ToolController::class, 'destroy'])->name('dashboard.tools.destroy');

    Route::get('/videos', [SessionVideoController::class, 'index'])->name('dashboard.videos');
    Route::post('/videos', [SessionVideoController::class, 'store'])->name('dashboard.videos.store');
    Route::get('/videos/export', [SessionVideoController::class, 'export'])->name('dashboard.videos.export');
    Route::get('/videos/{video}/edit', [SessionVideoController::class, 'edit'])->name('dashboard.videos.edit');
    Route::put('/videos/{video}', [SessionVideoController::class, 'update'])->name('dashboard.videos.update');
    Route::delete('/videos/{video}', [SessionVideoController::class, 'destroy'])->name('dashboard.videos.destroy');

    Route::get('/courses', [CourseController::class, 'index'])->name('dashboard.courses');
    Route::post('/courses', [CourseController::class, 'store'])->name('dashboard.courses.store');
    Route::get('/courses/export', [CourseController::class, 'export'])->name('dashboard.courses.export');
    Route::get('/courses/{course}/edit', [CourseController::class, 'edit'])->name('dashboard.courses.edit');
    Route::put('/courses/{course}', [CourseController::class, 'update'])->name('dashboard.courses.update');
    Route::delete('/courses/{course}', [CourseController::class, 'destroy'])->name('dashboard.courses.destroy');

    Route::get('/recruiter-plans', [RecruiterPlanController::class, 'index'])->name('dashboard.recruiter-plans');
    Route::post('/recruiter-plans', [RecruiterPlanController::class, 'store'])->name('dashboard.recruiter-plans.store');
    Route::get('/recruiter-plans/export', [RecruiterPlanController::class, 'export'])->name('dashboard.recruiter-plans.export');
    Route::get('/recruiter-plans/{plan}/edit', [RecruiterPlanController::class, 'edit'])->name('dashboard.recruiter-plans.edit');
    Route::put('/recruiter-plans/{plan}', [RecruiterPlanController::class, 'update'])->name('dashboard.recruiter-plans.update');
    Route::delete('/recruiter-plans/{plan}', [RecruiterPlanController::class, 'destroy'])->name('dashboard.recruiter-plans.destroy');

    Route::get('/plan-purchases', [PlanPurchaseController::class, 'index'])->name('dashboard.plan-purchases');
    Route::get('/plan-purchases/export', [PlanPurchaseController::class, 'export'])->name('dashboard.plan-purchases.export');
    Route::post('/plan-purchases/grant', [PlanPurchaseController::class, 'grant'])->name('dashboard.plan-purchases.grant');

    Route::get('/talent-pool', [DashboardTalentPoolController::class, 'index'])->name('dashboard.talent-pool');
    Route::get('/talent-pool/export', [DashboardTalentPoolController::class, 'export'])->name('dashboard.talent-pool.export');
    Route::get('/talent-pool/{profile}', [DashboardTalentPoolController::class, 'show'])->name('dashboard.talent-pool.show');
    Route::get('/talent-pool/{profile}/cv', [DashboardTalentPoolController::class, 'downloadCv'])->name('dashboard.talent-pool.cv');
    Route::delete('/talent-pool/{profile}', [DashboardTalentPoolController::class, 'destroy'])->name('dashboard.talent-pool.destroy');

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
