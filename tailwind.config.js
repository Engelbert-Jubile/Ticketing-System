// tailwind.config.js
import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

export default {
  darkMode: 'class',
  content: [
    './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    './storage/framework/views/*.php',
    './resources/views/**/*.blade.php',
    './resources/js/**/*.vue',
    './resources/js/**/*.js',
    './resources/css/**/*.css'
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Figtree', ...defaultTheme.fontFamily.sans]
      }
    }
  },
  plugins: [forms],
  safelist: [
    'translate-x-0',
    '-translate-x-full',
    'opacity-100',
    'pointer-events-auto',
    'bg-gray-100',
    'dark:bg-gray-900',
    'text-gray-900',
    'dark:text-gray-100'
  ]
};
