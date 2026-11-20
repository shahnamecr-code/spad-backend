import axios from "axios";
import { adminPathApi } from "../../../constants/defaultValues";

export async function request(method, path, data, options) {
  const response = await axios({ ...options, method, url: adminPathApi + path, data });
  return response.data;
}

export function actionError(error, fallback) {
  const errors = error?.response?.data?.errors;
  const firstError = errors && Object.values(errors).flat().find(Boolean);
  return firstError || error?.response?.data?.message || fallback;
}
