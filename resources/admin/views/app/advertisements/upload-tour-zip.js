import axios from "axios";
import { adminPathApi } from "../../../constants/defaultValues";

const bytesLabel = (size) => {
  if (!Number.isFinite(size)) return "۰ بایت";
  if (size < 1024) return Math.round(size).toLocaleString("fa-IR") + " بایت";
  if (size < 1048576) return (size / 1024).toFixed(1) + " کیلوبایت";
  return (size / 1048576).toFixed(1) + " مگابایت";
};

export { bytesLabel };

export const uploadTourZip = (advertisementId, file, onProgress) => {
  if (!advertisementId) return Promise.reject(new Error("کد آگهی مشخص نیست."));
  if (!file || !/\.zip$/i.test(file.name)) return Promise.reject(new Error("فایل ZIP معتبر انتخاب کنید."));
  if (file.size > 512 * 1024 * 1024) return Promise.reject(new Error("حجم فایل ZIP نمی‌تواند بیش از ۵۱۲ مگابایت باشد."));
  const data = new FormData();
  data.append("file", file, file.name);
  if (onProgress) onProgress({ phase: "upload", percent: 0, loaded: 0, total: file.size });
  return axios.post(`${adminPathApi}/virtual-tours/property/${encodeURIComponent(advertisementId)}/zip`, data, {
    headers: { "Content-Type": "multipart/form-data" },
    timeout: 2700000,
    onUploadProgress: (event) => {
      if (!onProgress) return;
      const total = file.size;
      const loaded = Math.min(total, Number(event.loaded || 0));
      const percent = Math.max(0, Math.min(100, Math.round(loaded / Math.max(total, 1) * 100)));
      onProgress({ phase: percent >= 100 ? "processing" : "upload", percent, loaded, total });
    },
  }).then(response => {
    if (!response.data?.status) throw new Error(response.data?.message || "نصب تور آماده ناموفق بود.");
    onProgress?.({ phase: "complete", percent: 100, loaded: file.size, total: file.size, preview_url: response.data?.preview_url });
    return response.data;
  });
};
