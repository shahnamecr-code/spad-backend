import React from "react";
import PdfImage from "../../assets/img/pdf.png";
import ZipImage from "../../assets/img/zip.png";
import XslxImage from "../../assets/img/xlsx.png";
import UnknownImage from "../../assets/img/unknown.png";

const image = (src) => <img src={src || UnknownImage} alt="" className="file-manager__file-image"
  onError={event => {
    if (event.currentTarget.src !== window.location.origin + UnknownImage) {
      event.currentTarget.onerror = null;
      event.currentTarget.src = UnknownImage;
    }
  }} />;

export const renderFileImage = (file) => {
  const kind = String(file?.type || "").toLowerCase();
  if (["image/jpeg", "image/jpg", "image/png", "image/webp", "image/gif", "image/avif"].includes(kind)) {
    return image(file?.url);
  }
  if (kind === "application/pdf") return image(PdfImage);
  if (kind.includes("zip") || kind.includes("rar") || kind.includes("gzip")) return image(ZipImage);
  if (kind.includes("spreadsheet") || kind.includes("excel")) return image(XslxImage);
  // PHP, scripts and other files must never be treated as image URLs.
  return image(UnknownImage);
};
