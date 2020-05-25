const autotoc = require('metalsmith-autotoc')
const collections = require('metalsmith-collections')
const discoverHelpers = require('metalsmith-discover-helpers')
const discoverPartials = require('metalsmith-discover-partials')
const headings = require('metalsmith-headings')
const headingsidentifier = require('metalsmith-headings-identifier')
const layouts = require('metalsmith-layouts')
const lunr = require('metalsmith-lunr');
const markdown = require('metalsmith-markdown')
const mdpartials = require('metalsmith-markdown-partials')
const Metalsmith = require('metalsmith')
const permalinks = require('metalsmith-permalinks')
const replace = require('metalsmith-one-replace')

const markdownRenderer = require("./markdown-renderer")

const createMetalSmith = () => {
  return Metalsmith(__dirname)
    .metadata({
      sitename: 'Pipes Website'
    })
    .source('./src')
    .destination('./build')
    .clean(false)
    .use(collections({
      documentation: {
        pattern: '*.md',
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
          'portal_api_url': process.env.PORTAL_API_URL || 'http://localhost.com'
        }
      }]
    }))
    .use(mdpartials({
      libraryPath: 'src/docs/'
    }))
    .use(markdown({
      renderer: markdownRenderer,
      highlight: function (code) {
        return require('highlight.js').highlightAuto(code).value
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
      'h2'
    ))
    // adding identifiers for inserting scrollspy class into html file
    .use(headingsidentifier({
      // There are no markdown hashes - already converted to html by use(markdown)
      selector: 'H2',
      // Automatically generates also heading-anchor class with href
      headingClass: "section scrollspy"
    }))
    .use(autotoc({
      selector: 'h1, h2, h3, h4'
    }))
    .use(discoverPartials({
      directory: 'handlebars/partials',
      pattern: /\.hbs$/
    }))
    .use(discoverHelpers({
      directory: 'handlebars/helpers',
      pattern: /\.js$/
    }))
    .use(layouts({
      engine: 'handlebars',
      default: "main.hbs",
      directory: 'handlebars/layouts'
    }))
    .use(lunr(
      {
        ref: 'name',
        indexPath: 'index.json',
        fields: {
          contents: 1,
          tags: 10
        },
        preprocess: function (content) {
          return '/' + this.path + '||' + this.name;
        }
      }
    ))
}

module.exports = createMetalSmith