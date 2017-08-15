var path = require('path'),
  webpack = require('webpack');


module.exports = {
  entry: [
    'webpack-dev-server/client?http://localhost:8080', // WebpackDevServer host and port
    'webpack/hot/only-dev-server',
    'babel-polyfill',
    './src/app' // Your appʼs entry point
  ],
  output: {
    filename: "build/bundle.js"
  },
  plugins: [
    new webpack.HotModuleReplacementPlugin(),
    new webpack.NoErrorsPlugin()
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
      { test: /\.(xml|bpmn)$/,      loader: 'raw-loader'},
      { test: /\.less$/,      loader: 'style-loader!css-loader!autoprefixer?browsers=last 2 version!less-loader' },
      { test: /\.css$/,       loader: 'style-loader!css-loader' },
      { test: /\.(png|jpg)$/, loader: 'url-loader?limit=8192'}, // inline base64 URLs for <=8k images, direct URLs for the rest
      {
        test: /\.jsx?$/,
        include: [
          path.resolve(__dirname, "src"),
          path.resolve(__dirname, "node_modules/flusanec")
        ],
        loaders: ['react-hot', 'babel?presets[]=react,presets[]=es2016,presets[]=es2015'] // loaders process from right to left
      },
      { test: /\.woff(\?.*)?$/,  loader: "url-loader?prefix=fonts/&name=[path][name].[ext]&limit=10000&mimetype=application/font-woff" },
      { test: /\.woff2(\?.*)?$/, loader: "url-loader?prefix=fonts/&name=[path][name].[ext]&limit=10000&mimetype=application/font-woff2" },
      { test: /\.ttf(\?.*)?$/,   loader: "url-loader?prefix=fonts/&name=[path][name].[ext]&limit=10000&mimetype=application/octet-stream" },
      { test: /\.eot(\?.*)?$/,   loader: "file-loader?prefix=fonts/&name=[path][name].[ext]" },
      { test: /\.svg(\?.*)?$/,   loader: "url-loader?prefix=fonts/&name=[path][name].[ext]&limit=10000&mimetype=image/svg+xml" },
    ]
  }
};

