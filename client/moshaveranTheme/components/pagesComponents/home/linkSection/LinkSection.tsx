import Link from "next/link";
import styles from "./linkSection.module.scss";

const LinkSection = () => {
  const linkItems = [
    { url: "/", title1: "ویلا های فروشی", title2: "تمامی ویلا های فروشی" },
    {
      url: "/",
      title1: "آپارتمان های اجاره ای",
      title2: "کلیه آپارتمان های اجاره ای",
    },
    { url: "/", title1: "زمین های مشارکتی", title2: "کلیه زمین های مشارکتی" },
    { url: "/", title1: "معاوضه ها", title2: "کلیه املاک معاوضه ای" },
  ];
  return (
    <div className={styles.container}>
      {linkItems.map((item, i) => (
        <Link key={i} href={item.url}>
          <a className={styles.card}>
            <span className={styles.title1}>{item.title1}</span>
            <span className={styles.title2}>{item.title2}</span>
          </a>
        </Link>
      ))}
    </div>
  );
};

export default LinkSection;
