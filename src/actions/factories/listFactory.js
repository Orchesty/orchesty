import * as types from '../../baseActionTypes';
import {listType} from '../../types';

export default prefix => ({
  createPaginationList: (id, pageSize, local = false, sort, filter, page = 0) => ({
    type: prefix + types.LIST_CREATE,
    id,
    listType: listType.PAGINATION,
    local,
    pageSize,
    sort,
    filter,
    page
  }),
  createRelationList: (id, objectType, objectId) => ({
    type: prefix + types.LIST_CREATE,
    id,
    listType: listType.RELATION,
    objectType,
    objectId
  }),
  createCompleteList: (id, local = false, sort, filter) => ({
    type: prefix + types.LIST_CREATE,
    id,
    listType: listType.COMPLETE,
    local,
    sort,
    filter
  }),
  listLoading: id => ({
    type: prefix + types.LIST_LOADING,
    id
  }),
  listError: id => ({
    type: prefix + types.LIST_ERROR,
    id
  }),
  listReceive: (id, data) => ({
    type: prefix + types.LIST_RECEIVE,
    id,
    data
  }),
  listDelete: id => ({
    type: prefix + types.LIST_DELETE,
    id
  }),
  listChangeSort: (id, sort) => ({
    type: prefix + types.LIST_CHANGE_SORT,
    id,
    sort
  }),
  listChangePage: (id, page) => ({
    type: prefix + types.LIST_CHANGE_PAGE,
    id: id,
    page
  }),
  listChangeFilter: (id, filter) => ({
    type: prefix + types.LIST_CHANGE_FILTER,
    id: id,
    filter
  }),
  invalidateLists: (objectType, objectId) => ({
    type: prefix + types.LIST_INVALIDATE,
    objectType,
    objectId
  })
});