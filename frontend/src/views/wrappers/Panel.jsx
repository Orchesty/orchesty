import React from 'react';
import PropTypes from 'prop-types';
import {connect} from 'react-redux';
import * as panelActions from 'rootApp/actions/panelActions';

import './Panel.less';
import ActionIconPanel from 'rootApp/views/elements/actions/ActionIconPanel';
import {menuItemType} from 'rootApp/types';

function mapStateToProps(state, ownProps){
  const {panel} = state;
  return {
    visible: panel[ownProps.componentKey] !== false
  }
}

function mapActionsToProps(dispatch, ownProps){
  return {
    toggleVisible: forced => dispatch(panelActions.togglePanel(ownProps.componentKey))
  }
}

const connectPanel = connect(mapStateToProps, mapActionsToProps);

export default (WrappedComponent, parameters) => {
	class Panel extends React.Component {
		constructor(props){
			super(props);
			this.setActions = this.setActions.bind(this);
			this.setPanelActions = this.setPanelActions.bind(this);
      this.state = {
        actions: [],
	      panelActions: [],
      };
		}

		setActions(actions){
		  this.setState({actions});
    }

    setPanelActions(panelActions){
		  this.setState({panelActions});
    }

		render() {
			const {title, subTitle, icon, HeaderComponent, visible, toggleVisible, middleHeader, noActions, noHide, ...passProps} = this.props;
			const {actions, panelActions} = this.state;
			const allActions = noHide ? [...actions, ...panelActions] : [...actions, ...panelActions, {
			  icon: 'fa fa-chevron-' + (visible ? 'up' : 'down'),
        caption: visible ? 'Hide' : 'Show',
        action: toggleVisible,
        type: menuItemType.ACTION
      }];
      const actionProps = noActions ? {} : {setActions: this.setActions};
			return (
				<div className={'x_panel' + (visible ? '' : ' closed')}>
					<div className="x_title">
						<h2>{icon && <span className={icon} aria-hidden="true" />} {title}
							{subTitle && <small>{subTitle}</small>}
						</h2>
						{middleHeader}
						{allActions.length > 0 && <ActionIconPanel items={allActions}/>}
            {HeaderComponent && <HeaderComponent last={allActions.length === 0} {...passProps}/>}
						<div className="clearfix" />
					</div>
					{(visible || noHide) && <div className="x_content"><WrappedComponent {...passProps} setPanelActions={this.setPanelActions} {...actionProps}/></div>}
				</div>
			);
		}
	}

	Panel.defaultProps = {
		noActions: false,
		noHide: false,
		...parameters
	};

	Panel.propTypes = {
		componentKey: PropTypes.string.isRequired,
		title: PropTypes.string.isRequired,
		subTitle: PropTypes.string,
		icon: PropTypes.string,
		HeaderComponent: PropTypes.func,
    toggleVisible: PropTypes.func.isRequired,
		noActions: PropTypes.bool.isRequired,
		noHide: PropTypes.bool.isRequired,
	};

	Panel.displayName = `Panel(${WrappedComponent.displayName || WrappedComponent.name || 'Component'})`;

	return connectPanel(Panel);
}

