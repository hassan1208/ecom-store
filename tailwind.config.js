// Storefront Tailwind build. Brand colours come from Admin → Settings → Branding
// at runtime (CSS variables printed in includes/site-header.php), so changing the
// theme colour never needs a rebuild. Re-run `npm run build:css` after adding new
// utility classes to any PHP template.
const withAlpha = (v) => `rgb(var(${v}) / <alpha-value>)`;

module.exports = {
  content: [
    './*.php',
    './includes/**/*.php',
    './assets/js/**/*.js',
  ],
  safelist: [
    'bg-emerald-500', 'bg-slate-700', 'bg-ignite', 'bg-sky-500', 'bg-violet-500',
    'text-amber-400', 'text-slate-300', 'opacity-0', 'opacity-100', 'z-0', 'z-10', 'w-2.5', 'w-7', 'bg-white/40',
  ],
  theme: {
    extend: {
      fontFamily: {
        display: ['Oswald', 'Impact', 'sans-serif'],
        sans: ['Inter', 'system-ui', 'sans-serif'],
        poster: ['Anton', 'Impact', 'sans-serif'],
      },
      colors: {
        ink: withAlpha('--c-ink'),
        ignite: { DEFAULT: withAlpha('--c-ignite'), dark: withAlpha('--c-ignite-dark') },
      },
      boxShadow: {
        glow: '0 10px 40px -10px rgb(var(--c-ignite) / 0.55)',
        card: '0 1px 2px rgb(15 23 42 / 0.04), 0 12px 32px -12px rgb(15 23 42 / 0.18)',
      },
      keyframes: {
        floaty: { '0%,100%': { transform: 'translateY(0)' }, '50%': { transform: 'translateY(-10px)' } },
        shimmer: { '0%': { backgroundPosition: '-200% 0' }, '100%': { backgroundPosition: '200% 0' } },
      },
      animation: {
        floaty: 'floaty 6s ease-in-out infinite',
        shimmer: 'shimmer 2.4s linear infinite',
      },
    },
  },
  plugins: [require('@tailwindcss/typography')],
};
