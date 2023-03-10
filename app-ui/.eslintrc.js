module.exports = {
  plugins: ["vue"],
  extends: [
    "plugin:vue/essential",
    "plugin:vue/recommended",
    "eslint:recommended",
    "@vue/prettier",
  ],
  parserOptions: {
    parser: "babel-eslint",
  },
  env: {
    es6: true,
    node: true,
  },
  rules: {
    "vue/multi-word-component-names": "off",
    "prettier/prettier": "warn",
    "vue/max-attributes-per-line": "off",
    "vue/singleline-html-element-content-newline": "off",
    "vue/order-in-components": "off",
    "vue/html-self-closing": [
      "error",
      { html: { void: "any", normal: "any", component: "always" } },
    ],
    "vue/valid-v-slot": "off",
    "no-case-declarations": "off",
  },
  overrides: [
    {
      files: ["**/__tests__/*.{j,t}s?(x)"],
      env: {
        jest: true,
      },
    },
  ],
}
