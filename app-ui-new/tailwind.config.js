import flowbite from 'flowbite/plugin'
import typography from '@tailwindcss/typography'

/** @type {import('tailwindcss').Config} */
export default {
  darkMode: 'class', // Enable class-based dark mode
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
    "./node_modules/flowbite/**/*.js",
    "./node_modules/rete-editor/**/*.{js,vue}",
  ],
  theme: {
    extend: {
      colors: {
        gray: {
          50: '#F8F8F8',
          100: '#DEDEDE',
          200: '#C4C4C4',
          300: '#ABABAB',
          400: '#929292',
          500: '#7A7A7A',
          600: '#636363',
          700: '#333333',
          800: '#1F1F1F',
          900: '#141414',
          950: '#141414',
        },
        primary: {
          50:  '#EDFDF4',
          100: '#D3FBDF',
          200: '#ABF7C5',
          300: '#71EFA3',
          400: '#3BE689',
          500: '#1BEA83',
          600: '#10C86C',
          700: '#0D9E58',
          800: '#0F7C47',
          900: '#0D663C',
          950: '#043A21',
        },
      },
      transitionProperty: {
        'width': 'width'
      },
    },
  },
  plugins: [flowbite, typography],
}

