##Following dependencies cannot be updated due to backwards incompatibility with Vue 3 and Vue 2.
An update will be possible with the release of Vuetify Titan with the support for Vue 3.

```text
@casl/vue
vee-validate
vue
vue-i18n
vue-router
vuex
```

#`vue-eslint-parser`
`vue-eslint-parser` doesn't support ES-Lint 8 yet, hence cannot be updated. The issue is being tracked here.
https://github.com/vuejs/vue-cli/issues/6759

#`sass-loader`
`sass-loader@11.0.0` doesn't work with `vue@2.6.12` since Vue 2 is using `webpack 4`. Upgrading to Vue 3 will be the solution. For now, it is being downgraded to `"sass-loader": "^10"`

https://stackoverflow.com/questions/66847329/vue-cli-using-webpack-4-instead-of-5-by-default