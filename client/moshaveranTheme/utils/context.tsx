import {
  createContext,
  useContext,
  ReactNode,
  useState,
  useEffect,
} from "react";
import { appName, getStore } from ".";
import {
  ILocationStore,
  ISearchLocation,
} from "../components/layout/cityPopup/cityPopup.dev";
import { getCategoriesV3 } from "../services/AdsServices";
import { getAllFavorites } from "../services/FavoritesServices";
import { getOptions } from "../services/MainPageServices";
import { token } from "../services/sevices";
import { IUserInformation } from "../types/auth.types";
import { ICategory, ILocation } from "../types/createAd.types";
import { IOptions } from "../types/mainPage.types";
import { FAVORITES, LOCATION, REGION, USER } from "./stores";

export type AppContextType = {
  provinces: ISearchLocation[];
  setProvinces: (value: ISearchLocation[]) => void;
  options: IOptions;
  regions: ILocation[];
  setRegions: (value: ILocation[]) => void;
  favorites: number[];
  setFavorites: (value: number[]) => void;
  categories: ICategory[];
  setCategories: (value: ICategory[]) => void;
  labels: { label: string }[];
  setLabels: (value: { label: string }[]) => void;
  getCategories: () => void;
  user: IUserInformation["user"];
  setUser: (value: IUserInformation["user"]) => void;
  location: ILocationStore;
  setLocation: (value: ILocationStore) => void;
};

const contextDefaultValues: AppContextType = {
  provinces: [],
  setProvinces: () => {},
  options: { general_site_name: appName },
  regions: [],
  setRegions: () => {},
  favorites: [],
  setFavorites: () => {},
  categories: [],
  setCategories: () => {},
  labels: [],
  setLabels: () => {},
  getCategories: () => {},
  user: {},
  setUser: () => {},
  location: {},
  setLocation: () => {},
};

export const AppContext = createContext<AppContextType>(contextDefaultValues);

export function useAppContext() {
  return useContext(AppContext);
}

type Props = {
  children: ReactNode;
};

export function ContextProvider({ children }: Props) {
  const [provinces, setProvinces] = useState<ISearchLocation[]>([]);
  const [options, setOptions] = useState<IOptions>({ general_site_name: "" });
  const [regions, setRegions] = useState<ILocation[]>([]);
  const [favorites, setFavorites] = useState<number[]>(
    JSON.parse(getStore(FAVORITES) || "[]") as number[]
  );
  const [categories, setCategories] = useState<ICategory[]>([]);
  const [labels, setLabels] = useState<{ label: string }[]>([]);
  const [user, setUser] = useState<IUserInformation["user"]>({});
  const [location, setLocation] = useState<ILocationStore>({});

  useEffect(() => {
    token() && getAllFavorites().then((res) => setFavorites(res.data));
  }, []);

  useEffect(() => {
    const user = JSON.parse(getStore(USER) || "{}") as IUserInformation["user"];
    setUser(user);
    const location = JSON.parse(getStore(LOCATION) || "{}") as ILocationStore;
    setLocation(location);
    const region = JSON.parse(getStore(REGION) || "[]") as ILocation[];
    setRegions(region);
  }, []);

  useEffect(() => {
    getOptions().then((res) => setOptions(res.data || {}));
  }, []);

  const getCategories = () => {
    if (!categories.length)
      getCategoriesV3().then((res) => {
        setCategories(res.data.data);
        setLabels(res.data.levels || []);
      });
  };

  const value = {
    provinces,
    setProvinces,
    options,
    regions,
    setRegions,
    favorites,
    setFavorites,
    categories,
    setCategories,
    labels,
    setLabels,
    getCategories,
    user,
    setUser,
    location,
    setLocation,
  };

  return (
    <>
      <AppContext.Provider value={value}>{children}</AppContext.Provider>
    </>
  );
}

export const LoadingContext = createContext<{
  loading: boolean;
  setLoading: (value: boolean) => void;
}>({
  loading: true,
  setLoading: () => {},
});

export function useLoadingContext() {
  return useContext(LoadingContext);
}
