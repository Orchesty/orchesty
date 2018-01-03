import React from 'react'
import PropTypes from 'prop-types';

export default (WrappedComponent, parameters) => {

  class BasicFormElement extends React.Component {
    constructor(props) {
      super(props);
    }

    render() {
      const {marginTop, ...passProps} = this.props;
      const {label, meta: {error, touched}} = this.props;
      const style = {marginTop};
      return (
        <div className="form-group">
          <label>{label}</label>
          <WrappedComponent {...passProps} />
          {
            touched && error &&
            <ul className="parsley-errors-list filled">
              <li>{error}</li>
            </ul>
          }
        </div>
      );
    }
  }

  BasicFormElement.displayName = `BasicFormElement(${WrappedComponent.displayName || WrappedComponent.name || 'Component'})`;

  BasicFormElement.defaultProps = Object.assign({}, parameters);

  BasicFormElement.propTypes = {
    marginTop: PropTypes.string
  };

  return BasicFormElement;
}


