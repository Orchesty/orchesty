const Metalsmith = require('metalsmith')
const collections = require('metalsmith-collections')
const layouts = require('metalsmith-layouts')
const markdown = require('metalsmith-markdown')
const inplace = require('metalsmith-markdown')
const mdpartials = require('metalsmith-markdown-partials')
const autotoc = require('metalsmith-autotoc')
const debug = require('metalsmith-debug')
const s3 = require('metalsmith-s3')

require('handlebars-helpers')();

Metalsmith(__dirname)
  .metadata({
    sitename: 'Pipes Website'
  })
  .source('./src')
  .destination('./build')
  .clean(true)
  .use(collections({
    pages: 'pages/*.md'
  }))
  .use(mdpartials({
      libraryPath: 'src/'
    }))
  .use(markdown())
  .use(inplace({
    engine: 'handlebars',
    pattern: '**'
  }))
  .use(autotoc())
  .use(layouts({
      default: 'default.hbs'
  }))
  //.use(s3({
  //  action: 'write',
  //  bucket: 'pipes-website',
  //  s3: {
  //    ACL: 'public-read'
  //  }
  //}))
  .use(debug())
  .build((err) => { if (err) throw err })
