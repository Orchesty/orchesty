import React from 'react'

export default (WrappedComponent) => {

  class FilterElement extends React.Component {
    constructor(props) {
      super(props);
    }

    render() {
      const {icon, ...passProps} = this.props;
      const {label} = this.props;
      return (
        <div className="form-group col-md-2 col-sm-3 col-xs-12">
          <label className="control-label">{label}</label>
          <div className="input-prepend input-group">
            <span className="add-on input-group-addon"><i className={icon} /></span>
            <WrappedComponent {...passProps} />
          </div>
        </div>
      );
    }
  }

	FilterElement.displayName = `FilterElement(${WrappedComponent.displayName || WrappedComponent.name || 'Component'})`;
  
  return FilterElement;
}

