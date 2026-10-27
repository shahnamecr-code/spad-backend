import styles from "./favorites.module.scss";
import { useEffect, useState } from "react";
import { IAd } from "../../../../types/ads.types";
import { IPagingResponse } from "../../../../types/general.types";
import { useAppContext, useLoadingContext } from "../../../../utils/context";
import Paging from "../../../general/paging/Paging";
import FavoriteAdCard from "./favoriteAdCard/FavoriteAdCard";
import {
  getFavorites,
  getCustomAds,
  removeFavorite,
  getAllFavorites,
  removeAllFavorite,
} from "../../../../services/FavoritesServices";
import { token } from "../../../../services/sevices";
import Button from "../../../general/button/Button";
import { removeStore } from "../../../../utils";
import { FAVORITES } from "../../../../utils/stores";

const Favorites = () => {
  const [favorites, setFavorites] = useState<IPagingResponse<IAd>>();
  const loadingContext = useLoadingContext();
  const context = useAppContext();

  useEffect(() => {
    getPagingFavorites();
  }, []);

  const getPagingFavorites = (url?: string) => {
    loadingContext.setLoading(true);
    if (token())
      getFavorites(url)
        .then((res) => setFavorites(res.data))
        .finally(() => loadingContext.setLoading(false));
    else
      getCustomAds(url, context.favorites)
        .then((res) => setFavorites(res.data))
        .finally(() => loadingContext.setLoading(false));
  };

  const deleteFromFavorite = (id: number) => {
    removeFavorite(id).then((res) => {
      if (res.status === 200) {
        getPagingFavorites();
        getAllFavorites().then((res) => context.setFavorites(res.data));
      }
    });
  };

  const deleteAllFavorite = () => {
    removeAllFavorite().then((res) => {
      if (res.status === 200) {
        setFavorites({ data: [] });
        context.setFavorites([]);
        removeStore(FAVORITES);
      }
    });
  };

  return (
    <div className={styles.container}>
      {favorites?.data?.length === 0 ? (
        !loadingContext.loading && (
          <span className={styles["not-exist"]}>(اطلاعاتی وجود ندارد)</span>
        )
      ) : (
        <>
          <Button onClick={deleteAllFavorite}>حذف همه</Button>
          <div className={styles["ads-container"]}>
            {favorites?.data?.map((item, i) => (
              <FavoriteAdCard
                key={i}
                adInfo={item}
                onClickDelete={deleteFromFavorite}
              />
            ))}
          </div>
        </>
      )}
      <Paging
        data={favorites?.meta}
        onClickPage={(link) => {
          getPagingFavorites(link.url || "");
        }}
      />
    </div>
  );
};

export default Favorites;
