import eslint from '@eslint/js';
import eslintConfigPrettier from 'eslint-config-prettier';
import globals from "globals";
import pluginVue from "eslint-plugin-vue";
import { defineConfig } from "eslint/config";

export default defineConfig([
  {
    extends: [
      // eslint.configs.recommended,
      ...pluginVue.configs['flat/recommended'],
    ],
    files: ['**/*.{ts,vue}'],
    languageOptions: {
      ecmaVersion: 'latest',
      sourceType: 'module',
      globals: globals.browser,

    },
    rules: {
      'vue/multi-word-component-names': 'off',
      'indent': ['error', 2],
    },
  },
  eslintConfigPrettier,
]);
