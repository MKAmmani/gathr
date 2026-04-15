import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
          darkMode: "class",
          colors: {
            primary: '#00A3FF',
            secondary: '#00D1FF',
            darkBlue: '#0047AB',
            bgGray: '#F9FAFB',
            'security-navy': '#0a364d',
            'security-gold': '#b08d24',
            'primary-dark': "#006FA8",
            "surface": "#FFFFFF",
            "background": "#F8FAFC",
            "on-surface": "#1E293B",
            "on-surface-variant": "#64748B",
            "success": "#22C55E",
            "warning": "#F59E0B",
            "info-bg": "#E0F2FE",
            "primary": "#00A0E9",
              "background": "#F8FAFB",
              "surface": "#FFFFFF",
              "on-surface": "#333333",
              "on-surface-variant": "#666666",
              "outline-variant": "#E8F5FD",
              "surface-container-highest": "#E6EBF1",
              "header-blue": "#107CC0",
                        "paystack-banner": "#2C7EAF",
                        "progress-orange": "#F9A825",
                        "progress-track": "#D9D9D9",
                        "stats-text": "#757575",
                        "badge-yellow": "#FFC107",
                        "surface": "#F5F8FA",
                        "card-green": "#E8F5E9",
                        "card-blue": "#E3F2FD",
                        "card-yellow": "#FFF9C4",
                "secondary-blue": "#0EA5E9",
                    "brand-orange": "#FFB84D",
                    "card-bg-start": "#034878",
                    "card-bg-end": "#0392DA",
                    "peach-bg": "#FEF3E2",
            brand: {
              blue: '#007fbd',
              darkBlue: '#0288D1',
              lightBlue: '#E1F5FE',
              green: '#25D366',
              navy: '#1A237E'
            }
          },
          fontFamily: {
            sans: ['Inter', 'sans-serif'],
             "headline": ["Manrope", "sans-serif"],
             "body": ["Inter", "sans-serif"],
          },
          borderRadius: {
            DEFAULT: '0.25rem',
            lg: '0.5rem',
            xl: '1.25rem',
            full: '9999px',
            "lg": "12px",
             "xl": "12px",
          },
          keyframes: {
            marquee: {
              '0%': { transform: 'translateX(0)' },
              '100%': { transform: 'translateX(-50%)' },
            },
            marqueeSlow: {
              '0%': { transform: 'translateX(0)' },
              '100%': { transform: 'translateX(-50%)' },
            }
          },
          animation: {
            marquee: 'marquee 20s linear infinite',
            'marquee-slow': 'marqueeSlow 30s linear infinite',
          }
        }
      },

    plugins: [forms],
};
