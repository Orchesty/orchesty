var path = require('path');
var webpack = require('webpack');
const fs = require('fs');
const merge = require('webpack-merge');
const common = require('./webpack.config.common.js');

var CleanWebpackPlugin = require('clean-webpack-plugin');

let configPath = path.join(__dirname, 'src', 'config', 'local');
if (!fs.existsSync(configPath)){
  configPath = path.join(__dirname, 'src', 'config', 'prod');
}

console.log('App config file:', configPath);

module.exports = merge(common, {
  entry: [
    'babel-polyfill',
    './src/main_prod.jsx' // Your appʼs entry point
  ],
  output: {
    path: path.join(__dirname, 'dist'),
    filename: "bundle.js",
    publicPath: '/ui/'
  },
  plugins: [
    new CleanWebpackPlugin('dist'),
    new webpack.DefinePlugin({
      'process.env':{
        'NODE_ENV': JSON.stringify('production')
      }
    }),
    new webpack.optimize.UglifyJsPlugin({
      beautify: false,
      mangle: {
        screw_ie8: false,
        keep_fnames: false
      },
      compress: {
        screw_ie8: false,
        warnings: false
      },
      comments: false
    })
  ],
  resolve: {
    alias: {
      'config-env': configPath
    }
  },
  module: {
    loaders: [
      {
        test: /\.jsx?$/,
        exclude: /node_modules/,
        loaders: ['babel-loader?presets[]=react,presets[]=es2015,presets[]=es2016,presets[]=es2017']
      }
    ]
  }
});