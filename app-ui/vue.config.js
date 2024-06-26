const CircularDependencyPlugin = require("circular-dependency-plugin")

module.exports = {
  transpileDependencies: ["vuetify"],
  productionSourceMap: false,
  lintOnSave: true,
  css: {
    loaderOptions: {
      scss: {
        additionalData: `@use "sass:map";`,
      },
    },
  },
  configureWebpack: {
    plugins: [
      new CircularDependencyPlugin({
        exclude: /node_modules/,
        failOnError: true,
        allowAsyncCycles: false,
        cwd: process.cwd(),
      }),
    ],
  },
}
