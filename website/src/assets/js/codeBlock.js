// Click to select whole snippet - script
let pres = document.querySelectorAll('pre,kbd,blockquote');
for (let i = 0; i < pres.length; i++) {
  pres[i].addEventListener('click', function () {
    let selection = getSelection();
    let range = document.createRange();
    range.selectNodeContents(this);
    document.execCommand('copy');
    selection.removeAllRanges();
    selection.addRange(range);
  }, false);
}