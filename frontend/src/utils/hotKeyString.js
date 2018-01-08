export default rec => {
  if (rec) {
    const items = [];
    if (rec.ctrl) {
      items.push('Ctrl');
    }
    if (rec.alt) {
      items.push('Alt');
    }
    if (rec.shift) {
      items.push('Shift');
    }
    items.push(rec.char);
    return items.join(' + ');
  } else {
    return ''
  }
}