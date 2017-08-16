var path = require('path'),
  webpack = require('webpack');

var CopyWebpackPlugin = require('copy-webpack-plugin');
var CleanWebpackPlugin = require('clean-webpack-plugin');


module.exports = {
  entry: [
    'babel-polyfill',
    './src/app' // Your appʼs entry point
  ],
  output: {
    path: path.join(__dirname, '/build'),
    filename: "bundle.js"
  },
  plugins: [
    new CleanWebpackPlugin('build', {
      exclude: ['.gitignore']
    }),
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
    extensions: ['', '.js', '.json', '.jsx', '.less'],
    alias: {
      // pretty useful to have a starting point in nested modules
      'appRoot': path.join(__dirname, 'src'),
      'vendor': 'appRoot/vendor'
    }
  },
  module: {
    loaders: [
      { test: /\.json$/,      loader: 'json'},
      { test: /\.(xml|bpmn)$/, loader: 'raw-loader'},
      { test: /\.less$/,      loader: 'style-loader!css-loader!autoprefixer?browsers=last 2 version!less-loader' },
      { test: /\.css$/,       loader: 'style-loader!css-loader' },
      { test: /\.(png|jpg)$/, loader: 'url-loader?name=imgs/[hash].[ext]&limit=8192'}, // inline base64 URLs for <=8k images, direct URLs for the rest
      {
        test: /\.jsx?$/,
        include: [
          path.resolve(__dirname, "src"),
          path.resolve(__dirname, "node_modules/flusanec")
        ],
        loaders: ['babel?presets[]=react,presets[]=es2016,presets[]=es2015'] // loaders process from right to left
      },
      { test: /\.woff(\?.*)?$/,  loader: "url-loader?name=fonts/[hash].[ext]&limit=1000" },
      { test: /\.woff2(\?.*)?$/, loader: "url-loader?name=fonts/[hash].[ext]&limit=1000" },
      { test: /\.ttf(\?.*)?$/,   loader: "url-loader?name=fonts/[hash].[ext]&limit=1000" },
      { test: /\.eot(\?.*)?$/,   loader: "file-loader?name=fonts/[hash].[ext]&limit=1000" },
      { test: /\.svg(\?.*)?$/,   loader: "url-loader?name=fonts/[hash].[ext]&limit=1000" }
    ]
  }
};