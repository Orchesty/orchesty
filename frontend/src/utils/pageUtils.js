import config from 'rootApp/config';

export function getPageId(key, args){
  const page = config.pages[key];
  return page ? typeof page.id == 'function' ? page.id(args) : (typeof page.id == 'string' ? page.id : page.key) : null;
}

export function getPageArgs(key, args){
  const page = config.pages[key];
  if (page && page.defaultArgs){
    return Object.assign({}, page.defaultArgs, args);
  } else {
    return args;
  }
}