/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ["./public/**/*.{html,js}", "./node_modules/flowbite/**/*.js"],
  theme: {
    fontFamily: {
      'sans': ['Montserrat']
    },
    extend: {},
  },
  plugins: [
    require('flyonui'),
    require('daisyui'),
    require('flowbite/plugin')
  ],
}

