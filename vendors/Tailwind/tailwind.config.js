module.exports = {
  content: [
    '../../views/**/*.thtml',
  ],
  darkMode: "class", // or 'media' or 'class'
  theme: {
    extend: {},
  },
  variants: {
    extend: {},
  },
  plugins: [
    require("daisyui")
  ],
}