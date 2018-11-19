import React from 'react'
import {connect} from 'react-redux';

import * as contextMenus from 'components/contextMenus';

const ActiveContextMenu = ({contextMenu}) => contextMenu && contextMenus[contextMenu.menuKey] ? React.createElement(contextMenus[contextMenu.menuKey], contextMenu.args) : null;
ActiveContextMenu.displayName = 'ActiveContextMenu';

const mapStateToProps = ({application: {contextMenu}}) => ({contextMenu});

export default connect(mapStateToProps)(ActiveContextMenu);