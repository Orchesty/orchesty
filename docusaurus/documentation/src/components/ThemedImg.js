import ThemedImage from '@theme/ThemedImage';
import React from 'react';

export default _props => {
  const path = _props.path;
  const i = path.lastIndexOf('.');
  let j = path.lastIndexOf('/');
  if (j > 0) {
    j++;
  }

  let dark = `${path.substr(0, i)}_dark${path.substr(i)}`;
  if (_props.lightOnly) {
    dark = path;
  }

  return (
    <div>
    <ThemedImage
      {..._props}
      alt={_props.alt || path.substr(j, i - j - 2)}
      sources={{
        light: path,
        dark,
      }}
    />
    </div>
  );
}
