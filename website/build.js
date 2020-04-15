const assets = require('metalsmith-assets');
const autotoc = require('metalsmith-autotoc');
const collections = require('metalsmith-collections');
const cssPacker = require('metalsmith-css-packer');
const debug = require('metalsmith-debug');
const dotenv = require('dotenv');
const headings = require('metalsmith-headings');
const headingsidentifier = require('metalsmith-headings-identifier');
const helperRegister = require('metalsmith-register-helpers');
const htmlMinifier = require("metalsmith-html-minifier");
const jsPacker = require('metalsmith-js-packer');
const layouts = require('metalsmith-layouts');
const markdown = require('metalsmith-markdown');
const marked = require('marked');
const mdpartials = require('metalsmith-markdown-partials');
const Metalsmith = require('metalsmith');
const permalinks = require('metalsmith-permalinks');
const replace = require('metalsmith-one-replace');
const watch = require('metalsmith-watch');

require('handlebars-helpers')();
dotenv.config();
const markdownRenderer = new marked.Renderer();

markdownRenderer.image = function (href, title, text) {
  return `
  <figure>
    <img src="${href}" alt="${title}" title="${title}" />
    <figcaption>
      <p>${text}</p>
    </figcaption>
  </figure>`;
};
markdownRenderer.code = function (code, infostring, escaped) {
  let lang = (infostring || '').match(/\S*/)[0];
  let highlighted = code;
  if (this.options.highlight) {
    let out = this.options.highlight(code, lang);
    if (out != null && out !== code) {
      escaped = true;
      highlighted = out;
    }
  }

  if (!lang) {
    return '<pre class="code"><code>'
      + (escaped ? highlighted : escape(code))
      + '</code></pre>';
  }

  if (lang === 'infoBlock') {
    return '<pre class="infoBlock">'
      + code
      + '</pre>';
  }

  return '<pre class="code"><code><label>'
    + escape(lang)
    + '</label>'
    + (escaped ? highlighted : escape(code))
    + '</code></pre>\n';
};

let ms = Metalsmith(__dirname)
  .metadata({
    sitename: 'Pipes Website'
  })
  .source('./src')
  .destination('./build')
  .clean(true)
  .use(helperRegister({directory: '_helpers'}))
  .use(collections({
    documentation: {
      sortBy: 'index',
      metadata: {
        name: "Documentation",
        description: "Description of PIPES documentation ..."
      },
    },
  }))
  .use(replace({
    actions: [{
      type: 'var',
      varValues: {
        'portal_api_url': process.env.PORTAL_API_URL
      }
    }]
  }))
  .use(mdpartials({
    libraryPath: 'src/'
  }))
  .use(markdown({
    renderer: markdownRenderer,
    highlight: function (code) {
      return require('highlight.js').highlightAuto(code).value;
    },
    pattern: '**/*.md',
    engine: 'handelbars',
    smartypants: true,
    smartlists: true,
    gfm: true,
    tables: true,
  }))
  .use(permalinks({
    relative: false,
    pattern: ':title'
  }))
  // adds object with list of headings
  .use(headings(
    'h4'
  ))
  // adding identifiers for inserting scrollspy class into html file
  .use(headingsidentifier({
    // There are no markdown hashes - already converted to html by use(markdown)
    selector: 'H4',
    // Automatically generates also heading-anchor class with href
    headingClass: "section scrollspy"
  }))
  .use(autotoc({
    selector: 'h1, h2, h3, h4'
  }))
  .use(layouts({
    engine: 'handlebars',
    default: "main.hbs",
    directory: 'layouts'
  }))
  .use(cssPacker({
    removeLocalSrc: true,
    outputPath: 'assets/css/'
  }))
  .use(jsPacker({
    ouputPath: 'assets/js/'
  }))
  .use(htmlMinifier({
    minifierOptions: {
      collapseBooleanAttributes: true,
      collapseWhitespace: true,
      removeAttributeQuotes: true,
      removeComments: true,
      removeEmptyAttributes: true,
      removeRedundantAttributes: true,
    },
  }))
  .use(assets({
    source: './src/assets/img',
    destination: 'img'
  }))
  //.use(s3({
  //  action: 'write',
  //  bucket: 'pipes-website',
  //  s3: {
  //    ACL: 'public-read'
  //  }
  //}))
  .use(debug());

if (process.argv.includes('--dev-server')) {
  ms.use(
    watch({
      paths: {
        "${source}/**/*": true, // rebuild the file when it changed
        "layouts/**/*": "**/*", // rebuild all files when layout changes
      },
    })
  )
}

ms.build((err) => {
  if (err) throw err
});


