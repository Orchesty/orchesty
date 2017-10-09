export function isInteger(value){
  return /^\s*-?\d+$/.exec(value);
}

export function isJSON(value){
  try{
    JSON.parse(value);
    return true;
  } catch (e){
    return false;
  }
}