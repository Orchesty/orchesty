export default function nestedValue(obj, key) {
  switch (typeof key) {
    case 'function':
      return nestedValue(obj, key());
    case 'string':
      if (key.indexOf('.') >= 0) {
        return nestedValue(obj, key.split('.'));
      }
      return obj[key];

    case 'object':
      if (key.length > 0) {
        return nestedValue(nestedValue(obj, key[0]), key.slice(1));
      }
      return obj;

    default:
      return obj[key];
  }
}
