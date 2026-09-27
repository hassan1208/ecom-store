// Admin panel Tailwind build. Re-run `npm run build:admin-css` after adding new
// utility classes to admin templates.
module.exports = {
  content: ['./admin/**/*.php', './admin/assets/js/**/*.js'],
  theme: {
    extend: {
      fontFamily: { sans: ['Plus Jakarta Sans', 'system-ui', 'sans-serif'] },
      colors: {
        ink: '#0d1321',
        panel: '#141b2d',
        brand: { DEFAULT: '#059669', dark: '#047857', light: '#d1fae5' },
      },
    },
  },
  plugins: [require('@tailwindcss/typography')],
};
