import RenderLinks from "../../components/general/renderLinks/RenderLinks";
import MainLayout from "../../components/layout/mainLayout/MainLayout";
import { getRules } from "../../services/generalServices";
import { IRule } from "../../types/general.types";
import { useAppContext } from "../../utils/context";
import styles from "./rules.module.scss";

interface IProps {
  rules: IRule[];
}

const Rules = (props: IProps) => {
  const context = useAppContext();

  return (
    <MainLayout title="قوانین و مقررات">
      <RenderLinks links={[{ title: "قوانین و مقررات" }]} />
      <div className={styles.container}>
        <h1 className={styles.title}>
          قوانین و مقررات {context.options.general_site_name}
        </h1>
        <div className={styles["description-container"]}>
          <p>
            توجه: شما ضمن استفاده از سامانه {context.options.general_site_name}{" "}
            موافقت خود را با شرایط مقرر در این وب سایت اعلام کرده اید.
          </p>
          <p>
            لازم است پیش از عضویت در {context.options.general_site_name} و
            استفاده از امکانات و خدمات آن، ابتدا با تعاریف و اصطلاحات و سپس با
            ضوابط و مقررات این سامانه آشنا شوید.
          </p>
        </div>
        {props.rules.map((item, i) => {
          let splitedArray: string[] = [];
          let answ = item?.description?.split("\n");
          answ?.forEach(function (obj) {
            splitedArray.push(obj);
          });
          return (
            <div className={styles["items-container"]} key={i}>
              <h2 className={styles["item-title"]}>{item.title}</h2>
              {splitedArray.length ? (
                splitedArray.map((row, i) => (
                  <p className={styles["item-description"]} key={i}>
                    {row}
                  </p>
                ))
              ) : (
                <p className={styles["item-description"]}>{item.description}</p>
              )}
            </div>
          );
        })}
      </div>
    </MainLayout>
  );
};

export default Rules;

export async function getServerSideProps() {
  // Fetch data from external API
  const rules = await getRules().then((res) => res.data.rules);
  // Pass data to the page via props
  return { props: { rules: rules } };
}
