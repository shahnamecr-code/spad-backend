export interface RegisterReqData {
  first_name: string;
  last_name: string;
  email: string;
  mobile: string;
  password: string;
}

export interface LoginReqData {
  mobile: string;
  code: string;
}

export interface LoginWithPassReqData {
  mobile: string;
  password: string;
}
export interface IUserInformation {
  message: string;
  status: boolean;
  token: string;
  redirect?: boolean;
  user: {
    created_at?: string;
    email?: string;
    email_verified_at?: string | null;
    first_name?: string;
    full_name?: string;
    id?: number;
    last_name?: string;
    mobile?: string;
    phone_verified_at?: string | null;
    status?: number;
    updated_at?: string;
  };
}

export interface IUpdateProfile {
  first_name?: string;
  last_name?: string;
  password?: string;
}

export interface IUpdatePass {
  password?: string;
  password_confirmation?: string;
}

export interface IUpdatePassErrorMsg {
  password?: string[];
  password_confirmation?: string[];
}

export interface IUpdateProfileErrorMsg {
  first_name?: string[];
  last_name?: string[];
  email?: string[];
}
