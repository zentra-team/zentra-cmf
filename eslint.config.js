import js from '@eslint/js';
import prettierConfig from 'eslint-config-prettier';
import globals from 'globals';

export default [
    js.configs.recommended,
    prettierConfig,
    {
        files: ['resources/js/**/*.js', 'public/assets/js/**/*.js'],
        languageOptions: {
            ecmaVersion: 2022,
            sourceType: 'script',
            globals: {
                ...globals.browser,
                // Third-party libs loaded via CDN/script tags
                bootstrap: 'readonly',
                Sortable: 'readonly',
                ace: 'readonly',
                tinymce: 'readonly',
                EasyMDE: 'readonly',
                // Maps SDKs (loaded dynamically via loadScript())
                ymaps: 'readonly',
                google: 'readonly',
                // Zentra globals injected from Blade via window.*
                ZentraConfig: 'readonly',
                ZentraMaps: 'readonly',
                ZentraBootstrapIcons: 'readonly',
                // Shared helpers defined in core.js (writable — auth/install define their own)
                showToast: 'writable',
            },
        },
        rules: {
            'no-unused-vars': ['warn', { argsIgnorePattern: '^_', varsIgnorePattern: '^_', caughtErrorsIgnorePattern: '^_' }],
            'no-undef': 'error',
            'no-console': 'off',
            'no-var': 'error',
            'prefer-const': 'warn',
            eqeqeq: ['error', 'always', { null: 'ignore' }],
            curly: ['error', 'all'],
            // Empty catch {} is valid ES2019+ and used intentionally in fetch error handling
            'no-empty': ['error', { allowEmptyCatch: true }],
            // Browser scripts legitimately redeclare globals (e.g. showToast per standalone page)
            'no-redeclare': 'off',
            // Advisory for browser scripts: global scope functions are sometimes intentional
            'no-implicit-globals': 'warn',
        },
    },
];
