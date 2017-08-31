var path = require('path');
var webpack = require('webpack');

var CopyWebpackPlugin = require('copy-webpack-plugin');
var CleanWebpackPlugin = require('clean-webpack-plugin');


module.exports = {
  entry: [
    'babel-polyfill',
    './src/main.jsx' // Your appʼs entry point
  ],
  output: {
    path: path.join(__dirname, 'dist'),
    filename: "bundle.js"
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
        screw_ie8: true,
        keep_fnames: true
      },
      compress: {
        screw_ie8: true,
        warnings: false
      },
      comments: false
    }),
    new CopyWebpackPlugin([{
      from: './src/index.html'
    }])
  ],
  resolve: {
    // require files in app without specifying extensions
    extensions: ['.js', '.jsx']
  },
  module: {
    loaders: [
      {
        test: /\.jsx?$/,
        exclude: /node_modules/,
        loaders: ['babel-loader?presets[]=react,presets[]=es2015']
      },
      {
        test: /\.css$/,
        loaders: ['style-loader', 'css-loader']
      },
      {
        test: /\.less$/,
        loaders: ['style-loader', 'css-loader', 'less-loader']
      },
      { test: /\.woff(\?.*)?$/,  loader: "url-loader?name=fonts/[hash].[ext]&limit=1000" },
      { test: /\.woff2(\?.*)?$/, loader: "url-loader?name=fonts/[hash].[ext]&limit=1000" },
      { test: /\.ttf(\?.*)?$/,   loader: "url-loader?name=fonts/[hash].[ext]&limit=1000" },
      { test: /\.eot(\?.*)?$/,   loader: "file-loader?name=fonts/[hash].[ext]&limit=1000" },
      { test: /\.svg(\?.*)?$/,   loader: "url-loader?name=fonts/[hash].[ext]&limit=1000" }
    ]
  }
};