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
  let index = (infostring || '1').match(/[\d*]/)
  index = index == null ? 1 : index;

  let highlighted = code
  if (this.options.highlight) {
    let out = this.options.highlight(code, lang)
    if (out != null && out !== code) {
      escaped = true
      highlighted = out
    }
  }

  if (!lang) {
    return '<div class="tab-content">'
      + '<pre>'
      + (escaped ? highlighted : escape(code))
      + '</pre>'
      + '</div>'
  }

  if (lang === 'infoBlock') {
    return '<div class="infoBlock">'
      + '<svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" ><path d="M256 8C119.043 8 8 119.083 8 256c0 136.997 111.043 248 248 248s248-111.003 248-248C504 119.083 392.957 8 256 8zm0 110c23.196 0 42 18.804 42 42s-18.804 42-42 42-42-18.804-42-42 18.804-42 42-42zm56 254c0 6.627-5.373 12-12 12h-88c-6.627 0-12-5.373-12-12v-24c0-6.627 5.373-12 12-12h12v-64h-12c-6.627 0-12-5.373-12-12v-24c0-6.627 5.373-12 12-12h64c6.627 0 12 5.373 12 12v100h12c6.627 0 12 5.373 12 12v24z"/></svg>'
      + '<div class="content">'
      + code
      + '</div></div>'
  }

  if (lang === 'warningBlock') {
    return '<div class="warningBlock">'
      + '<svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" ><path d="M569.517 440.013C587.975 472.007 564.806 512 527.94 512H48.054c-36.937 0-59.999-40.055-41.577-71.987L246.423 23.985c18.467-32.009 64.72-31.951 83.154 0l239.94 416.028zM288 354c-25.405 0-46 20.595-46 46s20.595 46 46 46 46-20.595 46-46-20.595-46-46-46zm-43.673-165.346l7.418 136c.347 6.364 5.609 11.346 11.982 11.346h48.546c6.373 0 11.635-4.982 11.982-11.346l7.418-136c.375-6.874-5.098-12.654-11.982-12.654h-63.383c-6.884 0-12.356 5.78-11.981 12.654z"/></svg>'
      + '<div class="content">'
      + code
      + '</div></div>'
  }

  return '<div class="code-block-' + index + ' tab-content" lang="' + escape(lang) + '" >'
    + '<code><pre>'
    + (escaped ? highlighted : escape(code))
    + '</pre></code>'
    + '</div>'
}

module.exports = markdownRenderer