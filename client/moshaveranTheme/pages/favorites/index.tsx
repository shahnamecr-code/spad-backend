import { useEffect, useState } from "react";
import Button from "../../components/general/button/Button";
import MessageBox from "../../components/general/messageBox/MessageBox";
import Paging from "../../components/general/paging/Paging";
import RenderLinks from "../../components/general/renderLinks/RenderLinks";
import MainLayout from "../../components/layout/mainLayout/MainLayout";
import FavoriteAdCard from "../../components/pagesComponents/favorites/favoriteAdCard/FavoriteAdCard";
import {
  getAllFavorites,
  getCustomAds,
  getFavorites,
  removeAllFavorite,
  removeFavorite,
} from "../../services/FavoritesServices";
import { token } from "../../services/sevices";
import { IAd } from "../../types/ads.types";
import { IPagingResponse } from "../../types/general.types";
import { deepCopy, removeStore, setStore } from "../../utils";
import { useAppContext, useLoadingContext } from "../../utils/context";
import { FAVORITES } from "../../utils/stores";
import styles from "./favorites.module.scss";

const Favorites = () => {
  const [favorites, setFavorites] = useState<IPagingResponse<IAd>>({
    data: [],
  });
  const [showDeleteMsgBox, setShowDeleteMsgBox] = useState(false);
  const loadingContext = useLoadingContext();
  const context = useAppContext();

  useEffect(() => {
    getPagingFavorites();
  }, []);

  const getPagingFavorites = (url?: string, favorites?: number[]) => {
    loadingContext.setLoading(true);
    if (token())
      getFavorites(url)
        .then((res) => setFavorites(res.data))
        .finally(() => loadingContext.setLoading(false));
    else {
      if ((favorites || context.favorites).length)
        getCustomAds(url, favorites || context.favorites)
          .then((res) => setFavorites(res.data))
          .finally(() => loadingContext.setLoading(false));
      else {
        setFavorites({ data: [] });
        loadingContext.setLoading(false);
      }
    }
  };

  const deleteFromFavorite = (id: number) => {
    if (token()) {
      loadingContext.setLoading(true);
      removeFavorite(id)
        .then((res) => {
          if (res.status === 200) {
            getPagingFavorites();
            getAllFavorites().then((res) => {
              context.setFavorites(res.data);
              setStore(FAVORITES, res.data);
            });
          }
        })
        .finally(() => loadingContext.setLoading(false));
    } else {
      let arr = deepCopy(context.favorites);
      arr = arr.filter((x) => x != id);
      context.setFavorites(arr);
      getPagingFavorites("", arr);
      setStore(FAVORITES, arr);
    }
  };

  const deleteAllFavorite = () => {
    if (token()) {
      loadingContext.setLoading(true);
      removeAllFavorite()
        .then((res) => {
          if (res.status === 200) {
            setFavorites({ data: [] });
            context.setFavorites([]);
            removeStore(FAVORITES);
          }
        })
        .finally(() => loadingContext.setLoading(false));
    } else {
      context.setFavorites([]);
      getPagingFavorites("", []);
      setStore(FAVORITES, []);
    }
  };

  return (
    <MainLayout title="علاقه مندی ها">
      <div className="flex-col w-100">
        <div className={styles.container}>
          <div className="flex-c w-100">
            <RenderLinks links={[{ title: "علاقه مندی های من" }]} titleH1/>
            {favorites?.data.length ? (
              <Button
                onClick={() => setShowDeleteMsgBox(true)}
                type="outlined"
                style={{ whiteSpace: "nowrap" }}
              >
                حذف همه
              </Button>
            ) : null}
          </div>
          {favorites?.data.length === 0 ? (
            !loadingContext.loading && (
              <h2 className={styles["not-exist"]}>(اطلاعاتی وجود ندارد)</h2>
            )
          ) : (
            <div className={styles.container}>
              {favorites?.data?.map((item, i) => (
                <FavoriteAdCard
                  key={i}
                  adInfo={item}
                  onClickDelete={deleteFromFavorite}
                />
              ))}
            </div>
          )}
        </div>
        <Paging
          data={favorites?.meta}
          onClickPage={(link) => {
            getPagingFavorites(link.url || "");
          }}
        />
        <MessageBox
          visible={showDeleteMsgBox}
          onConfirm={() => {
            deleteAllFavorite();
            setShowDeleteMsgBox(false);
          }}
          onHiding={() => setShowDeleteMsgBox(false)}
        />
      </div>
    </MainLayout>
  );
};

export default Favorites;
