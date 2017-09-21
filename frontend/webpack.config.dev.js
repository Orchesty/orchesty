var path = require('path');
var webpack = require('webpack');
const merge = require('webpack-merge');
const common = require('./webpack.config.common.js');

module.exports = merge(common, {
  devtool: 'source-map',
  entry: [
    'react-hot-loader/patch',
    'webpack-dev-server/client?http://localhost:8080',
    'webpack/hot/only-dev-server',
    './src/main_dev.jsx'
  ],
  output: {
    path: path.join(__dirname, 'dist'),
    filename: 'bundle.js',
    publicPath: '/'
  },
  plugins: [
    new webpack.NamedModulesPlugin()
  ],
  resolve: {
    alias: {
      'config-env': path.join(__dirname, 'src', 'config', 'dev')
    }
  },
  module: {
    loaders: [
      {
        test: /\.jsx?$/,
        exclude: /node_modules/,
        loaders: ['react-hot-loader/webpack', 'babel-loader?presets[]=react,presets[]=es2015,presets[]=es2016,presets[]=es2017']
      }
    ]
  },
  devServer: {
    hot: true,
    inline: true,
    historyApiFallback: {
      index: '/'
    }
  }
});
