import Button from "../../../general/button/Button";
import { Select } from "../../../general/select/Select";
import styles from "./filters.module.scss";
import { HiFilter } from "@react-icons/all-files/hi/HiFilter";
import RegionSelect from "../regionSelect/RegionSelect";
import { ICategory } from "../../../../types/createAd.types";
import { useFiltersDev } from "./filters.dev";
import { FaHome } from "@react-icons/all-files/fa/FaHome";
import { useEffect } from "react";
import FieldsBox from "../fieldsBox/FieldsBox";

export interface IFiltersProps {
  onSearch: (values: any) => void;
  categories: ICategory[];
  labels: {
    label: string;
  }[];
  setLoading: (value: boolean) => void;
}

const Filters = (props: IFiltersProps) => {
  const dev = useFiltersDev(props);

  useEffect(() => {
    props.setLoading(dev.loading);
  }, [dev.loading]);

  return (
    <>
      <Button
        className={`w-100 sm-hide ${styles["search-btn"]}`}
        onClick={() => dev.setShowFilters(!dev.showFilters)}
        type="text"
      >
        مشاهده ی فیلترهای جستجو
        <HiFilter fontSize={20} />
      </Button>
      <div
        className={`${styles.container} ${
          !dev.showFilters ? styles["hide-filters"] : ""
        }`}
      >
        <h1 className={`${styles.title} xs-hide`}>ملک خود را بیابید ..!</h1>
        <div className={styles["box-container"]}>
          <div className={styles["box-label-container"]}>
            <h2>مشخصات ملک</h2>

            <FaHome fontSize={20} />
          </div>
          <div className="divider" />
          <div className={styles["box-items-container"]}>
            <RegionSelect
              label="منطقه"
              onChange={(e) => {
                dev.setFieldValue("position.region", e);
              }}
            />
            <Select
              id="transactions"
              label={props.labels[0]?.label}
              items={props.categories}
              className={styles.transaction}
              displayExpr="name"
              valueKey="id"
              value={dev.transaction}
              onChange={(e) => {
                if (e) {
                  dev.setValues({
                    transaction: e,
                    has_image: dev.values.has_image,
                    name: dev.values.name,
                  });
                }
              }}
            />
            {dev.transaction && (
              <>
                <Select
                  id="application_type"
                  label={props.labels[1]?.label}
                  items={dev.transaction.customer_children}
                  className={styles.transaction}
                  displayExpr="name"
                  valueKey="id"
                  value={dev.applicationType}
                  onChange={(e) => {
                    e &&
                      dev.setValues({
                        transaction: dev.transaction,
                        application_type: e,
                        has_image: dev.values.has_image,
                        name: dev.values.name,
                        land_type: undefined,
                      });
                  }}
                />

                {dev.applicationType && (
                  <Select
                    id="application_type"
                    label={props.labels[2]?.label}
                    items={dev.applicationType.customer_children}
                    className={styles.transaction}
                    displayExpr="name"
                    valueKey="id"
                    value={dev.landType}
                    onChange={(e) => {
                      e &&
                        dev.setValues({
                          transaction: dev.transaction,
                          application_type: dev.applicationType,
                          has_image: dev.values.has_image,
                          name: dev.values.name,
                          land_type: e,
                        });
                    }}
                  />
                )}
              </>
            )}
          </div>
        </div>

        {dev.filters?.map((item, i) => (
          <FieldsBox boxInfo={item} key={i} values={dev.values} />
        ))}

        <div className={styles["btn-container"]}>
          <Button width="100%" btnType="submit">
            جستجو
          </Button>
        </div>
      </div>
    </>
  );
};

export default Filters;
