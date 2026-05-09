<?php

use App\Http\Controllers\Admin\AdminAssetController;
use App\Http\Controllers\Admin\ApiTokenController;
use App\Http\Controllers\Admin\AssetFileController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BlockController;
use App\Http\Controllers\Admin\BlockGroupController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DatabaseController;
use App\Http\Controllers\Admin\DocsRequestController;
use App\Http\Controllers\Admin\DocumentController;
use App\Http\Controllers\Admin\LayoutController;
use App\Http\Controllers\Admin\ModuleController;
use App\Http\Controllers\Admin\ModuleTemplateController;
use App\Http\Controllers\Admin\NavigationController;
use App\Http\Controllers\Admin\NavigationItemController;
use App\Http\Controllers\Admin\NavigationTemplateController;
use App\Http\Controllers\Admin\RedirectController;
use App\Http\Controllers\Admin\RubricController;
use App\Http\Controllers\Admin\RubricFieldController;
use App\Http\Controllers\Admin\RubricPermissionController;
use App\Http\Controllers\Admin\RubricSubfieldController;
use App\Http\Controllers\Admin\RubricTemplateController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SystemLogController;
use App\Http\Controllers\Admin\UpdateController;
use App\Http\Controllers\Admin\UploadController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserGroupController;
use App\Http\Controllers\Api\RubricController as ApiRubricController;
use App\Http\Controllers\Install\InstallController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\RssController;
use App\Http\Controllers\SitemapController;
use App\Support\Permission;
use Illuminate\Support\Facades\Route;

Route::prefix('install')->name('install.')->group(function () {
    Route::get('/', [InstallController::class, 'index'])->name('index');
    Route::get('/step/{step}', [InstallController::class, 'show'])->name('step')->where('step', '[1-5]');

    Route::post('/step/1', [InstallController::class, 'processStep1'])->name('step1.post');
    Route::post('/step/2', [InstallController::class, 'processStep2'])->name('step2.post');
    Route::post('/step/3', [InstallController::class, 'processStep3'])->name('step3.post');
    Route::post('/step/4', [InstallController::class, 'processStep4'])->name('step4.post');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post')->middleware('throttle:5,1');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email')->middleware('throttle:5,1');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update')->middleware('throttle:5,1');
});

Route::get('/admin/assets/{path}', AdminAssetController::class)
     ->where('path', '.*')
     ->name('admin.asset');

Route::get('/media/{uuid}', [MediaController::class, 'show'])
     ->where('uuid', '[0-9a-fA-F\-]{10,}')
     ->name('media.show');

Route::any('/module/{sys_name}/{path?}', [ModuleController::class, 'frontDispatch'])
     ->where(['sys_name' => '[a-z_][a-z0-9_]*', 'path' => '.*'])
     ->name('module.front');

