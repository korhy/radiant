/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./assets/**/*.js",
    "./templates/**/*.html.twig",
  ],
  theme: {
    extend: {
      colors: {
        'bg-blue-950' : '#23313e',
        'amber-400' : '#FCC175',
        'amber-500' : '#faa734ff',
        'amber-600' : '#E68A1A',
        'amber-700' : '#B76A14',

      }
    },
  },
  plugins: [],
}