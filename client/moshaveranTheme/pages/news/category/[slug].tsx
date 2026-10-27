import NewsCard from "../../../components/general/newsCard/NewsCard";
import RenderLinks from "../../../components/general/renderLinks/RenderLinks";
import MainLayout from "../../../components/layout/mainLayout/MainLayout";
import { getNews, getNewsByCategory } from "../../../services/NewsServices";
import { INews } from "../../../types/news.types";
import styles from "../news.module.scss";

interface IProps {
  news: INews[];
}

const News = ({ news }: IProps) => {
  return (
    <MainLayout title="اخبار و مقالات">
      <RenderLinks links={[{ title: "اخبار و مقالات" }]} titleH1/>
      <div className={styles["container"]}>
        {news.map((item, i) => (
          <NewsCard newsInfo={item} key={i} />
        ))}
      </div>
    </MainLayout>
  );
};

export default News;

export async function getServerSideProps(context: any) {
  // Fetch data from external API
  const news = await getNewsByCategory(context.params.slug).then((res) => {
    return res.data;
  });
  // // Pass data to the page via props
  return { props: { news: news } };
}
