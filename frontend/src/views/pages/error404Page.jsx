import React from 'react'

class Error404Page extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    return (
      <div className="right_col" role="main">
        <div className="main-page">
          Page not found.
        </div>
      </div>
    );
  }
}

export default Error404Page;