Route::prefix('admin')->name('admin.')->middleware(['auth.admin:admin', 'admin.active', 'last.seen'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');
    Route::get('/metric', [DashboardController::class, 'metric'])->name('dashboard.metric');
    Route::get('/online-users', [DashboardController::class, 'onlineUsers'])->name('dashboard.online-users');
    Route::get('/widgets', [DashboardController::class, 'widgets'])->name('dashboard.widgets');

    Route::post('/cache/clear', [SettingsController::class, 'clearAllCache'])->name('cache.clear')->middleware(Permission::perm(Permission::CACHE_CLEAR));

    Route::get('/platform/check', [UpdateController::class, 'check'])->name('platform.check');
    Route::post('/platform/update', [UpdateController::class, 'perform'])->name('platform.update')->middleware(Permission::perm(Permission::SETTINGS_MANAGE));

    $placeholder = fn () => redirect()->route('admin.dashboard');

    Route::post('/documents/bulk', [DocumentController::class, 'bulkAction'])->name('documents.bulk')->middleware([Permission::perm(Permission::DOCUMENTS_ACCESS), Permission::perm(Permission::DOCUMENTS_LIST)]);
    Route::get('/documents/rubric-fields', [DocumentController::class, 'rubricFields'])->name('documents.rubricFields')->middleware([Permission::perm(Permission::DOCUMENTS_ACCESS), Permission::perm(Permission::DOCUMENTS_LIST)]);
    Route::get('/documents/search', [DocumentController::class, 'search'])->name('documents.search')->middleware(Permission::perm(Permission::DOCUMENTS_ACCESS));
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index')->middleware([Permission::perm(Permission::DOCUMENTS_ACCESS), Permission::perm(Permission::DOCUMENTS_LIST)]);
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store')->middleware([Permission::perm(Permission::DOCUMENTS_ACCESS), Permission::perm(Permission::DOCUMENTS_CREATE)]);
    Route::post('/documents/normalize-positions', [DocumentController::class, 'normalizePositions'])->name('documents.normalizePositions')->middleware([Permission::perm(Permission::DOCUMENTS_ACCESS), Permission::perm(Permission::DOCUMENTS_EDIT)]);
    Route::get('/documents/{document}/edit', [DocumentController::class, 'edit'])->name('documents.edit')->middleware(Permission::perm(Permission::DOCUMENTS_ACCESS));
    Route::get('/documents/{document}/preview', [DocumentController::class, 'preview'])->name('documents.preview')->middleware(Permission::perm(Permission::DOCUMENTS_ACCESS));
    Route::put('/documents/{document}', [DocumentController::class, 'update'])->name('documents.update')->middleware([Permission::perm(Permission::DOCUMENTS_ACCESS), Permission::perm(Permission::DOCUMENTS_EDIT)]);
    Route::post('/documents/{document}/copy', [DocumentController::class, 'copy'])->name('documents.copy')->middleware([Permission::perm(Permission::DOCUMENTS_ACCESS), Permission::perm(Permission::DOCUMENTS_CREATE)]);
    Route::patch('/documents/{document}/position', [DocumentController::class, 'adjustPosition'])->name('documents.position')->middleware([Permission::perm(Permission::DOCUMENTS_ACCESS), Permission::perm(Permission::DOCUMENTS_EDIT)]);
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy')->middleware([Permission::perm(Permission::DOCUMENTS_ACCESS), Permission::perm(Permission::DOCUMENTS_DELETE)]);

    Route::get('/documents/{document}/revisions', [DocumentController::class, 'revisions'])->name('documents.revisions')->middleware(Permission::perm(Permission::DOCUMENTS_ACCESS));
    Route::get('/documents/{document}/revisions/{revision}', [DocumentController::class, 'revisionView'])->name('documents.revisions.show')->middleware(Permission::perm(Permission::DOCUMENTS_ACCESS));
    Route::post('/documents/{document}/revisions/{revision}/restore', [DocumentController::class, 'revisionRestore'])->name('documents.revisions.restore')->middleware([Permission::perm(Permission::DOCUMENTS_ACCESS), Permission::perm(Permission::DOCUMENTS_EDIT)]);
    Route::delete('/documents/{document}/revisions/{revision}', [DocumentController::class, 'revisionDelete'])->name('documents.revisions.destroy')->middleware([Permission::perm(Permission::DOCUMENTS_ACCESS), Permission::perm(Permission::DOCUMENTS_EDIT)]);
    Route::delete('/documents/{document}/revisions', [DocumentController::class, 'revisionDeleteAll'])->name('documents.revisions.destroyAll')->middleware([Permission::perm(Permission::DOCUMENTS_ACCESS), Permission::perm(Permission::DOCUMENTS_EDIT)]);

    Route::get('/rubrics', [RubricController::class, 'index'])->name('rubrics.index')->middleware(Permission::perm(Permission::RUBRICS_ACCESS));
    Route::post('/rubrics', [RubricController::class, 'store'])->name('rubrics.store')->middleware([Permission::perm(Permission::RUBRICS_ACCESS), Permission::perm(Permission::RUBRICS_CREATE)]);
    Route::patch('/rubrics/{rubric}', [RubricController::class, 'update'])->name('rubrics.update')->middleware([Permission::perm(Permission::RUBRICS_ACCESS), Permission::perm(Permission::RUBRICS_EDIT)]);
    Route::post('/rubrics/save-all', [RubricController::class, 'saveAll'])->name('rubrics.save-all')->middleware([Permission::perm(Permission::RUBRICS_ACCESS), Permission::perm(Permission::RUBRICS_EDIT)]);
    Route::post('/rubrics/order', [RubricController::class, 'updateOrder'])->name('rubrics.order')->middleware([Permission::perm(Permission::RUBRICS_ACCESS), Permission::perm(Permission::RUBRICS_EDIT)]);
    Route::get('/rubrics/{rubric}/docs-count', [RubricController::class, 'docsCount'])->name('rubrics.docs-count')->middleware(Permission::perm(Permission::RUBRICS_ACCESS));
    Route::post('/rubrics/{rubric}/copy', [RubricController::class, 'copy'])->name('rubrics.copy')->middleware([Permission::perm(Permission::RUBRICS_ACCESS), Permission::perm(Permission::RUBRICS_CREATE)]);
    Route::patch('/rubrics/{rubric}/seo', [RubricController::class, 'updateSeo'])->name('rubrics.update-seo')->middleware([Permission::perm(Permission::RUBRICS_ACCESS), Permission::perm(Permission::RUBRICS_EDIT)]);
    Route::patch('/rubrics/{rubric}/rss', [RubricController::class, 'updateRss'])->name('rubrics.update-rss')->middleware([Permission::perm(Permission::RUBRICS_ACCESS), Permission::perm(Permission::RUBRICS_EDIT)]);
    Route::patch('/rubrics/{rubric}/api', [RubricController::class, 'updateApi'])->name('rubrics.update-api')->middleware([Permission::perm(Permission::RUBRICS_ACCESS), Permission::perm(Permission::RUBRICS_EDIT)]);
    Route::get('/rubrics/{rubric}/fields-meta', [RubricController::class, 'fieldsMeta'])->name('rubrics.fields-meta')->middleware(Permission::perm(Permission::RUBRICS_ACCESS));
    Route::delete('/rubrics/{rubric}', [RubricController::class, 'destroy'])->name('rubrics.destroy')->middleware([Permission::perm(Permission::RUBRICS_ACCESS), Permission::perm(Permission::RUBRICS_DELETE)]);

    Route::get('/rubrics/{rubric}/fields', [RubricFieldController::class, 'show'])->name('rubrics.fields')->middleware(Permission::perm(Permission::RUBRICS_ACCESS));
    Route::post('/rubrics/{rubric}/fields', [RubricFieldController::class, 'store'])->name('rubrics.fields.store')->middleware([Permission::perm(Permission::RUBRICS_ACCESS), Permission::perm(Permission::RUBRICS_EDIT)]);
    Route::put('/rubrics/{rubric}/fields/{field}', [RubricFieldController::class, 'update'])->name('rubrics.fields.update')->middleware([Permission::perm(Permission::RUBRICS_ACCESS), Permission::perm(Permission::RUBRICS_EDIT)]);
    Route::patch('/rubrics/{rubric}/fields/{field}/alias', [RubricFieldController::class, 'updateAlias'])->name('rubrics.fields.alias')->middleware([Permission::perm(Permission::RUBRICS_ACCESS), Permission::perm(Permission::RUBRICS_EDIT)]);
    Route::patch('/rubrics/{rubric}/fields/{field}/in-api', [RubricFieldController::class, 'toggleInApi'])->name('rubrics.fields.in-api')->middleware([Permission::perm(Permission::RUBRICS_ACCESS), Permission::perm(Permission::RUBRICS_EDIT)]);
    Route::patch('/rubrics/{rubric}/fields/{field}/config', [RubricFieldController::class, 'updateConfig'])->name('rubrics.fields.config')->middleware([Permission::perm(Permission::RUBRICS_ACCESS), Permission::perm(Permission::RUBRICS_EDIT)]);
    Route::post('/rubrics/{rubric}/fields/reorder', [RubricFieldController::class, 'reorder'])->name('rubrics.fields.reorder')->middleware([Permission::perm(Permission::RUBRICS_ACCESS), Permission::perm(Permission::RUBRICS_EDIT)]);
    Route::delete('/rubrics/{rubric}/fields/{field}', [RubricFieldController::class, 'destroy'])->name('rubrics.fields.destroy')->middleware([Permission::perm(Permission::RUBRICS_ACCESS), Permission::perm(Permission::RUBRICS_DELETE)]);

    Route::get('/rubrics/{rubric}/fields/{field}/subfields', [RubricSubfieldController::class, 'show'])->name('rubrics.subfields')->middleware(Permission::perm(Permission::RUBRICS_ACCESS));
    Route::post('/rubrics/{rubric}/fields/{field}/subfields', [RubricSubfieldController::class, 'store'])->name('rubrics.subfields.store')->middleware([Permission::perm(Permission::RUBRICS_ACCESS), Permission::perm(Permission::RUBRICS_EDIT)]);
    Route::put('/rubrics/{rubric}/fields/{field}/subfields/{idx}', [RubricSubfieldController::class, 'update'])->name('rubrics.subfields.update')->middleware([Permission::perm(Permission::RUBRICS_ACCESS), Permission::perm(Permission::RUBRICS_EDIT)])->whereNumber('idx');
    Route::patch('/rubrics/{rubric}/fields/{field}/subfields/{idx}/alias', [RubricSubfieldController::class, 'updateAlias'])->name('rubrics.subfields.alias')->middleware([Permission::perm(Permission::RUBRICS_ACCESS), Permission::perm(Permission::RUBRICS_EDIT)])->whereNumber('idx');
    Route::post('/rubrics/{rubric}/fields/{field}/subfields/reorder', [RubricSubfieldController::class, 'reorder'])->name('rubrics.subfields.reorder')->middleware([Permission::perm(Permission::RUBRICS_ACCESS), Permission::perm(Permission::RUBRICS_EDIT)]);
    Route::delete('/rubrics/{rubric}/fields/{field}/subfields/{idx}', [RubricSubfieldController::class, 'destroy'])->name('rubrics.subfields.destroy')->middleware([Permission::perm(Permission::RUBRICS_ACCESS), Permission::perm(Permission::RUBRICS_DELETE)])->whereNumber('idx');

    Route::get('/rubrics/{rubric}/template', [RubricTemplateController::class, 'edit'])->name('rubrics.template')->middleware(Permission::perm(Permission::RUBRICS_ACCESS));
    Route::put('/rubrics/{rubric}/template', [RubricTemplateController::class, 'update'])->name('rubrics.template.update')->middleware([Permission::perm(Permission::RUBRICS_ACCESS), Permission::perm(Permission::RUBRICS_EDIT)]);

    Route::get('/rubrics/{rubric}/permissions', [RubricPermissionController::class, 'edit'])->name('rubrics.permissions')->middleware([Permission::perm(Permission::RUBRICS_ACCESS), Permission::perm(Permission::RUBRICS_PERMISSIONS)]);
    Route::put('/rubrics/{rubric}/permissions', [RubricPermissionController::class, 'update'])->name('rubrics.permissions.update')->middleware([Permission::perm(Permission::RUBRICS_ACCESS), Permission::perm(Permission::RUBRICS_PERMISSIONS)]);

    Route::get('/blocks', [BlockController::class, 'index'])->name('blocks.index')->middleware(Permission::perm(Permission::BLOCKS_ACCESS));
    Route::get('/blocks/create', [BlockController::class, 'create'])->name('blocks.create')->middleware([Permission::perm(Permission::BLOCKS_ACCESS), Permission::perm(Permission::BLOCKS_CREATE)]);
    Route::post('/blocks', [BlockController::class, 'store'])->name('blocks.store')->middleware([Permission::perm(Permission::BLOCKS_ACCESS), Permission::perm(Permission::BLOCKS_CREATE)]);
    Route::get('/blocks/{block}/edit', [BlockController::class, 'edit'])->name('blocks.edit')->middleware(Permission::perm(Permission::BLOCKS_ACCESS));
    Route::put('/blocks/{block}', [BlockController::class, 'update'])->name('blocks.update')->middleware([Permission::perm(Permission::BLOCKS_ACCESS), Permission::perm(Permission::BLOCKS_EDIT)]);
    Route::post('/blocks/{block}/copy', [BlockController::class, 'copy'])->name('blocks.copy')->middleware([Permission::perm(Permission::BLOCKS_ACCESS), Permission::perm(Permission::BLOCKS_CREATE)]);
    Route::delete('/blocks/{block}', [BlockController::class, 'destroy'])->name('blocks.destroy')->middleware([Permission::perm(Permission::BLOCKS_ACCESS), Permission::perm(Permission::BLOCKS_DELETE)]);

    Route::post('/block-groups', [BlockGroupController::class, 'store'])->name('block-groups.store')->middleware([Permission::perm(Permission::BLOCKS_ACCESS), Permission::perm(Permission::BLOCKS_GROUPS)]);
    Route::put('/block-groups/{group}', [BlockGroupController::class, 'update'])->name('block-groups.update')->middleware([Permission::perm(Permission::BLOCKS_ACCESS), Permission::perm(Permission::BLOCKS_GROUPS)]);
    Route::post('/block-groups/reorder', [BlockGroupController::class, 'reorder'])->name('block-groups.reorder')->middleware([Permission::perm(Permission::BLOCKS_ACCESS), Permission::perm(Permission::BLOCKS_GROUPS)]);
    Route::delete('/block-groups/{group}', [BlockGroupController::class, 'destroy'])->name('block-groups.destroy')->middleware([Permission::perm(Permission::BLOCKS_ACCESS), Permission::perm(Permission::BLOCKS_GROUPS)]);

    Route::get('/layouts', [LayoutController::class, 'index'])->name('layouts.index')->middleware(Permission::perm(Permission::LAYOUTS_ACCESS));
    Route::post('/layouts', [LayoutController::class, 'store'])->name('layouts.store')->middleware([Permission::perm(Permission::LAYOUTS_ACCESS), Permission::perm(Permission::LAYOUTS_CREATE)]);
    Route::get('/layouts/tags', [LayoutController::class, 'tags'])->name('layouts.tags')->middleware(Permission::perm(Permission::LAYOUTS_ACCESS));
    Route::get('/layouts/{layout}/edit', [LayoutController::class, 'edit'])->name('layouts.edit')->middleware(Permission::perm(Permission::LAYOUTS_ACCESS));
    Route::put('/layouts/{layout}', [LayoutController::class, 'update'])->name('layouts.update')->middleware([Permission::perm(Permission::LAYOUTS_ACCESS), Permission::perm(Permission::LAYOUTS_EDIT)]);
    Route::post('/layouts/{layout}/copy', [LayoutController::class, 'copy'])->name('layouts.copy')->middleware([Permission::perm(Permission::LAYOUTS_ACCESS), Permission::perm(Permission::LAYOUTS_CREATE)]);
    Route::delete('/layouts/{layout}', [LayoutController::class, 'destroy'])->name('layouts.destroy')->middleware([Permission::perm(Permission::LAYOUTS_ACCESS), Permission::perm(Permission::LAYOUTS_DELETE)]);

    Route::post('/layouts/assets/{type}', [AssetFileController::class, 'create'])->name('layouts.asset.create')->middleware([Permission::perm(Permission::LAYOUTS_ACCESS), Permission::perm(Permission::LAYOUTS_FILES)]);
    Route::post('/layouts/assets/{type}/upload', [AssetFileController::class, 'upload'])->name('layouts.asset.upload')->middleware([Permission::perm(Permission::LAYOUTS_ACCESS), Permission::perm(Permission::LAYOUTS_FILES)]);
    Route::get('/layouts/assets/{type}/{file}', [AssetFileController::class, 'edit'])->name('layouts.asset.edit')->where('file', '.+')->middleware(Permission::perm(Permission::LAYOUTS_ACCESS));
    Route::post('/layouts/assets/{type}/{file}', [AssetFileController::class, 'update'])->name('layouts.asset.update')->where('file', '.+')->middleware([Permission::perm(Permission::LAYOUTS_ACCESS), Permission::perm(Permission::LAYOUTS_FILES)]);
    Route::delete('/layouts/assets/{type}/{file}', [AssetFileController::class, 'destroy'])->name('layouts.asset.destroy')->where('file', '.+')->middleware([Permission::perm(Permission::LAYOUTS_ACCESS), Permission::perm(Permission::LAYOUTS_FILES)]);

    Route::post('/upload/image', [UploadController::class, 'image'])->name('upload.image');
    Route::delete('/upload/image', [UploadController::class, 'destroyImage'])->name('upload.image.destroy');
    Route::post('/upload/file', [UploadController::class, 'file'])->name('upload.file');

    Route::get('/navigations/doc-search', [NavigationController::class, 'docSearch'])->name('navigations.doc-search')->middleware(Permission::perm(Permission::NAVIGATIONS_ACCESS));
    Route::get('/navigations', [NavigationController::class, 'index'])->name('navigations.index')->middleware(Permission::perm(Permission::NAVIGATIONS_ACCESS));
    Route::post('/navigations', [NavigationController::class, 'store'])->name('navigations.store')->middleware([Permission::perm(Permission::NAVIGATIONS_ACCESS), Permission::perm(Permission::NAVIGATIONS_CREATE)]);
    Route::post('/navigations/{navigation}/copy', [NavigationController::class, 'copy'])->name('navigations.copy')->middleware([Permission::perm(Permission::NAVIGATIONS_ACCESS), Permission::perm(Permission::NAVIGATIONS_CREATE)]);
    Route::delete('/navigations/{navigation}', [NavigationController::class, 'destroy'])->name('navigations.destroy')->middleware([Permission::perm(Permission::NAVIGATIONS_ACCESS), Permission::perm(Permission::NAVIGATIONS_DELETE)]);

    Route::get('/navigations/{navigation}/template', [NavigationTemplateController::class, 'edit'])->name('navigations.template')->middleware(Permission::perm(Permission::NAVIGATIONS_ACCESS));
    Route::put('/navigations/{navigation}/template', [NavigationTemplateController::class, 'update'])->name('navigations.template.update')->middleware([Permission::perm(Permission::NAVIGATIONS_ACCESS), Permission::perm(Permission::NAVIGATIONS_EDIT)]);

    Route::get('/navigations/{navigation}/items', [NavigationItemController::class, 'index'])->name('navigations.items')->middleware(Permission::perm(Permission::NAVIGATIONS_ACCESS));
    Route::post('/navigations/{navigation}/items', [NavigationItemController::class, 'store'])->name('navigations.items.store')->middleware([Permission::perm(Permission::NAVIGATIONS_ACCESS), Permission::perm(Permission::NAVIGATIONS_EDIT)]);
    Route::put('/navigations/{navigation}/items/{item}', [NavigationItemController::class, 'update'])->name('navigations.items.update')->middleware([Permission::perm(Permission::NAVIGATIONS_ACCESS), Permission::perm(Permission::NAVIGATIONS_EDIT)]);
    Route::patch('/navigations/{navigation}/items/{item}/toggle', [NavigationItemController::class, 'toggleStatus'])->name('navigations.items.toggle')->middleware([Permission::perm(Permission::NAVIGATIONS_ACCESS), Permission::perm(Permission::NAVIGATIONS_EDIT)]);
    Route::post('/navigations/{navigation}/items/reorder', [NavigationItemController::class, 'reorder'])->name('navigations.items.reorder')->middleware([Permission::perm(Permission::NAVIGATIONS_ACCESS), Permission::perm(Permission::NAVIGATIONS_EDIT)]);
    Route::delete('/navigations/{navigation}/items/{item}', [NavigationItemController::class, 'destroy'])->name('navigations.items.destroy')->middleware([Permission::perm(Permission::NAVIGATIONS_ACCESS), Permission::perm(Permission::NAVIGATIONS_DELETE)]);

    Route::get('/requests', [DocsRequestController::class, 'index'])->name('requests.index')->middleware(Permission::perm(Permission::REQUESTS_ACCESS));
    Route::post('/requests', [DocsRequestController::class, 'store'])->name('requests.store')->middleware([Permission::perm(Permission::REQUESTS_ACCESS), Permission::perm(Permission::REQUESTS_CREATE)]);
    Route::get('/requests/{docsRequest}/edit', [DocsRequestController::class, 'edit'])->name('requests.edit')->middleware(Permission::perm(Permission::REQUESTS_ACCESS));
    Route::put('/requests/{docsRequest}', [DocsRequestController::class, 'update'])->name('requests.update')->middleware([Permission::perm(Permission::REQUESTS_ACCESS), Permission::perm(Permission::REQUESTS_EDIT)]);
    Route::post('/requests/{docsRequest}/copy', [DocsRequestController::class, 'copy'])->name('requests.copy')->middleware([Permission::perm(Permission::REQUESTS_ACCESS), Permission::perm(Permission::REQUESTS_CREATE)]);
    Route::delete('/requests/{docsRequest}', [DocsRequestController::class, 'destroy'])->name('requests.destroy')->middleware([Permission::perm(Permission::REQUESTS_ACCESS), Permission::perm(Permission::REQUESTS_DELETE)]);

    Route::get('/navigation', $placeholder)->name('navigation.index');
    Route::get('/fields', $placeholder)->name('fields.index');

    Route::get('/modules', [ModuleController::class, 'index'])->name('modules.index')->middleware(Permission::perm(Permission::MODULES_LIST));
    Route::post('/modules/install', [ModuleController::class, 'install'])->name('modules.install')->middleware(Permission::perm(Permission::MODULES_INSTALL));
    Route::post('/modules/uninstall', [ModuleController::class, 'uninstall'])->name('modules.uninstall')->middleware(Permission::perm(Permission::MODULES_INSTALL));
    Route::post('/modules/reinstall', [ModuleController::class, 'reinstall'])->name('modules.reinstall')->middleware(Permission::perm(Permission::MODULES_INSTALL));
    Route::post('/modules/toggle', [ModuleController::class, 'toggle'])->name('modules.toggle')->middleware(Permission::perm(Permission::MODULES_USE));
    Route::post('/modules/upload', [ModuleController::class, 'upload'])->name('modules.upload')->middleware(Permission::perm(Permission::MODULES_INSTALL));
    Route::post('/modules/update', [ModuleController::class, 'update'])->name('modules.update')->middleware(Permission::perm(Permission::MODULES_INSTALL));
    Route::get('/modules/check-updates', [ModuleController::class, 'checkUpdates'])->name('modules.check-updates')->middleware(Permission::perm(Permission::MODULES_LIST));

    Route::post('/modules/catalog/install', [ModuleController::class, 'catalogInstall'])->name('modules.catalog.install')->middleware(Permission::perm(Permission::MODULES_INSTALL));
    Route::post('/modules/catalog/update', [ModuleController::class, 'catalogUpdate'])->name('modules.catalog.update')->middleware(Permission::perm(Permission::MODULES_INSTALL));
    Route::get('/modules/catalog/updates', [ModuleController::class, 'checkCatalogUpdates'])->name('modules.catalog.updates')->middleware(Permission::perm(Permission::MODULES_LIST));

    Route::get('/modules/{sys}/templates', [ModuleTemplateController::class, 'index'])->name('modules.templates')->middleware(Permission::perm(Permission::MODULES_USE))->where('sys', '[a-z][a-z0-9_]*');
    Route::get('/modules/{sys}/templates/{view}', [ModuleTemplateController::class, 'show'])->name('modules.templates.show')->middleware(Permission::perm(Permission::MODULES_USE))->where(['sys' => '[a-z][a-z0-9_]*', 'view' => '[A-Za-z0-9_-]+']);
    Route::post('/modules/{sys}/templates/{view}', [ModuleTemplateController::class, 'save'])->name('modules.templates.save')->middleware(Permission::perm(Permission::MODULES_USE))->where(['sys' => '[a-z][a-z0-9_]*', 'view' => '[A-Za-z0-9_-]+']);
    Route::delete('/modules/{sys}/templates/{view}', [ModuleTemplateController::class, 'reset'])->name('modules.templates.reset')->middleware(Permission::perm(Permission::MODULES_USE))->where(['sys' => '[a-z][a-z0-9_]*', 'view' => '[A-Za-z0-9_-]+']);

    Route::any('/modules/{sys_name}/{path?}', [ModuleController::class, 'adminDispatch'])
         ->where(['sys_name' => '[a-z_][a-z0-9_]*', 'path' => '.*'])
         ->name('modules.dispatch')
         ->middleware(Permission::perm(Permission::MODULES_USE));

    Route::get('/api-tokens', [ApiTokenController::class, 'index'])->name('api-tokens.index')->middleware([Permission::perm(Permission::API_TOKENS_ACCESS), Permission::perm(Permission::API_TOKENS_LIST)]);
    Route::get('/api-tokens/create', [ApiTokenController::class, 'create'])->name('api-tokens.create')->middleware([Permission::perm(Permission::API_TOKENS_ACCESS), Permission::perm(Permission::API_TOKENS_CREATE)]);
    Route::post('/api-tokens', [ApiTokenController::class, 'store'])->name('api-tokens.store')->middleware([Permission::perm(Permission::API_TOKENS_ACCESS), Permission::perm(Permission::API_TOKENS_CREATE)]);
    Route::post('/api-tokens/bulk', [ApiTokenController::class, 'bulk'])->name('api-tokens.bulk')->middleware(Permission::perm(Permission::API_TOKENS_ACCESS));
    Route::get('/api-tokens/{apiToken}/edit', [ApiTokenController::class, 'edit'])->name('api-tokens.edit')->middleware([Permission::perm(Permission::API_TOKENS_ACCESS), Permission::perm(Permission::API_TOKENS_LIST)]);
    Route::put('/api-tokens/{apiToken}', [ApiTokenController::class, 'update'])->name('api-tokens.update')->middleware([Permission::perm(Permission::API_TOKENS_ACCESS), Permission::perm(Permission::API_TOKENS_EDIT)]);
    Route::post('/api-tokens/{apiToken}/regenerate', [ApiTokenController::class, 'regenerate'])->name('api-tokens.regenerate')->middleware([Permission::perm(Permission::API_TOKENS_ACCESS), Permission::perm(Permission::API_TOKENS_EDIT)]);
    Route::delete('/api-tokens/{apiToken}', [ApiTokenController::class, 'destroy'])->name('api-tokens.destroy')->middleware([Permission::perm(Permission::API_TOKENS_ACCESS), Permission::perm(Permission::API_TOKENS_DELETE)]);

    Route::get('/redirects', [RedirectController::class, 'index'])->name('redirects.index')->middleware([Permission::perm(Permission::REDIRECTS_ACCESS), Permission::perm(Permission::REDIRECTS_LIST)]);
    Route::get('/redirects/create', [RedirectController::class, 'create'])->name('redirects.create')->middleware([Permission::perm(Permission::REDIRECTS_ACCESS), Permission::perm(Permission::REDIRECTS_CREATE)]);
    Route::post('/redirects', [RedirectController::class, 'store'])->name('redirects.store')->middleware([Permission::perm(Permission::REDIRECTS_ACCESS), Permission::perm(Permission::REDIRECTS_CREATE)]);
    Route::post('/redirects/inspect', [RedirectController::class, 'inspect'])->name('redirects.inspect')->middleware([Permission::perm(Permission::REDIRECTS_ACCESS), Permission::perm(Permission::REDIRECTS_LIST)]);
    Route::post('/redirects/bulk', [RedirectController::class, 'bulk'])->name('redirects.bulk')->middleware(Permission::perm(Permission::REDIRECTS_ACCESS));
    Route::get('/redirects/misses', [RedirectController::class, 'misses'])->name('redirects.misses')->middleware([Permission::perm(Permission::REDIRECTS_ACCESS), Permission::perm(Permission::REDIRECTS_MISSES_VIEW)]);
    Route::delete('/redirects/misses/clear', [RedirectController::class, 'clearMisses'])->name('redirects.misses.clear')->middleware([Permission::perm(Permission::REDIRECTS_ACCESS), Permission::perm(Permission::REDIRECTS_MISSES_VIEW), Permission::perm(Permission::REDIRECTS_DELETE)]);
    Route::delete('/redirects/misses/{miss}', [RedirectController::class, 'destroyMiss'])->name('redirects.misses.destroy')->middleware([Permission::perm(Permission::REDIRECTS_ACCESS), Permission::perm(Permission::REDIRECTS_MISSES_VIEW), Permission::perm(Permission::REDIRECTS_DELETE)]);
    Route::get('/redirects/{redirect}/edit', [RedirectController::class, 'edit'])->name('redirects.edit')->middleware([Permission::perm(Permission::REDIRECTS_ACCESS), Permission::perm(Permission::REDIRECTS_LIST)]);
    Route::put('/redirects/{redirect}', [RedirectController::class, 'update'])->name('redirects.update')->middleware([Permission::perm(Permission::REDIRECTS_ACCESS), Permission::perm(Permission::REDIRECTS_EDIT)]);
    Route::delete('/redirects/{redirect}', [RedirectController::class, 'destroy'])->name('redirects.destroy')->middleware([Permission::perm(Permission::REDIRECTS_ACCESS), Permission::perm(Permission::REDIRECTS_DELETE)]);

    Route::get('/users/groups', [UserGroupController::class, 'index'])->name('user-groups.index')->middleware(Permission::perm(Permission::GROUPS_ACCESS));
    Route::post('/users/groups', [UserGroupController::class, 'store'])->name('user-groups.store')->middleware([Permission::perm(Permission::GROUPS_ACCESS), Permission::perm(Permission::GROUPS_CREATE)]);
    Route::get('/users/groups/{userGroup}/edit', [UserGroupController::class, 'edit'])->name('user-groups.edit')->middleware(Permission::perm(Permission::GROUPS_ACCESS));
    Route::put('/users/groups/{userGroup}/info', [UserGroupController::class, 'updateInfo'])->name('user-groups.update-info')->middleware([Permission::perm(Permission::GROUPS_ACCESS), Permission::perm(Permission::GROUPS_EDIT)]);
    Route::put('/users/groups/{userGroup}/permissions', [UserGroupController::class, 'updatePermissions'])->name('user-groups.update-permissions')->middleware([Permission::perm(Permission::GROUPS_ACCESS), Permission::perm(Permission::GROUPS_EDIT)]);
    Route::post('/users/groups/{userGroup}/duplicate', [UserGroupController::class, 'duplicate'])->name('user-groups.duplicate')->middleware([Permission::perm(Permission::GROUPS_ACCESS), Permission::perm(Permission::GROUPS_CREATE)]);
    Route::delete('/users/groups/{userGroup}', [UserGroupController::class, 'destroy'])->name('user-groups.destroy')->middleware([Permission::perm(Permission::GROUPS_ACCESS), Permission::perm(Permission::GROUPS_DELETE)]);

    Route::get('/users', [UserController::class, 'index'])->name('users.index')->middleware(Permission::perm(Permission::USERS_ACCESS));
    Route::post('/users', [UserController::class, 'store'])->name('users.store')->middleware([Permission::perm(Permission::USERS_ACCESS), Permission::perm(Permission::USERS_CREATE)]);
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit')->middleware(Permission::perm(Permission::USERS_ACCESS));
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update')->middleware([Permission::perm(Permission::USERS_ACCESS), Permission::perm(Permission::USERS_EDIT)]);
    Route::patch('/users/{user}/group', [UserController::class, 'updateGroup'])->name('users.update-group')->middleware([Permission::perm(Permission::USERS_ACCESS), Permission::perm(Permission::USERS_GROUPS)]);
    Route::post('/users/{user}/send-password', [UserController::class, 'sendPassword'])->name('users.send-password')->middleware([Permission::perm(Permission::USERS_ACCESS), Permission::perm(Permission::USERS_EDIT)]);
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy')->middleware([Permission::perm(Permission::USERS_ACCESS), Permission::perm(Permission::USERS_DELETE)]);

    Route::get('/logs', [SystemLogController::class, 'index'])->name('logs.index')->middleware(Permission::perm(Permission::LOGS_ACCESS));
    Route::post('/logs/{type}/clear', [SystemLogController::class, 'clear'])->name('logs.clear')->middleware([Permission::perm(Permission::LOGS_ACCESS), Permission::perm(Permission::LOGS_CLEAR)]);
    Route::get('/logs/{type}/export', [SystemLogController::class, 'export'])->name('logs.export')->middleware([Permission::perm(Permission::LOGS_ACCESS), Permission::perm(Permission::LOGS_EXPORT)]);
    Route::get('/logs/framework/{filename}/download', [SystemLogController::class, 'downloadFrameworkLog'])->name('logs.framework.download')->middleware([Permission::perm(Permission::LOGS_ACCESS), Permission::perm(Permission::LOGS_EXPORT)]);
    Route::post('/logs/framework/{filename}/clear', [SystemLogController::class, 'clearFrameworkLog'])->name('logs.framework.clear')->middleware([Permission::perm(Permission::LOGS_ACCESS), Permission::perm(Permission::LOGS_CLEAR)]);

    Route::get('/database', [DatabaseController::class, 'index'])->name('database')->middleware(Permission::perm(Permission::DB_ACCESS));
    Route::get('/database/stats', [DatabaseController::class, 'stats'])->name('database.stats')->middleware(Permission::perm(Permission::DB_ACCESS));
    Route::get('/database/tables', [DatabaseController::class, 'tables'])->name('database.tables')->middleware(Permission::perm(Permission::DB_ACCESS));
    Route::post('/database/maintenance', [DatabaseController::class, 'maintenance'])->name('database.maintenance')->middleware([Permission::perm(Permission::DB_ACCESS), Permission::perm(Permission::DB_OPTIMIZE)]);
    Route::post('/database/backup', [DatabaseController::class, 'backup'])->name('database.backup')->middleware([Permission::perm(Permission::DB_ACCESS), Permission::perm(Permission::DB_BACKUP)]);
    Route::post('/database/restore-upload', [DatabaseController::class, 'restoreUpload'])->name('database.restore-upload')->middleware([Permission::perm(Permission::DB_ACCESS), Permission::perm(Permission::DB_RESTORE)]);
    Route::post('/database/restore/{filename}', [DatabaseController::class, 'restoreFromServer'])->name('database.restore-server')->middleware([Permission::perm(Permission::DB_ACCESS), Permission::perm(Permission::DB_RESTORE)]);
    Route::get('/database/download/{filename}', [DatabaseController::class, 'download'])->name('database.download')->middleware([Permission::perm(Permission::DB_ACCESS), Permission::perm(Permission::DB_BACKUP)]);
    Route::delete('/database/backup/{filename}', [DatabaseController::class, 'deleteBackup'])->name('database.backup.delete')->middleware([Permission::perm(Permission::DB_ACCESS), Permission::perm(Permission::DB_BACKUP)]);

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings')->middleware(Permission::perm(Permission::SETTINGS_ACCESS));
    Route::post('/settings/general', [SettingsController::class, 'saveGeneral'])->name('settings.save-general')->middleware([Permission::perm(Permission::SETTINGS_ACCESS), Permission::perm(Permission::SETTINGS_EDIT_GENERAL)]);
    Route::post('/settings/env', [SettingsController::class, 'saveEnv'])->name('settings.save-env')->middleware([Permission::perm(Permission::SETTINGS_ACCESS), Permission::perm(Permission::SETTINGS_EDIT_ENV)]);
    Route::post('/settings/email/test', [SettingsController::class, 'sendTestEmail'])->name('settings.email-test')->middleware([Permission::perm(Permission::SETTINGS_ACCESS), Permission::perm(Permission::SETTINGS_EDIT_ENV)]);
    Route::post('/settings/seo', [SettingsController::class, 'saveSeo'])->name('settings.save-seo')->middleware([Permission::perm(Permission::SETTINGS_ACCESS), Permission::perm(Permission::SETTINGS_EDIT_SEO)]);
    Route::get('/settings/sitemap-preview', [SettingsController::class, 'sitemapPreview'])->name('settings.sitemap-preview')->middleware([Permission::perm(Permission::SETTINGS_ACCESS), Permission::perm(Permission::SETTINGS_TAB_SEO)]);
    Route::post('/settings/sitemap-flush', [SettingsController::class, 'sitemapFlush'])->name('settings.sitemap-flush')->middleware([Permission::perm(Permission::SETTINGS_ACCESS), Permission::perm(Permission::SETTINGS_EDIT_SEO)]);
    Route::post('/settings/maps', [SettingsController::class, 'saveMaps'])->name('settings.save-maps')->middleware([Permission::perm(Permission::SETTINGS_ACCESS), Permission::perm(Permission::SETTINGS_EDIT_MAPS)]);
    Route::post('/settings/maps/check-key', [SettingsController::class, 'checkMapsKey'])->name('settings.maps-check-key')->middleware([Permission::perm(Permission::SETTINGS_ACCESS), Permission::perm(Permission::SETTINGS_TAB_MAPS)]);
    Route::get('/settings/cache-stats', [SettingsController::class, 'cacheStats'])->name('settings.cache-stats')->middleware(Permission::perm(Permission::SETTINGS_ACCESS));
    Route::post('/settings/optimize', [SettingsController::class, 'optimize'])->name('settings.optimize')->middleware([Permission::perm(Permission::SETTINGS_ACCESS), Permission::perm(Permission::SETTINGS_EDIT_ENV)]);
    Route::post('/settings/optimize-clear', [SettingsController::class, 'optimizeClear'])->name('settings.optimize-clear')->middleware([Permission::perm(Permission::SETTINGS_ACCESS), Permission::perm(Permission::SETTINGS_EDIT_ENV)]);
    Route::get('/settings/composer-outdated', [SettingsController::class, 'composerOutdated'])->name('settings.composer-outdated')->middleware([Permission::perm(Permission::SETTINGS_ACCESS), Permission::perm(Permission::SETTINGS_TAB_ENV)]);
    Route::get('/settings/redis-check', [SettingsController::class, 'redisCheck'])->name('settings.redis-check')->middleware([Permission::perm(Permission::SETTINGS_ACCESS), Permission::perm(Permission::SETTINGS_TAB_ENV)]);
    Route::post('/settings/log-notify', [SettingsController::class, 'saveLogNotify'])->name('settings.log-notify')->middleware([Permission::perm(Permission::SETTINGS_ACCESS), Permission::perm(Permission::SETTINGS_EDIT_ENV)]);
    Route::post('/settings/log-notify/test', [SettingsController::class, 'testLogNotify'])->name('settings.log-notify-test')->middleware([Permission::perm(Permission::SETTINGS_ACCESS), Permission::perm(Permission::SETTINGS_EDIT_ENV)]);
    Route::post('/settings/cache/clear', [SettingsController::class, 'clearCache'])->name('settings.cache-clear')->middleware([Permission::perm(Permission::SETTINGS_ACCESS), Permission::perm(Permission::SETTINGS_TAB_CACHE), Permission::perm(Permission::CACHE_CLEAR)]);
    Route::post('/settings/cache/clear-all', [SettingsController::class, 'clearAllCache'])->name('settings.cache-clear-all')->middleware([Permission::perm(Permission::SETTINGS_ACCESS), Permission::perm(Permission::SETTINGS_TAB_CACHE), Permission::perm(Permission::CACHE_CLEAR)]);
    Route::post('/settings/cache/clear-sessions', [SettingsController::class, 'clearSessions'])->name('settings.cache-clear-sessions')->middleware([Permission::perm(Permission::SETTINGS_ACCESS), Permission::perm(Permission::SETTINGS_TAB_CACHE), Permission::perm(Permission::CACHE_CLEAR)]);
    Route::post('/settings/public-cache', [SettingsController::class, 'savePublicCache'])->name('settings.save-public-cache')->middleware([Permission::perm(Permission::SETTINGS_ACCESS), Permission::perm(Permission::SETTINGS_TAB_CACHE), Permission::perm(Permission::CACHE_CLEAR)]);
    Route::post('/settings/public-cache/flush', [SettingsController::class, 'flushPublicCache'])->name('settings.public-cache-flush')->middleware([Permission::perm(Permission::SETTINGS_ACCESS), Permission::perm(Permission::SETTINGS_TAB_CACHE), Permission::perm(Permission::CACHE_CLEAR)]);
});

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap.index');
Route::get('/sitemap-{n}.xml', [SitemapController::class, 'chunk'])
     ->whereNumber('n')
     ->name('sitemap.chunk');

