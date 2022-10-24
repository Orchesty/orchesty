/* eslint-disable @typescript-eslint/no-var-requires */
const path = require("path")

module.exports = {
  chainWebpack: (config) => {
    configSVGIcon(config)
  },
  transpileDependencies: ["vuetify"],
  pluginOptions: {
    storybook: {
      allowedPlugins: ["VuetifyLoaderPlugin"],
    },
    "style-resources-loader": {
      preProcessor: "scss",
      patterns: [path.resolve(__dirname, "./src/styles/variables.scss")],
    },
  },
}

function configSVGIcon(config) {
  const iconFolder = path.resolve(__dirname, "./src/assets/img/icons")

  config.module.rule("svg").exclude.add(iconFolder).end()

  config.module
    .rule("svg-icon")
    .test(/\.svg$/)
    .include.add(iconFolder)
    .end()
    .use("svg-sprite-loader")
    .loader("svg-sprite-loader")
    .options({
      symbolId: "icon-[name]",
    })
    .end()
}
