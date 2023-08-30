import React from 'react'
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