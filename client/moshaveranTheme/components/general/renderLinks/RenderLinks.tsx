import { IoIosArrowForward } from "@react-icons/all-files/io/IoIosArrowForward";
import Link from "next/link";
import { useRouter } from "next/router";
import { useAppContext } from "../../../utils/context";
import Button from "../button/Button";
import styles from "./renderLinks.module.scss";

interface IProps {
  links: { title: string; url?: string }[];
  titleH1?: boolean;
}

const RenderLinks = (props: IProps) => {
  const router = useRouter();
  const context = useAppContext();

  return (
    <div className={styles.container}>
      <Button
        type="text"
        color="primary"
        className={styles["back-btn"]}
        onClick={() => router.back()}
      >
        <IoIosArrowForward />
        بازگشت
      </Button>
      <span className={styles.slash}>/</span>
      <Link href="/">
        <a>{context.options.general_site_name || ""}</a>
      </Link>
      {props.links.slice(0, props.links.length - 1).map((item, i) => (
        <Link key={i} href={item.url || "/"}>
          <a>{item.title}</a>
        </Link>
      ))}
      {props.titleH1 ? (
        <h1>{props.links[props.links.length - 1]?.title}</h1>
      ) : (
        <span>{props.links[props.links.length - 1]?.title}</span>
      )}
    </div>
  );
};

export default RenderLinks;
