import { useEffect, useState } from "react";
import { toast } from "react-toastify";
import {
  removeFavorite,
  addFavorites,
} from "../../../services/FavoritesServices";
import { token } from "../../../services/sevices";
import { deepCopy, setStore } from "../../../utils";
import { useAppContext, useLoadingContext } from "../../../utils/context";
import { FAVORITES } from "../../../utils/stores";

export const useFavoriteDev = (id: number) => {
  const { favorites, setFavorites } = useAppContext();
  const [favorite, setFavorite] = useState(false);
  const { setLoading } = useLoadingContext();

  useEffect(() => {
    checkIsFavorite();
  }, []);

  const checkIsFavorite = () => {
    if (favorites instanceof Array && favorites.includes(id)) setFavorite(true);
    else setFavorite(false);
  };

  const toggleFavorite = () => {
    if (favorite) removeFromFavorites();
    else addToFavorites();
  };

  const removeFromFavorites = async () => {
    let res = true;
    if (token()) {
      setLoading(true);
      res = await removeFavorite(id).then((res) => res.status === 200);
      setLoading(false);
    }
    if (res) {
      setFavorite(false);
      let arr = deepCopy(favorites);
      arr = arr?.filter((x) => x !== id);
      setStore(FAVORITES, arr);
      setFavorites(arr);
    }
  };

  const addToFavorites = async () => {
    if (favorites.length === 100)
      return toast.error(
        "جهت افزودن بیش از 100 آگهی به علاقه مندی ها، لطفا وارد حساب کاربری خود شوید."
      );
    let arr = deepCopy(favorites) || [];
    arr = [id, ...arr];
    let res = true;
    if (token()) {
      setLoading(true);
      res = await addFavorites([id]).then((res) => res.status === 200);
      setLoading(false);
    }
    if (res) {
      setFavorite(true);
      setStore(FAVORITES, arr);
      setFavorites(arr);
    }
  };

  return { checkIsFavorite, toggleFavorite, favorite };
};
