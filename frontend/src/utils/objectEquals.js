function countProps(obj) {
  let count = 0;
  let k;
  for (k in obj) {
    if (obj.hasOwnProperty(k)) {
      count++;
    }
  }
  return count;
}

function objectEquals(v1, v2) {
  if (typeof (v1) !== typeof (v2)) {
    return false;
  }

  if (typeof (v1) === 'function') {
    return v1.toString() === v2.toString();
  }

  if (v1 instanceof Object && v2 instanceof Object) {
    if (countProps(v1) !== countProps(v2)) {
      return false;
    }
    let r = true;
    let k;
    for (k in v1) {
      r = objectEquals(v1[k], v2[k]);
      if (!r) {
        return false;
      }
    }
    return true;
  }
  return v1 === v2;
}

export default objectEquals;
