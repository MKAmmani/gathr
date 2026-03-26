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
          colors: {
            primary: '#00A3FF',
            secondary: '#00D1FF',
            darkBlue: '#0047AB',
            bgGray: '#F9FAFB',
            'security-navy': '#0a364d',
            'security-gold': '#b08d24',
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
