import React from 'react'
import PropTypes from 'prop-types';

class GeneralSearch extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    return (
      <div className="form-group top_search">
        <div className="input-group">
          <input type="text" className="form-control" placeholder="Search for..." />
          <span className="input-group-btn">
            <button className="btn btn-default" type="button">Go!</button>
          </span>
        </div>
      </div>
    );
  }
}

GeneralSearch.propTypes = {};

export default GeneralSearch;