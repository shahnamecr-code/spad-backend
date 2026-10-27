import { IAd } from "../../../../types/ads.types";
import AdCardV2 from "../../../general/adCard-v2/AdCardV2";
import SectionTitle from "../../../general/sectionTitle/SectionTitle";
import styles from "./latestSection.module.scss";

interface IProps {
  title?: string;
  subTitle?: string;
  showAllTitle?: string;
  id: string;
  adItems?: IAd[];
  titleLink: string;
}

const LatestSection = (props: IProps) => {
  if (props.adItems && props.adItems.length <= 0) return <></>;
  return (
    <section className={styles.container}>
      <SectionTitle {...props} />
      <div className={styles["ads-container"]}>
        {props.adItems?.map((item, i) => (
          <AdCardV2 adInfo={item} key={i} />
        ))}
      </div>
    </section>
  );
};

export default LatestSection;