Route::get('/feed.xml', [RssController::class, 'forSite'])->name('rss.site');
Route::get('/{alias}/feed.xml', [RssController::class, 'forRubric'])
     ->where('alias', '[a-z0-9_-]+')
     ->name('rss.rubric');

try {
    $apiDomain = trim((string) \App\Models\Setting::getValue('api_domain', ''));
    $apiPrefix = trim((string) \App\Models\Setting::getValue('api_url_prefix', '/api/v1'), '/');
} catch (\Throwable) {
    $apiDomain = '';
    $apiPrefix = 'api/v1';
}

if ($apiPrefix === '') {
    $apiPrefix = 'api/v1';
}

$apiDocsGroup = Route::name('api.public.');

if ($apiDomain !== '') {
    $apiDocsGroup = $apiDocsGroup->domain($apiDomain);
}

if ($apiPrefix !== '') {
    $apiDocsGroup = $apiDocsGroup->prefix($apiPrefix);
}
$apiDocsGroup->group(function () {
    Route::get('/docs', [\App\Http\Controllers\Api\DocsController::class, 'show'])->name('docs');
});

$apiGroup = Route::middleware('api.token');

if ($apiDomain !== '') {
    $apiGroup = $apiGroup->domain($apiDomain);
}

if ($apiPrefix !== '') {
    $apiGroup = $apiGroup->prefix($apiPrefix);
}
$apiGroup->name('api.')->group(function () {
    Route::get('/rubrics', [ApiRubricController::class, 'index'])->name('rubrics.index');
    Route::get('/rubrics/{alias}', [ApiRubricController::class, 'show'])->name('rubrics.show')->where('alias', '[a-z0-9_-]+');
    Route::get('/rubrics/{alias}/documents', [ApiRubricController::class, 'documents'])->name('rubrics.documents')->where('alias', '[a-z0-9_-]+');
    Route::get('/rubrics/{alias}/documents/{docAlias}', [ApiRubricController::class, 'showDocument'])->name('rubrics.documents.show')
         ->where(['alias' => '[a-z0-9_-]+', 'docAlias' => '[a-z0-9_-]+']);
});

Route::get('/{path?}', [PublicController::class, 'show'])
    ->where('path', '.*')
    ->middleware([
        \App\Http\Middleware\HandleRedirects::class,
        \App\Http\Middleware\CachePublicResponse::class,
    ])
    ->name('public.show');
