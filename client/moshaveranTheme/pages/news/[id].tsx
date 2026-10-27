/* eslint-disable @next/next/no-img-element */
import Head from "next/head";
import React from "react";
import RenderLinks from "../../components/general/renderLinks/RenderLinks";
import MainLayout from "../../components/layout/mainLayout/MainLayout";
import { getNewsBySlug } from "../../services/NewsServices";
import { INews } from "../../types/news.types";
import { paths } from "../../utils";

import styles from "./news.module.scss";

interface IProps {
  newsInfo: INews;
}

const News = ({ newsInfo }: IProps) => {
  return (
    <React.Fragment>
      <Head>
        <meta charSet="UTF-8" />
        <meta name="description" content={newsInfo.meta_description} />
        <meta
          name="keywords"
          content={newsInfo?.meta_description?.split(" ").join(",")}
        />
        <meta name="author" content={newsInfo.author} />
      </Head>
      <MainLayout title={newsInfo.meta_title}>
        <div className={styles["single-container"]}>
          <RenderLinks
            links={[
              { title: "اخبار و مقالات", url: paths.news },
              ...newsInfo.categories.map((cat) => {
                return { title: cat.name, url: `/news/category/${cat.slug}` };
              }),
              { title: newsInfo.title },
            ]}
            titleH1
          />
          <div className={styles.heading}>
            {/* {newsInfo.categories?.map((cat, i) => (
            <Link key={i} href={`/news/category/${cat.slug}`}>
              {`${cat.name}`}
            </Link>
          ))} */}
            <h2>{newsInfo.created_at}</h2>
          </div>
          <div className={styles.image}>
            <img
              src={newsInfo.image}
              alt={newsInfo.meta_description}
            />
          </div>

          <div
            className={styles.description}
            dangerouslySetInnerHTML={{ __html: newsInfo.description }}
          />
        </div>
      </MainLayout>
    </React.Fragment>
  );
};

export default News;

export async function getServerSideProps(context: any) {
  // Fetch data from external API
  const newsInfo = await getNewsBySlug(context.params.id).then((res) => res);

  // Pass data to the page via props
  return {
    props: {
      newsInfo,
    },
  };
}
