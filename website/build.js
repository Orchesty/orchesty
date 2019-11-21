const Metalsmith = require('metalsmith')
const collections = require('metalsmith-collections')
const assets = require('metalsmith-assets')
const replace = require('metalsmith-one-replace')
const layouts = require('metalsmith-layouts')
const markdown = require('metalsmith-markdown')
const mdpartials = require('metalsmith-markdown-partials')
const permalinks = require('metalsmith-permalinks')
const headingsidentifier = require('metalsmith-headings-identifier')
const headings = require('metalsmith-headings')
const autotoc = require('metalsmith-autotoc')
const debug = require('metalsmith-debug')
const s3 = require('metalsmith-s3')
const watch = require('metalsmith-watch')

require('handlebars-helpers')();

let ms = Metalsmith(__dirname)
    .metadata({
        sitename: 'Pipes Website'
    })
    .source('./src')
    .destination('./build')
    .clean(true)
    .use(collections({
        Collection1: {
            pattern: 'docs/*.md',
            metadata: {
                name: "generovana kolekce1"
            }
        },
        Collection2: {
            pattern: 'stack/*.md',
            sortBy: 'order',
            metadata: {
                name: "generovana kolekce2"
            }
        },
        news: {
            pattern: '',
            metadata: {
                name: "news kolekce",
                reverse: false,
                sortBy: 'order'
            }
        },
        install: {
            pattern: '',
            metadata: {
                name: "install kolekce",
                reverse: false,

            }
        },
        pages_coll: {
            pattern: 'pages/*.md',
            metadata: {
                name: "pages",
                reverse: false,
            }
        },

    }))
    .use(replace({
        actions:[{
            type:'var',
            varValues: {
                'page_url':'http://127.0.0.88:8000/installer'
            }
        }]
    }))
    .use(mdpartials({
        libraryPath: 'src/'
    }))
    .use(markdown({
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
    .use(assets({
        source: './src/assets/css',
        destination: 'css'
    }))
    .use(assets({
        source: './src/docs/images',
        destination: 'images'
    }))
    //.use(s3({
    //  action: 'write',
    //  bucket: 'pipes-website',
    //  s3: {
    //    ACL: 'public-read'
    //  }
    //}))
    .use(debug())

if (process.argv.includes('--dev-server'))
    ms = ms.use(
        watch({
            paths: {
                "${source}/**/*": true, // rebuild the file when it changed
                "layouts/**/*": "**/*", // rebuild all files when layout changes
            },
            livereload: false, // not yet ;)
        })
    )

ms.build((err) => {
    if (err) throw err
})


