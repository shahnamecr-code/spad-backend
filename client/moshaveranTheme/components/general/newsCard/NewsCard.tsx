/* eslint-disable @next/next/no-img-element */
import { useRouter } from "next/router";
import Skeleton from "react-loading-skeleton";
import "react-loading-skeleton/dist/skeleton.css";
import styles from "./newsCard.module.scss";
import { INews } from "../../../types/news.types";
import Link from "next/link";

interface IProps {
  newsInfo?: INews;
  className?: string;
}

const NewsCard = (props: IProps) => {
  const router = useRouter();
  return (
    <div className={`${styles.space} ${props.className}`}>
      <div className={styles.container}>
        {props.newsInfo?.image ? (
          <div>
            <div className={styles["gallery-container"]}>
              <img
                src={props.newsInfo?.image}
                style={{ height:"100%",width: "100%"}}
                className="object-fit-cover swiper-lazy"
                alt={props.newsInfo.title}
              />
              <div className={styles.code}>{props.newsInfo?.created_at}</div>
            </div>
          </div>
        ) : (
          <Skeleton height={180} width="100%" borderRadius={16} />
        )}

        <div
          className={styles["info-container"]}
          onClick={() => router.push(`/news/${props.newsInfo?.slug}`)}
        >
          {props.newsInfo?.categories?.map((cat, index) => {
            return (
              <div
                key={index}
                className={styles.slug}
                onClick={(e) => e.stopPropagation()}
              >
                <Link href={`/news/category/${cat.slug}`}>{`${cat.name}`}</Link>
              </div>
            );
          })}
          {props.newsInfo ? (
            <h2 className={styles.title}>{props.newsInfo?.title}</h2>
          ) : (
            <Skeleton height={10} />
          )}
          <p className={styles["description"]}>
            {props.newsInfo ? (
              `${props.newsInfo?.short_description}`
            ) : (
              <Skeleton width="50%" height={10} count={3} />
            )}
          </p>
        </div>
      </div>
    </div>
  );
};

export default NewsCard;
