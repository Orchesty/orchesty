import React from 'react';
import Modal from 'wrappers/Modal';
import TopologyCategoryChange from 'components/topology/TopologyCategoryChange';

export default Modal(TopologyCategoryChange, {
  title: 'Change category',
  size: 'md'
});