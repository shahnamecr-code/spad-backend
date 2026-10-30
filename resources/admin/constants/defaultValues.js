export const UserRole = {
  Admin: "Super Admin",
  Manager: "manager",
  Supervisor: "supervisor",
  Expert: "expert",
};

/*
Menu Types:
"menu-default", "menu-sub-hidden", "menu-hidden"
*/
export const defaultMenuType = "menu-default";
export const companyOwnership = true;
export const hasCrm = true;

export const subHiddenBreakpoint = 1440;
export const menuHiddenBreakpoint = 768;
export const defaultLocale = "en";
export const localeOptions = [
  { id: "en", name: "English - LTR", direction: "ltr" },
  { id: "es", name: "Español", direction: "ltr" },
  { id: "enrtl", name: "English - RTL", direction: "rtl" },
];

export const firebaseConfig = {
  apiKey: "AIzaSyBBksq-Asxq2M4Ot-75X19IyrEYJqNBPcg",
  authDomain: "gogo-react-login.firebaseapp.com",
  databaseURL: "https://gogo-react-login.firebaseio.com",
  projectId: "gogo-react-login",
  storageBucket: "gogo-react-login.appspot.com",
  messagingSenderId: "216495999563",
};

export const currentUser = {
  id: 1,
  title: "SPAD",
  img: "/assets/img/profiles/1-8.jpg",
  date: "Last seen today 15:24",
  role: UserRole.Admin,
};

export const adminRoot = "/spad-admin";
export const siteUrl = "https://spad.estate";
export const uploadPath = "/storage";
export const defaultMainImage = "/images/default/unknown-product.png";
export const buyUrl = "https://spad.estate";
export const searchPath = `${adminRoot}/pages/miscellaneous/search`;
export const servicePath = "https://api.coloredstrategies.com";
export const adminPathApi = "/api/admin";

export const themeColorStorageKey = "__theme_selected_color";
export const isMultiColorActive = false;
export const defaultColor = "light.spad";
export const isDarkSwitchActive = false;
export const defaultDirection = "rtl";
export const themeRadiusStorageKey = "__theme_radius";
export const isAuthGuardActive = true;
export const colors = [
  "bluenavy",
  "blueyale",
  "blueolympic",
  "greenmoss",
  "greenlime",
  "purplemonster",
  "orangecarrot",
  "redruby",
  "yellowgranola",
  "greysteel",
];
