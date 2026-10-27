/* eslint-disable @next/next/no-img-element */
import { FiArrowRight } from "@react-icons/all-files/fi/FiArrowRight";
import Link from "next/link";
import { img } from "../../utils";
import styles from "./404.module.scss";

const NotFound = () => {
  return (
    <div className={styles.container}>
      <img src={img.not_found.default.src} alt="404" />
      <h1>صفحه موردنظر یافت نشد</h1>
      <Link href="/">
        <a>
          <FiArrowRight />
          بازگشت به صفحه اصلی
        </a>
      </Link>
    </div>
  );
};

export default NotFound;
