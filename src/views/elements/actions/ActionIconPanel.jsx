import React from 'react'
import PropTypes from 'prop-types';
import ActionIcon from 'rootApp/views/elements/actions/ActionIcon';

import './ActionIconPanel.less';

class ActionIconPanel extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    const {items, ...passProps} = this.props;
    const iconItems = items.map((item, index) => <ActionIcon key={index} item={item} {...passProps}/>);
    return (
      <div className="action-icon-panel">
        {iconItems}
      </div>
    );
  }
}

ActionIconPanel.propTypes = {
  items: PropTypes.array
};

export default ActionIconPanel;