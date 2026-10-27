import { IoIosArrowBack } from "@react-icons/all-files/io/IoIosArrowBack";
import Link from "next/link";
import styles from "./sectionTitle.module.scss";

interface IProps {
  title?: string;
  subTitle?: string;
  showAllTitle?: string;
  titleLink: string;
}

const SectionTitle = (props: IProps) => {
  return (
    <div className={styles["title-container"]}>
      <div className="flex-col">
        <h2 className={styles.main}>{props.title}</h2>
        <h3 className={styles.sub}>{props.subTitle}</h3>
      </div>
      <Link href={props.titleLink}>
        <a className={styles["show-all"]}>
          {props.showAllTitle || "نمایش همه"}
          <IoIosArrowBack />
        </a>
      </Link>
    </div>
  );
};

export default SectionTitle;
