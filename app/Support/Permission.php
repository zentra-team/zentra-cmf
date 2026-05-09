<?php

namespace App\Support;

final class Permission
{
    public const DOCUMENTS_ACCESS = 'documents.access';
    public const DOCUMENTS_LIST = 'documents.list';
    public const DOCUMENTS_CREATE = 'documents.create';
    public const DOCUMENTS_EDIT = 'documents.edit';
    public const DOCUMENTS_DELETE = 'documents.delete';

    public const RUBRICS_ACCESS = 'rubrics.access';
    public const RUBRICS_LIST = 'rubrics.list';
    public const RUBRICS_CREATE = 'rubrics.create';
    public const RUBRICS_EDIT = 'rubrics.edit';
    public const RUBRICS_DELETE = 'rubrics.delete';
    public const RUBRICS_PERMISSIONS = 'rubrics.permissions';

    public const REQUESTS_ACCESS = 'requests.access';
    public const REQUESTS_LIST = 'requests.list';
    public const REQUESTS_CREATE = 'requests.create';
    public const REQUESTS_EDIT = 'requests.edit';
    public const REQUESTS_DELETE = 'requests.delete';

    public const BLOCKS_ACCESS = 'blocks.access';
    public const BLOCKS_LIST = 'blocks.list';
    public const BLOCKS_CREATE = 'blocks.create';
    public const BLOCKS_EDIT = 'blocks.edit';
    public const BLOCKS_DELETE = 'blocks.delete';
    public const BLOCKS_GROUPS = 'blocks.groups';

    public const LAYOUTS_ACCESS = 'layouts.access';
    public const LAYOUTS_LIST = 'layouts.list';
    public const LAYOUTS_EDIT = 'layouts.edit';
    public const LAYOUTS_CREATE = 'layouts.create';
    public const LAYOUTS_DELETE = 'layouts.delete';
    public const LAYOUTS_FILES = 'layouts.files';

    public const NAVIGATIONS_ACCESS = 'navigations.access';
    public const NAVIGATIONS_LIST = 'navigations.list';
    public const NAVIGATIONS_CREATE = 'navigations.create';
    public const NAVIGATIONS_EDIT = 'navigations.edit';
    public const NAVIGATIONS_DELETE = 'navigations.delete';

    public const REDIRECTS_ACCESS = 'redirects.access';
    public const REDIRECTS_LIST = 'redirects.list';
    public const REDIRECTS_CREATE = 'redirects.create';
    public const REDIRECTS_EDIT = 'redirects.edit';
    public const REDIRECTS_DELETE = 'redirects.delete';
    public const REDIRECTS_MISSES_VIEW = 'redirects.misses_view';

    public const API_TOKENS_ACCESS = 'api_tokens.access';
    public const API_TOKENS_LIST = 'api_tokens.list';
    public const API_TOKENS_CREATE = 'api_tokens.create';
    public const API_TOKENS_EDIT = 'api_tokens.edit';
    public const API_TOKENS_DELETE = 'api_tokens.delete';

    public const USERS_ACCESS = 'users.access';
    public const USERS_LIST = 'users.list';
    public const USERS_CREATE = 'users.create';
    public const USERS_EDIT = 'users.edit';
    public const USERS_DELETE = 'users.delete';
    public const USERS_GROUPS = 'users.groups';

    public const GROUPS_ACCESS = 'groups.access';
    public const GROUPS_LIST = 'groups.list';
    public const GROUPS_CREATE = 'groups.create';
    public const GROUPS_EDIT = 'groups.edit';
    public const GROUPS_DELETE = 'groups.delete';

    public const MODULES_LIST = 'modules.list';
    public const MODULES_USE = 'modules.use';
    public const MODULES_INSTALL = 'modules.install';

    public const DB_ACCESS = 'db.access';
    public const DB_BACKUP = 'db.backup';
    public const DB_RESTORE = 'db.restore';
    public const DB_OPTIMIZE = 'db.optimize';

    public const LOGS_ACCESS = 'logs.access';
    public const LOGS_TAB_ADMIN = 'logs.tab.admin';
    public const LOGS_TAB_404 = 'logs.tab.404';
    public const LOGS_TAB_DB = 'logs.tab.db';
    public const LOGS_TAB_FRAMEWORK = 'logs.tab.framework';
    public const LOGS_EXPORT = 'logs.export';
    public const LOGS_CLEAR = 'logs.clear';

    public const SETTINGS_ACCESS = 'settings.access';
    public const SETTINGS_TAB_GENERAL = 'settings.tab.general';
    public const SETTINGS_TAB_ENV = 'settings.tab.env';
    public const SETTINGS_TAB_SEO = 'settings.tab.seo';
    public const SETTINGS_TAB_CACHE = 'settings.tab.cache';
    public const SETTINGS_TAB_MAPS = 'settings.tab.maps';
    public const SETTINGS_EDIT_GENERAL = 'settings.edit.general';
    public const SETTINGS_EDIT_ENV = 'settings.edit.env';
    public const SETTINGS_EDIT_SEO = 'settings.edit.seo';
    public const SETTINGS_EDIT_MAPS = 'settings.edit.maps';
    public const SETTINGS_MANAGE = 'settings.manage';

    public const CACHE_CLEAR = 'cache.clear';

    public static function perm(string $permission): string
    {
        return 'perm:' . $permission;
    }

    public static function uploadAllowingPermissions(): array
    {
        return [
            self::DOCUMENTS_CREATE,
            self::DOCUMENTS_EDIT,
            self::BLOCKS_CREATE,
            self::BLOCKS_EDIT,
            self::LAYOUTS_CREATE,
            self::LAYOUTS_EDIT,
            self::LAYOUTS_FILES,
            self::NAVIGATIONS_CREATE,
            self::NAVIGATIONS_EDIT,
            self::REQUESTS_CREATE,
            self::REQUESTS_EDIT,
            self::RUBRICS_EDIT,
            self::SETTINGS_EDIT_GENERAL,
            self::SETTINGS_EDIT_SEO,
        ];
    }
}
