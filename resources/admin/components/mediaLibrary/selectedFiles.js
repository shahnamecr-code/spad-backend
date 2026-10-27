import React from "react";
import { Card } from "reactstrap";
import { renderFileImage } from "./utils";

export const SelectedFiles = ({ files, onDelete }) => {
  return files && files.map((file) => {
    return (
      <div
        className="selected-file"
        onClick={() => onDelete(file.id)}
        key={file.id}
      >
        <Card className={"selected-file__file-card"}>
          {renderFileImage(file)}
          <span className={"selected-file__file-name"}>{file.name}</span>
        </Card>
        <span className="simple-icon-trash selected-file__remove" />
      </div>
    );
  });
};
