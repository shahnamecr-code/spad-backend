// import { ILoginResponse } from "../types/authenticate.types";
import {
  IUserInformation,
  LoginReqData,
  LoginWithPassReqData,
  RegisterReqData,
} from "../types/auth.types";
import { urls } from "../utils";
import http from "./sevices";

export const sendLoginCode = (data: { mobile: string }) => {
  return http
    .post(`${process.env.NEXT_PUBLIC_API_URL}${urls.sendLoginCode}`, data)
    .then((response) => response.data)
    .catch((err) => err.response.data);
};

export const register = (data: RegisterReqData) => {
  return http
    .post(`${process.env.NEXT_PUBLIC_API_URL}${urls.register}`, data)
    .then((response) => response.data)
    .catch((err) => err.response.data);
};

export const login = (data: LoginReqData): Promise<IUserInformation> => {
  return http
    .post(`${process.env.NEXT_PUBLIC_API_URL}${urls.login}`, data)
    .then((response) => response.data)
    .catch((err) => err.response.data);
};

export const loginWithPass = (data: LoginWithPassReqData): Promise<IUserInformation> => {
  return http
    .post(`${process.env.NEXT_PUBLIC_API_URL}${urls["login-with-pass"]}`, data)
    .then((response) => response.data)
    .catch((err) => err.response.data);
};

// export const login = (data: { mobile: string; code: string }) => {
//   return http
//     .post<ILoginResponse>(
//       `${process.env.NEXT_PUBLIC_API_URL}${urls.login}`,
//       data
//     )
//     .then((res) => res);
// };

export const logout = () => {
  return http
    .get(`${process.env.NEXT_PUBLIC_API_URL}${urls.logout}`)
    .then((res) => res);
};
