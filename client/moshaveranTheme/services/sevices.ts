import axios from "axios";
import { toast } from "react-toastify";
import { getStore, paths, removeStore } from "../utils";
import { TOKEN, USER } from "../utils/stores";

export const token = () => getStore<string>(TOKEN);

axios.defaults.headers.post["Content-Type"] = "application/json";
axios.defaults.headers.common["Authorization"] = `Bearer ${token()}`;

axios.interceptors.response.use(undefined, (error) => {
  if (error?.response?.status === 401) {
    removeStore(TOKEN);
    removeStore(USER);
    if (window?.location?.pathname) window.location.pathname = paths.login;
  }
  toast.error(
    error?.response
      ? error?.response?.data?.message
      : "مشکلی از سمت سرور رخ داده است."
  );
  return Promise.reject(error);
});

export default {
  get: axios.get,
  post: axios.post,
  put: axios.put,
  delete: axios.delete,
};
