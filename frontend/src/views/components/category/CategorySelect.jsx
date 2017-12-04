import React from 'react'
import PropTypes from 'prop-types';
import CategoryTreeView from './CategoryTreeView';

class CategorySelect extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    const {componentKey} = this.props;
    return <CategoryTreeView componentKey={componentKey} />
  }
}

CategorySelect.propTypes = {};

export default CategorySelect;