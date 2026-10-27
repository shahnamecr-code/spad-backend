/* eslint-disable @next/next/no-img-element */
import Link from "next/link";
import styles from "./baner.module.scss";

interface IProps {
  img: string;
  link?: string;
}

const Baner = (props: IProps) => {
  return (
    <div className={styles.container}>
      <Link href={props.link || "/"}>
        <a>
          <img src={props.img} alt="baner" />
        </a>
      </Link>
    </div>
  );
};

export default Baner;
