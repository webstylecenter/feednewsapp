import js from '@eslint/js';
import pluginVue from 'eslint-plugin-vue';
import globals from 'globals';

export default [
    {
        ignores: [
            'node_modules/**',
            '../backend/public/**',
        ],
    },
    js.configs.recommended,
    ...pluginVue.configs['flat/essential'],
    {
        files: ['src/**/*.{js,vue}'],
        languageOptions: {
            globals: globals.browser,
        },
        rules: {
            'no-empty': ['error', { allowEmptyCatch: true }],
            'vue/multi-word-component-names': 'off',
        },
    },
];
