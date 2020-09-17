const path = require('path');
const webpack = require('webpack');
const fs = require('fs');
const {merge} = require('webpack-merge');
const common = require('./webpack.config.common.js');

const {CleanWebpackPlugin} = require("clean-webpack-plugin");

let configPath = path.join(__dirname, 'src', 'config', 'local');
if (!fs.existsSync(configPath)) {
  configPath = path.join(__dirname, 'src', 'config', 'prod');
}

console.log('App config file:', configPath);

module.exports = merge(common, {
    mode: "production",
    devtool: 'source-map',
    entry: [
      '@babel/polyfill',
      './src/main_prod.jsx' // Your appʼs entry point
    ],
    output: {
      path: path.join(__dirname, 'dist'),
      filename: "bundle.js",
      publicPath: '/ui/'
    },
    plugins: [
      new CleanWebpackPlugin(),
      new webpack.DefinePlugin({
        'process.env': {
          'NODE_ENV': JSON.stringify('production')
        }
      })
    ],
    resolve: {
      alias: {
        'config-env': configPath
      }
    },
  module: {
    rules: [
      {
        test: /\.jsx?$/,
        exclude: /(node_modules|bower_components)/,
        use: {
          loader: 'babel-loader',
          options: {
            presets: ['@babel/preset-react'],
            plugins: ['@babel/plugin-proposal-object-rest-spread']
          }
        }
      }
    ]
  },
    optimization: {
      minimize: true,
      namedModules: true
    }
  }
);