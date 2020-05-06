const marked = require('marked');

const markdownRenderer = new marked.Renderer()

markdownRenderer.image = function (href, title, text) {
  return `
  <figure>
    <img src="${href}" alt="${title}" title="${title}" />
    <figcaption>
      <p>${text}</p>
    </figcaption>
  </figure>`
}
markdownRenderer.code = function (code, infostring, escaped) {
  let lang = (infostring || '').match(/\S*/)[0]
  let highlighted = code
  if (this.options.highlight) {
    let out = this.options.highlight(code, lang)
    if (out != null && out !== code) {
      escaped = true
      highlighted = out
    }
  }

  if (!lang) {
    return '<pre class="code"><code>'
      + (escaped ? highlighted : escape(code))
      + '</code></pre>'
  }

  if (lang === 'infoBlock') {
    return '<pre class="infoBlock">'
      + code
      + '</pre>'
  }

  return '<pre class="code"><code><label>'
    + escape(lang)
    + '</label>'
    + (escaped ? highlighted : escape(code))
    + '</code></pre>\n'
}

module.exports = markdownRenderer