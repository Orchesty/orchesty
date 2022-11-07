module.exports = {
  extends: [
    "stylelint-config-standard-scss",
    "stylelint-config-prettier-scss",
    "stylelint-config-recommended-vue/scss",
  ],
  rules: {
    "color-function-notation": ["legacy"], // legacy color notation is still used more than the new one
    "color-hex-length": null, // colors defined as 6 hex characters are fine
    "no-empty-source": null, // don't throw error when .vue component has empty styles
    "scss/comment-no-empty": null, // empty comments are used for delimiting sections
    "scss/dollar-variable-empty-line-before": null, // a block of redeclared variables is better without empty lines
    "alpha-value-notation": ["number"], // don't force percentage, a number is fine
    "shorthand-property-no-redundant-values": null, // the case for 3 values instead of 4 is bad
    "selector-class-pattern": null, // don't lint class selectors, BEM is fine
  },
}
