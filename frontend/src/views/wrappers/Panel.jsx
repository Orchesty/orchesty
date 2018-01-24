import React from 'react';
import PropTypes from 'prop-types';

export default (WrappedComponent, parameters) => {
	class Panel extends React.Component {
		constructor(props){
			super(props);
			this.state = {
				visible: true
			};
			this.onVisibleClick = this.onVisibleClick.bind(this);
		}

		visibleToggle(){
			this.setState(state => ({visible: !state.visible}));
		}

		onVisibleClick(e){
			e.preventDefault();
			this.visibleToggle();
		}

		render() {
			const {title, subTitle, icon, HeaderComponent, ...passProps} = this.props;
			const {visible} = this.state;
			return (
				<div className="x_panel">
					<div className="x_title">
						<h2><span className={icon} aria-hidden="true" /> {title}
							{subTitle && <small>{subTitle}</small>}
							{HeaderComponent && <HeaderComponent {...passProps}/>}
						</h2>
						<ul className="nav navbar-right panel_toolbox">
							<li><a className=""><i className={'fa fa-chevron-' + (visible ? 'up' : 'down')} onClick={this.onVisibleClick} /></a></li>
						</ul>
						<div className="clearfix" />
					</div>
					{visible && <div className="x_content"><WrappedComponent {...passProps}/></div>}
				</div>
			);
		}
	}

	Panel.defaultProps = {...parameters};

	Panel.propTypes = {
		title: PropTypes.string.isRequired,
		subTitle: PropTypes.string,
		icon: PropTypes.string.isRequired,
		HeaderComponent: PropTypes.func
	};

	Panel.displayName = `Panel(${WrappedComponent.displayName || WrappedComponent.name || 'Component'})`;

	return Panel;
}

