<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration')->index();
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration')->index();
        });

        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestampTz('failed_at')->useCurrent();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestampTz('created_at')->nullable();
        });

        Schema::create('user_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_system')->default(false);
            $table->json('permissions')->nullable();
            $table->timestampsTz();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->unsignedBigInteger('group_id')->nullable()->index();
            $table->json('additional_groups')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampTz('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->timestampTz('last_seen_at')->nullable();
            $table->rememberToken();
            $table->timestampsTz();
        });

        Schema::create('layouts', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255)->unique();
            $table->longText('content')->default('');
            $table->timestampsTz();
        });

        Schema::create('rubrics', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255)->unique();
            $table->string('alias', 100)->nullable()->unique();
            $table->foreignId('layout_id')->nullable()->constrained('layouts')->nullOnDelete();
            $table->longText('template')->default('');
            $table->text('description')->nullable();
            $table->string('color', 20)->nullable();
            $table->integer('position')->default(0)->index();

            $table->boolean('sitemap_include')->default(true);
            $table->string('sitemap_changefreq', 20)->nullable();
            $table->decimal('sitemap_priority', 3, 1)->nullable();
            $table->string('sitemap_index_changefreq', 20)->nullable();
            $table->decimal('sitemap_index_priority', 3, 1)->nullable();

            $table->boolean('rss_enabled')->default(false);
            $table->string('rss_title', 255)->nullable();
            $table->text('rss_description')->nullable();
            $table->unsignedInteger('rss_limit')->nullable();

            $table->unsignedInteger('public_cache_ttl')->nullable();
            $table->boolean('public_cache_disabled')->default(false);

            $table->boolean('api_enabled')->default(false);
            $table->unsignedInteger('api_default_limit')->nullable();
            $table->unsignedInteger('api_max_limit')->nullable();
            $table->timestampsTz();
        });

        Schema::create('rubric_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rubric_id')->constrained('rubrics')->cascadeOnDelete();
            $table->string('alias', 50);
            $table->string('title', 255);
            $table->string('type', 50);
            $table->integer('position')->default(0);
            $table->text('default_value')->nullable();
            $table->text('description')->nullable();
            $table->jsonb('config')->nullable();
            $table->boolean('in_api')->default(false);
            $table->timestampsTz();

            $table->unique(['rubric_id', 'alias']);
            $table->index(['rubric_id', 'position']);
        });

        Schema::table('rubrics', function (Blueprint $table) {
            $table->foreignId('rss_description_field_id')
                  ->nullable()->constrained('rubric_fields')->nullOnDelete();
            $table->foreignId('rss_image_field_id')
                  ->nullable()->constrained('rubric_fields')->nullOnDelete();
            $table->foreignId('rss_category_field_id')
                  ->nullable()->constrained('rubric_fields')->nullOnDelete();
        });

        Schema::create('rubric_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rubric_id')->constrained('rubrics')->cascadeOnDelete();
            $table->foreignId('group_id')->constrained('user_groups')->cascadeOnDelete();
            $table->boolean('can_view')->default(false);
            $table->boolean('can_all')->default(false);
            $table->boolean('can_create_moderated')->default(false);
            $table->boolean('can_create')->default(false);
            $table->boolean('can_edit_own')->default(false);
            $table->boolean('can_edit_all')->default(false);
            $table->boolean('can_revisions')->default(false);
            $table->timestampsTz();

            $table->unique(['rubric_id', 'group_id']);
        });

        Schema::create('block_groups', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('position')->default(0)->index();
            $table->timestampsTz();
        });

        Schema::create('blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->nullable()->constrained('block_groups')->nullOnDelete();
            $table->string('title');
            $table->string('alias', 100)->unique();
            $table->text('description')->nullable();
            $table->longText('content')->nullable();
            $table->boolean('is_wysiwyg')->default(false);
            $table->unsignedInteger('position')->default(0)->index();
            $table->timestampsTz();
        });

        Schema::create('navigations', function (Blueprint $table) {
            $table->id();
            $table->string('title')->unique();
            $table->string('alias', 100)->unique();
            $table->jsonb('allowed_groups')->nullable();
            $table->longText('template_l1')->nullable();
            $table->longText('link_tpl_l1')->nullable();
            $table->longText('template_l2')->nullable();
            $table->longText('link_tpl_l2')->nullable();
            $table->longText('template_l3')->nullable();
            $table->longText('link_tpl_l3')->nullable();
            $table->unsignedInteger('position')->default(0)->index();
            $table->timestampsTz();
        });

        Schema::create('navigation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('navigation_id')->constrained('navigations')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->references('id')->on('navigation_items')->nullOnDelete();
            $table->string('title');
            $table->string('url', 500)->nullable();
            $table->string('target', 20)->default('_self');
            $table->string('css_class', 200)->nullable();
            $table->string('css_id', 100)->nullable();
            $table->string('css_style', 500)->nullable();
            $table->text('description')->nullable();
            $table->string('image', 500)->nullable();
            $table->string('icon', 500)->nullable();
            $table->text('extra_html')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->index(['navigation_id', 'parent_id', 'position']);
        });

        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('alias', 100)->unique();
            $table->text('description')->nullable();
            $table->jsonb('rubric_ids')->nullable();
            $table->string('sort_field', 100)->nullable();
            $table->string('sort_system', 50)->nullable();
            $table->enum('sort_order', ['asc', 'desc'])->default('desc');
            $table->string('fetch_mode', 20)->default('global');
            $table->unsignedInteger('limit')->nullable();
            $table->boolean('show_pagination')->default(false);
            $table->unsignedInteger('per_page')->nullable();
            $table->boolean('exclude_current')->default(false);
            $table->unsignedInteger('cache_time')->nullable();
            $table->jsonb('conditions')->nullable();
            $table->longText('template_main')->nullable();
            $table->longText('template_item')->nullable();
            $table->timestampsTz();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rubric_id')->constrained('rubrics')->onDelete('cascade');
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->string('alias')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->integer('position')->default(0);
            $table->unsignedInteger('views')->default(0);
            $table->foreignId('nav_item_id')->nullable()->constrained('navigation_items')->nullOnDelete();
            $table->string('breadcrumb_title')->nullable();
            $table->unsignedBigInteger('parent_doc_id')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_robots', 50)->default('index,follow');
            $table->string('sitemap_changefreq', 20)->nullable();
            $table->decimal('sitemap_priority', 3, 1)->nullable();
            $table->unsignedInteger('public_cache_ttl')->nullable();
            $table->boolean('public_cache_disabled')->default(false);
            $table->timestampTz('published_at')->nullable();
            $table->timestampTz('unpublished_at')->nullable();
            $table->timestampsTz();

            $table->foreign('parent_doc_id')->references('id')->on('documents')->nullOnDelete();
        });

        DB::statement('CREATE UNIQUE INDEX documents_rubric_alias_unique ON documents (rubric_id, alias) WHERE alias IS NOT NULL');
        DB::statement('CREATE UNIQUE INDEX documents_rubric_null_alias_unique ON documents (rubric_id) WHERE alias IS NULL');

        Schema::create('document_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->onDelete('cascade');
            $table->foreignId('field_id')->constrained('rubric_fields')->onDelete('cascade');
            $table->longText('value')->nullable();
        });

        Schema::create('document_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->jsonb('snapshot');
            $table->timestampTz('created_at')->useCurrent();
        });

        Schema::create('admin_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('user_name', 100)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('action');
            $table->string('action_type', 20)->nullable()->index();
            $table->string('object_type', 50)->nullable();
            $table->unsignedBigInteger('object_id')->nullable();
            $table->string('object_title')->nullable();
            $table->timestampTz('created_at')->useCurrent()->index();
        });

        Schema::create('error_logs_404', function (Blueprint $table) {
            $table->id();
            $table->string('ip', 45)->nullable();
            $table->string('url', 1000);
            $table->string('referer', 1000)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestampTz('created_at')->useCurrent()->index();
        });

        Schema::create('error_logs_db', function (Blueprint $table) {
            $table->id();
            $table->string('level', 20)->default('ERROR')->index();
            $table->text('message');
            $table->text('query')->nullable();
            $table->text('context')->nullable();
            $table->timestampTz('created_at')->useCurrent();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestampsTz();
        });

        Schema::create('modules', function (Blueprint $table) {
            $table->string('sys_name')->primary();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('version');
            $table->boolean('is_active')->default(true);
            $table->string('github')->nullable();
            $table->string('tag')->nullable();
            $table->boolean('has_admin_page')->default(false);
            $table->boolean('has_front')->default(false);
            $table->boolean('has_settings')->default(false);
            $table->jsonb('config')->nullable();
            $table->timestampTz('installed_at')->nullable();
            $table->timestampsTz();
        });

        Schema::create('rubric_alias_history', function (Blueprint $table) {
            $table->id();
            $table->string('old_alias', 100);
            $table->foreignId('rubric_id')->constrained('rubrics')->onDelete('cascade');
            $table->timestampTz('created_at')->useCurrent();

            $table->unique('old_alias');
        });

        Schema::create('document_alias_history', function (Blueprint $table) {
            $table->id();
            $table->string('old_alias');
            $table->unsignedBigInteger('old_rubric_id')->nullable();
            $table->foreignId('document_id')->constrained('documents')->onDelete('cascade');
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(['old_alias', 'old_rubric_id']);
        });

        Schema::create('media_files', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('kind', 16);
            $table->string('storage_path', 512);
            $table->string('original_name', 512);
            $table->string('mime', 128);
            $table->unsignedBigInteger('size');
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestampTz('created_at')->nullable();

            $table->index('kind');
            $table->index('uploaded_by');
        });

        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_url', 500);
            $table->string('to_url', 500);
            $table->smallInteger('type')->default(301);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_wildcard')->default(false);
            $table->integer('priority')->default(0);
            $table->boolean('preserve_query_string')->default(true);
            $table->timestampTz('expires_at')->nullable();
            $table->unsignedInteger('hits')->default(0);
            $table->timestampTz('last_hit_at')->nullable();
            $table->text('note')->nullable();
            $table->timestampsTz();

            $table->index('from_url');
            $table->index(['is_active', 'is_wildcard', 'priority']);
            $table->index('expires_at');
            $table->unique('from_url');
        });

        Schema::create('redirect_misses', function (Blueprint $table) {
            $table->id();
            $table->string('url', 500)->unique();
            $table->unsignedInteger('hits')->default(1);
            $table->timestampTz('first_seen_at')->useCurrent();
            $table->timestampTz('last_seen_at')->useCurrent();
            $table->string('last_referer', 500)->nullable();
        });

        Schema::create('api_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('token_hash', 64)->unique();
            $table->string('token_prefix', 24);
            $table->jsonb('allowed_rubrics')->nullable();
            $table->unsignedInteger('rate_limit_per_minute')->default(60);
            $table->boolean('is_active')->default(true);
            $table->timestampTz('expires_at')->nullable();
            $table->timestampTz('secret_rotated_at')->nullable();
            $table->timestampTz('last_used_at')->nullable();
            $table->string('last_used_ip', 45)->nullable();
            $table->unsignedBigInteger('hits')->default(0);
            $table->jsonb('metadata')->nullable();
            $table->timestampsTz();

            $table->index(['is_active', 'expires_at']);
            $table->index('token_prefix');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_tokens');
        Schema::dropIfExists('redirect_misses');
        Schema::dropIfExists('redirects');
        Schema::dropIfExists('media_files');
        Schema::dropIfExists('document_alias_history');
        Schema::dropIfExists('rubric_alias_history');
        Schema::dropIfExists('modules');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('error_logs_db');
        Schema::dropIfExists('error_logs_404');
        Schema::dropIfExists('admin_logs');
        Schema::dropIfExists('document_revisions');
        Schema::dropIfExists('document_fields');

        Schema::table('rubrics', function (Blueprint $table) {
            $table->dropForeign(['rss_description_field_id']);
            $table->dropForeign(['rss_image_field_id']);
            $table->dropForeign(['rss_category_field_id']);
            $table->dropColumn(['rss_description_field_id', 'rss_image_field_id', 'rss_category_field_id']);
        });

        DB::statement('DROP INDEX IF EXISTS documents_rubric_null_alias_unique');
        DB::statement('DROP INDEX IF EXISTS documents_rubric_alias_unique');

        Schema::dropIfExists('documents');
        Schema::dropIfExists('requests');
        Schema::dropIfExists('navigation_items');
        Schema::dropIfExists('navigations');
        Schema::dropIfExists('blocks');
        Schema::dropIfExists('block_groups');
        Schema::dropIfExists('rubric_permissions');
        Schema::dropIfExists('rubric_fields');
        Schema::dropIfExists('rubrics');
        Schema::dropIfExists('layouts');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('user_groups');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
    }
};
