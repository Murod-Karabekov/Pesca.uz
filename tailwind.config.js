/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './templates/**/*.html.twig',
    './assets/**/*.js',
  ],
  theme: {
    extend: {
      colors: {
        peach: {
          50:  '#FFF8F5',
          100: '#FFF0EB',
          200: '#FFDDD2',
          300: '#FFC4B0',
          400: '#FFAA8A',
          500: '#FF8E63',
          600: '#E06D40',
          700: '#B85230',
          800: '#8C3D24',
          900: '#6B2F1C',
        },
        beige: {
          50:  '#FEFCF9',
          100: '#FDF8F0',
          200: '#FAF0E0',
          300: '#F5E4CC',
          400: '#EDD5B3',
          500: '#E4C49A',
          600: '#C9A577',
          700: '#A98558',
          800: '#86673E',
          900: '#674E30',
        },
        brand: {
          dark:   '#2D2926',
          medium: '#5C5552',
          light:  '#8A8380',
          muted:  '#B8B2AF',
        },
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
        display: ['Playfair Display', 'Georgia', 'serif'],
      },
      borderRadius: {
        'xl': '1rem',
        '2xl': '1.5rem',
        '3xl': '2rem',
      },
      spacing: {
        '18': '4.5rem',
        '22': '5.5rem',
      },
    },
  },
  plugins: [],
}
