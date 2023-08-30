export const listType = {
  RELATION: 'relation',
  PAGINATION: 'pagination',
  COMPLETE: 'complete',
};

export const stateType = {
  NOT_LOADED: 'not_loaded',
  LOADING: 'loading',
  SUCCESS: 'success',
  ERROR: 'error',
};

export const filterType = {
  EXACT: 'exact',
  EXACT_NULL: 'exact_null',
  SEARCH: 'search',
  BOOLEAN: 'boolean_str',
};

export const menuItemType = {
  SUB_MENU: 'sub_menu',
  ACTION: 'action',
  SEPARATOR: 'separator',
  BADGE: 'badge',
  TEXT: 'text',
};

export const intervalType = {
  HOUR: { caption: 'hour', value: '1h' },
  DAY: { caption: 'day', value: '1d' },
  WEEK: { caption: 'week', value: '1w' },
  FOUR_WEEK: { caption: '4 weeks', value: '4w' },
};
