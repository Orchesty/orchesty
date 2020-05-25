function ShowMenuItem(lang) {
  if (!lang) {
    lang = GetCookie('pipes-docs-lang');
  }
  if (lang === '') {
    lang = 'cs';
  }

  document.querySelectorAll('.generated-nav').forEach(
    function (menu) {
      menu.querySelectorAll('li').forEach(
        function (item) {
          if (item.getAttribute('lang') === lang) {
            item.style.display = 'flex';
          } else {
            item.style.display = 'none';
          }
        }
      );
    });
}

function SwitchLanguage(lang) {
  SetCookie('pipes-docs-lang', lang, 365);
  ShowMenuItem(lang);
}