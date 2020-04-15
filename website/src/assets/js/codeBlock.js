// Click to select whole snippet - script
var pres = document.querySelectorAll('pre,kbd,blockquote');
for (var i = 0; i < pres.length; i++) {
  pres[i].addEventListener('click', function () {
    var selection = getSelection();
    var range = document.createRange();
    range.selectNodeContents(this);
    document.execCommand('copy');
    selection.removeAllRanges();
    selection.addRange(range);
  }, false);
}