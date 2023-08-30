import React from 'react'
import {connect} from 'react-redux';

import Modal from 'wrappers/Modal';
import CategoryForm from 'components/category/CategoryForm';
import processes from 'rootApp/enums/processes';

function mapStateToProps(state, ownProps) {
  const {componentKey, addNew, categoryId} = ownProps;
  return {
    form: componentKey + (addNew ? 'new' : categoryId),
    processId: addNew ? processes.categoryCreate(componentKey) : processes.categoryUpdate(categoryId),
    title: (addNew ? 'New' : 'Edit') + ' category'
  };
}

export default connect(mapStateToProps)(Modal(CategoryForm, {
  size: 'md'
}));