let compares = {
  string: (a, b) => a.localeCompare(b),
  number: (a, b) => a - b,
  boolean: (a, b) => a - b
};

export default sort => {
  const {key, type} = sort;
  return (a, b) => {
    return compares[typeof a[key]](type == 'asc' ? a[key] : b[key], type == 'asc' ? b[key] : a[key]);
  }
}