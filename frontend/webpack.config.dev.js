const path = require('path');
const fs = require('fs');
const {merge} = require('webpack-merge');
const common = require('./webpack.config.common.js');

let configPath = path.join(__dirname, 'src', 'config', 'local');
if (!fs.existsSync(configPath)) {
  configPath = path.join(__dirname, 'src', 'config', 'dev');
}

console.log('App config file:', configPath);

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
    publicPath: '/ui/'
  },
  resolve: {
    alias: {
      'config-env': configPath,
      'react-dom': '@hot-loader/react-dom',
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
            plugins: ['react-hot-loader/babel', '@babel/plugin-proposal-object-rest-spread']
          }
        }
      }
    ]
  },
  devServer: {
    hot: true,
    inline: true,
    historyApiFallback: {
      index: '/ui/'
    }
  }
});