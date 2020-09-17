const path = require('path');
const CopyWebpackPlugin = require('copy-webpack-plugin');

const rootApp = path.join(__dirname, 'src');
const rootView = path.join(rootApp, 'views');

module.exports = {
  plugins: [
    new CopyWebpackPlugin({
      patterns: [
        {from: './src/static/index.html'},
        {from: './src/static/favicon.ico'},
        {from: './src/static/close-me.html'},
        {from: './src/static/.htaccess'}
      ],
      options: {
        concurrency: 100,
      },
    })
  ],
  resolve: {
    extensions: ['.js', '.jsx'],
    alias: {
      'rootApp': rootApp,
      'config-common': path.join(rootApp, 'config', 'common'),
      'actions': path.join(rootApp, 'actions'),
      'reducers': path.join(rootApp, 'reducers'),
      'services': path.join(rootApp, 'services'),
      'utils': path.join(rootApp, 'utils'),
      'enums': path.join(rootApp, 'enums'),
      'components': path.join(rootView, 'components'),
      'containers': path.join(rootView, 'containers'),
      'elements': path.join(rootView, 'elements'),
      'modals': path.join(rootView, 'modals'),
      'pages': path.join(rootView, 'pages'),
      'wrappers': path.join(rootView, 'wrappers')
    }
  },
  module: {
    rules: [
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
        loader: 'url-loader?name=files/[hash].[ext]&limit=16384'
      },
      {
        test: /\.(xml|bpmn)$/,
        loader: 'raw-loader'
      },
      {test: /\.woff(\?.*)?$/, loader: "url-loader?name=files/[hash].[ext]&limit=1000"},
      {test: /\.woff2(\?.*)?$/, loader: "url-loader?name=files/[hash].[ext]&limit=1000"},
      {test: /\.ttf(\?.*)?$/, loader: "url-loader?name=files/[hash].[ext]&limit=1000"},
      {test: /\.eot(\?.*)?$/, loader: "file-loader?name=files/[hash].[ext]&limit=1000"},
      {test: /\.svg(\?.*)?$/, loader: "url-loader?name=files/[hash].[ext]&limit=1000"}
    ]
  }
};
