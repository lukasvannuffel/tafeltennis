/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './templates/**/*.{html,twig}', // Voor Craft CMS of Twig-bestanden
  ],
  theme: {
    extend: {
      maxWidth: {
        '35': '35%',
      },
    },
  },
  plugins: [],
};
