import React from 'react'
import PropTypes from 'prop-types';

import ActionButton  from './ActionButton';

import './ActionButtonPanel.less';

class ActionButtonPanel extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    const {items, ...passProps} = this.props;
    if (items) {
      const buttons = items.map((item, index) => <ActionButton key={index} item={item} {...passProps}/>);
      return (
        <div className="action-button-panel">
          {buttons}
        </div>
      );
    } else {
      return null;
    }
  }
}

ActionButtonPanel.defaultProps = {
  size: 'sm',
  right: false
};

ActionButtonPanel.propTypes = {
  items: PropTypes.array,
  size: PropTypes.string,
  right: PropTypes.bool,
  buttonClassName: PropTypes.oneOfType([PropTypes.string, PropTypes.func]),
};

export default ActionButtonPanel;