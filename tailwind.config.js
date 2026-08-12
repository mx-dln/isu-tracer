/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './views/**/*.php',
    './public/**/*.php',
    './app/**/*.php',
    './public/assets/js/app.js',
    './public/assets/js/public-survey.js',
    './public/assets/js/admin-surveys.js',
    './public/assets/js/pages/*.js',
  ],
  theme: {
    extend: {
      colors: {
        brand: {
          50: '#f0f7f4',
          100: '#daeee3',
          200: '#b8dcc9',
          300: '#8cc4a8',
          400: '#5ca584',
          500: '#3d8a68',
          600: '#2c6e52',
          700: '#245843',
          800: '#1f4737',
          900: '#1a3b2f',
        },
        ink: {
          50: '#f8fafc',
          100: '#f1f5f9',
          200: '#e2e8f0',
          300: '#cbd5e1',
          400: '#94a3b8',
          500: '#64748b',
          600: '#475569',
          700: '#334155',
          800: '#1e293b',
          900: '#0f172a',
        },
      },
      fontFamily: {
        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
      },
      boxShadow: {
        card: '0 1px 3px 0 rgb(15 23 42 / 0.06), 0 1px 2px -1px rgb(15 23 42 / 0.06)',
      },
    },
  },
  plugins: [],
};
