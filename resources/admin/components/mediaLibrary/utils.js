import PdfImage from "../../assets/img/pdf.png";
import ZipImage from "../../assets/img/zip.png";
import XslxImage from "../../assets/img/xlsx.png";
import UnknownImage from "../../assets/img/unknown.png";

export const renderFileImage = (file) => {
  switch (file.type) {
    case "image/jpeg":
    case "image/jpg":
    case "image/png":
      return <img src={file.url} className="file-manager__file-image" />;
    case "application/pdf":
      return <img src={PdfImage} className="file-manager__file-image" />;
    case "application/x-rar-compressed":
    case "application/x-rar":
    case "application/zip":
    case "application/gzip":
      <img src={ZipImage} className="file-manager__file-image" />;
    case "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet":
      return <img src={XslxImage} className="file-manager__file-image" />;
    default:
      if (file.url)
        return <img src={file.url} className="file-manager__file-image" />;
      else
        return <img src={UnknownImage} className="file-manager__file-image" />;
  }
};
