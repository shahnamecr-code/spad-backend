import styles from "./paging.module.scss";
import Button from "../button/Button";
import { renderHtml } from "../../../utils";
import { IPagingLink, IPagingMeta } from "../../../types/general.types";

interface IProps {
  data?: IPagingMeta;
  onClickPage: (link: IPagingLink) => void;
}

const Paging = (props: IProps) => {
  return (props.data?.last_page || 0) > 1 ? (
    <div className={styles.container}>
      {props.data?.links.map((link, i) => (
        <Button
          type="text"
          key={i}
          className={`${styles.link} ${link.active ? styles.active : ""}`}
          disabled={link.url === null}
          onClick={() => props.onClickPage(link)}
        >
          <div
            dangerouslySetInnerHTML={{
              __html: renderHtml(link.label),
            }}
          />
        </Button>
      ))}
    </div>
  ) : null;
};

export default Paging;
