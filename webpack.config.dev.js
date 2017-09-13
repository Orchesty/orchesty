var path = require('path');
var webpack = require('webpack');
var CopyWebpackPlugin = require('copy-webpack-plugin');

module.exports = {
  devtool: 'source-map',
  entry: [
    'react-hot-loader/patch',
    'webpack-dev-server/client?http://localhost:8080',
    'webpack/hot/only-dev-server',
    './src/main.jsx'
  ],
  output: {
    path: path.join(__dirname, 'dist'),
    filename: 'bundle.js'
  },
  plugins: [
    new CopyWebpackPlugin([{
      from: './src/index.html'
    }]),
    new webpack.NamedModulesPlugin()
  ],
  resolve: {
    extensions: ['.js', '.jsx']
  },
  module: {
    loaders: [
      {
        test: /\.jsx?$/,
        exclude: /node_modules/,
        loaders: ['react-hot-loader/webpack', 'babel-loader?presets[]=react,presets[]=es2015,presets[]=es2016,presets[]=es2017']
      },
      {
        test: /\.css$/,
        loaders: ['style-loader', 'css-loader']
      },
      {
        test: /\.less$/,
        loaders: ['style-loader', 'css-loader', 'less-loader']
      },
      { 
        test: /\.(png|jpg)$/, 
        loader: 'url-loader?name=imgs/[hash].[ext]&limit=16384'
      },
      {
        test: /\.(xml|bpmn)$/,
        loader: 'raw-loader'
      },
      { test: /\.woff(\?.*)?$/,  loader: "url-loader?name=fonts/[hash].[ext]&limit=1000" },
      { test: /\.woff2(\?.*)?$/, loader: "url-loader?name=fonts/[hash].[ext]&limit=1000" },
      { test: /\.ttf(\?.*)?$/,   loader: "url-loader?name=fonts/[hash].[ext]&limit=1000" },
      { test: /\.eot(\?.*)?$/,   loader: "file-loader?name=fonts/[hash].[ext]&limit=1000" },
      { test: /\.svg(\?.*)?$/,   loader: "url-loader?name=fonts/[hash].[ext]&limit=1000" }
    ]
  },
  devServer: {
    hot: true,
    inline: true
  }
};
