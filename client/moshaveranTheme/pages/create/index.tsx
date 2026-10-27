import { Formik, Form, FormikProps } from "formik";
import { useState, useEffect } from "react";
import Button from "../../components/general/button/Button";
import { renderPopupTitle } from "../../components/general/popup/Popup";
import RenderLinks from "../../components/general/renderLinks/RenderLinks";
import MainLayout from "../../components/layout/mainLayout/MainLayout";
import { deepCopy } from "../../utils";
import styles from "./create.module.scss";
import { FiArrowRight } from "@react-icons/all-files/fi/FiArrowRight";
import { MdKeyboardArrowLeft } from "@react-icons/all-files/md/MdKeyboardArrowLeft";
import CreateAdSection from "../../components/pagesComponents/create/createAdSection/CreateAdSection";
import { toast } from "react-toastify";
import {
  createAd,
  getCategories,
  getCategoryLayout,
} from "../../services/AdsServices";
import { ICategory, IFieldData } from "../../types/createAd.types";
import { useLoadingContext } from "../../utils/context";
import { useRouter } from "next/router";

const Create = () => {
  const [selectedCategory, setSelectedCategory] = useState<ICategory[]>([]);
  const [fieldData, setFieldData] = useState<IFieldData>();
  const [categories, setCategories] = useState<ICategory[]>([]);
  const [errMsg, setErrMsg] = useState<{ [key: string]: string[] }>({});
  const router = useRouter();
  const context = useLoadingContext();

  useEffect(() => {
    context.setLoading(true);

    getCategories()
      .then((res) => setCategories(res.data))
      .finally(() => context.setLoading(false));
  }, []);

  useEffect(() => {
    const lastCategory = selectedCategory[selectedCategory.length - 1];
    if (
      selectedCategory?.length > 0 &&
      lastCategory.customer_children?.length === 0
    ) {
      context.setLoading(true);
      getCategoryLayout(lastCategory.id || 0)
        .then((res) => {
          setFieldData(res.data);
        })
        .finally(() => context.setLoading(false));
    }
  }, [selectedCategory]);

  const categoryGoBack = () => {
    const arr = deepCopy(selectedCategory);
    arr.splice(-1);
    setSelectedCategory(arr);
  };

  const renderCategory = (props: FormikProps<{ terms: {} }>) => (
    <>
      <div style={{ marginBottom: 8, display: "flex" }}>
        {renderPopupTitle("انتخاب دسته بندی")}
      </div>
      {selectedCategory?.length > 0 && (
        <Button
          onClick={categoryGoBack}
          type="text"
          className={styles["back-btn"]}
        >
          <FiArrowRight fontSize={16} />
          <span>
            بازگشت به همه ي{" "}
            {selectedCategory[selectedCategory.length - 2]
              ? selectedCategory[selectedCategory.length - 2].name
              : "دسته ها"}
          </span>
        </Button>
      )}
      {(selectedCategory?.length > 0
        ? selectedCategory[selectedCategory.length - 1]?.customer_children
        : categories
      )?.map((cat, i) => (
        <Button
          onClick={() => {
            setSelectedCategory((prev) => [...prev, cat]);
            props.setFieldValue("category", cat.id);
          }}
          type="text"
          key={i}
          className={styles["cat-name"]}
        >
          <h3>{cat.name || ""}</h3>
          <MdKeyboardArrowLeft fontSize={16} />
        </Button>
      ))}
    </>
  );

  const renderFields = () => (
    <>
      <div className={styles["fields-container"]}>
        <h2 className={styles["selected-category"]}>
          {selectedCategory[selectedCategory.length - 1]?.name}
        </h2>
        <Button
          onClick={categoryGoBack}
          type="text"
          color="primary"
          className={styles["change-category"]}
        >
          تغییر دسته بندی
        </Button>
      </div>

      {fieldData?.layout.map((data, i) => (
        <CreateAdSection
          key={i}
          sectionData={data}
          properties={fieldData?.properties}
          errMsgs={errMsg}
        />
      ))}
      {fieldData?.important_places?.length && (
        <CreateAdSection
          sectionData={{
            title: "فاصله از مراکز مهم",
            layout: [{ property_id: "distance" }],
          }}
          properties={{
            distance: {
              slug: "important_places",
              type: "important_places",
              options: fieldData?.important_places,
            },
          }}
          errMsgs={errMsg}
        />
      )}
      <div className={styles["btns-container"]}>
        <Button
          type="outlined"
          className={styles["cancel-btn"]}
          color="gray"
          onClick={() => router.back()}
        >
          انصراف
        </Button>
        <Button
          className={styles["send-ad-btn"]}
          btnType="submit"
          // disabled={loading}
        >
          ثبت آگهی
        </Button>
      </div>
    </>
  );

  return (
    <MainLayout title="ثبت آگهی">
      <div className={styles.container}>
        <RenderLinks links={[{ title: "ثبت آگهی" }]} titleH1 />
        <Formik
          initialValues={{ terms: {} }}
          onSubmit={(values) => {
            context.setLoading(true);
            createAd(values)
              .then(() => {
                toast.success("آگهی با موفقیت ثبت شد");
                router.push("/profile?active_tab=ads");
              })
              .catch((e) => {
                setErrMsg(e.response.data.errors);
              })
              .finally(() => context.setLoading(false));
          }}
        >
          {(props) => (
            <Form style={{ width: "100%" }}>
              <div className={styles["form-container"]}>
                {selectedCategory?.length > 0 &&
                (
                  selectedCategory[selectedCategory.length - 1]
                    .customer_children || []
                ).length === 0
                  ? renderFields()
                  : renderCategory(props)}
              </div>
            </Form>
          )}
        </Formik>
      </div>
    </MainLayout>
  );
};

export default Create;
