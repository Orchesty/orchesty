const sortMenu = (collection) => {
  let menu = {};
  let sortedMenu = [];

  collection.forEach(item => {
    if (item.menu_exclude === true) {
      return;
    }

    let obj = {
      name: item.name,
      level: item.level,
      path: item.path,
      index: item.index,
      lang: getLang(item),
      children: !menu[getIndexName(item)] ? [] : (menu[getIndexName(item)].children ? menu[getIndexName(item)].children : []),
    };

    if (item.parent) {
      if (!menu[getIndexName(item, item.parent)]) menu[getIndexName(item, item.parent)] = {};
      if (!menu[getIndexName(item, item.parent)].children) menu[getIndexName(item, item.parent)].children = [];

      menu[getIndexName(item, item.parent)].children.push(obj);
    } else {
      menu[getIndexName(item)] = obj;
    }
  }
  );

  for (const name in menu) {
    menu[name].children.sort(sortByIndex);
    sortedMenu.push(menu[name])
  }

  sortedMenu.sort(sortByIndex);

  return sortedMenu;
};

function sortByIndex(a, b) {
  if (b.index > a.index) return -1;
  if (a.index > b.index) return 1;
  return 0;
}

function getIndexName(item, name) {
  if (name) {
    return name + getLang(item);
  }

  return item.name + getLang(item);
}

function getLang(item) {
  return item.lang == null ? 'cs' : item.lang;
}

module.exports = sortMenu