const isMenuActive = (currentPath, itemPath) => {
  if (currentPath === itemPath) {
    return 'active';
  }

  return '';
};

module.exports = isMenuActive