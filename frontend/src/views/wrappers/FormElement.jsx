import React from 'react'
import PropTypes from 'prop-types';

export default (WrappedComponent, parameters) => {

  class FormElement extends React.Component {
    constructor(props) {
      super(props);
    }

    render() {
      const {marginTop, ...passProps} = this.props;
      const {label, meta: {error, touched}} = this.props;
      const style = {marginTop};
      return (
        <div className="form-group">
          <label className="control-label col-md-3 col-sm-3 col-xs-12">{label}</label>
          <div className="col-md-9 col-sm-9 col-xs-12" style={style}>
            <WrappedComponent {...passProps} />
            {
              touched && error &&
              <ul className="parsley-errors-list filled">
                <li>{error}</li>
              </ul>
            }
          </div>
        </div>
      );
    }
  }

  FormElement.displayName = `FormElement(${WrappedComponent.displayName || WrappedComponent.name || 'Component'})`;

  FormElement.defaultProps = Object.assign({}, parameters);

  FormElement.propTypes = {
    marginTop: PropTypes.string
  };

  return FormElement;
}

