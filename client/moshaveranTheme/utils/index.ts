import { useEffect } from "react";
import { SwiperOptions } from "swiper";
import { op } from "./opacity";
export * as img from "../assets/images/export";
import paths from "./paths.json";
import urls from "./urls.json";

export { paths, urls };

export const loginCodeTiem = 120;

export const showTimer = (time: number) => {
  let min: string | number = Math.floor(time / 60);
  min = min < 10 ? "0" + min : min;
  let sec: string | number = time % 60;
  sec = sec < 10 ? "0" + sec : sec;
  return `${min}:${sec}`;
};

export const colorOp = (color?: string, opacity: keyof typeof op = "10%") =>
  color + op[opacity];

export const useOutsideClick = (ref: any, callback: any) => {
  const handleClick = (e: any) => {
    if (ref.current && !ref.current.contains(e.target)) {
      callback();
    }
  };
  useEffect(() => {
    document.addEventListener("click", handleClick);
    return () => {
      document.removeEventListener("click", handleClick);
    };
  });
};

export const getStore = <T extends unknown>(key: string) =>
  typeof window !== "undefined" && (localStorage.getItem(key) as T);
export const removeStore = (key: string) =>
  typeof window !== "undefined" && localStorage.removeItem(key);
export const setStore = <T extends unknown>(key: string, value: T) =>
  typeof window !== "undefined" &&
  localStorage.setItem(key, JSON.stringify(value));

export const numberRegex = /^\d*$/;

export const isNumber = (value: any) => numberRegex.test(value);

export const deepCopy = <T extends unknown>(value: T) =>
  JSON.parse(JSON.stringify(value)) as T;

export const getFromOjectByKey = (o: { [key: string]: any }, s: string) => {
  s = s.replace(/\[(\w+)\]/g, ".$1"); // convert indexes to properties
  s = s.replace(/^\./, ""); // strip a leading dot
  var a = s.split(".");

  for (var i = 0, n = a.length; i < n; ++i) {
    var k = a[i];
    if (typeof o === "object" && k in o) {
      o = o[k];
    } else {
      return;
    }
  }
  return o;
};

export const appName = "مشاوران";

export const breakpoints: {
  [width: number]: SwiperOptions;
  [ratio: string]: SwiperOptions;
} = {
  1: {
    slidesPerView: 1,
  },
  768: {
    slidesPerView: 2,
  },
  1024: {
    slidesPerView: 4,
  },
};

export const makeNumberTwoDigit = (value?: string | number) => {
  if (value?.toString().length === 1) return "0" + value;
  return value;
};

export const renderHtml = (value?: string) =>
  (value || "")
    ?.replaceAll("font-family", "")
    .replaceAll("background-color", "") || "";

export const imgUrl = (url?: string) =>
  `${process.env.NEXT_PUBLIC_STORAGE_URL}/${url}`;

export const validateEmail = (email: string) => {
  return email.match(
    /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
  );
};


export const addCountryCode=(mobile:string)=>{
  let newMobile = mobile;
  if(newMobile.startsWith("0"))
  newMobile= newMobile.substring(1);
  newMobile = "+98"+newMobile;
  return newMobile
}