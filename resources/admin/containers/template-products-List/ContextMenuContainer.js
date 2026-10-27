import React from 'react';
import { ContextMenu, MenuItem } from 'react-contextmenu';

const ContextMenuContainer = ({ onContextMenu, onContextMenuClick }) => {
  return (
    <ContextMenu id="menu_id" onShow={(e) => onContextMenu(e, e.detail.data)}>
      <MenuItem onClick={onContextMenuClick} data={{ action: 'awaited' }}>
        <i className="simple-icon-docs" /> <span>در انتظار تأیید</span>
      </MenuItem>
      <MenuItem onClick={onContextMenuClick} data={{ action: 'approve' }}>
        <i className="simple-icon-drawer" /> <span>تأیید شده</span>
      </MenuItem>
      <MenuItem onClick={onContextMenuClick} data={{ action: 'reject' }}>
        <i className="simple-icon-trash" /> <span>رد شده</span>
      </MenuItem>
    </ContextMenu>
  );
};

export default ContextMenuContainer;
