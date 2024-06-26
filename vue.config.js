const CircularDependencyPlugin = require("circular-dependency-plugin")

module.exports = {
  transpileDependencies: ["vuetify"],
  productionSourceMap: false,
  lintOnSave: true,
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
