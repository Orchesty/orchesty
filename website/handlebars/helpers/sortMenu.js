module.exports = function (collection) {
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
        children: !menu[item.name] ? [] : (menu[item.name].children ? menu[item.name].children : []),
      };

      if (item.parent) {
        if (!menu[item.parent]) menu[item.parent] = {};
        if (!menu[item.parent].children) menu[item.parent].children = [];

        menu[item.parent].children.push(obj);
      } else {
        menu[item.name] = obj;
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