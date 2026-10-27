import { Form, Formik, FormikProps } from "formik";
import { useContext, useState } from "react";
import Button from "../../components/general/button/Button";
import RenderLinks from "../../components/general/renderLinks/RenderLinks";
import MainLayout from "../../components/layout/mainLayout/MainLayout";
import styles from "./recruitment.module.scss";
import { toast } from "react-toastify";
import { createAd } from "../../services/AdsServices";
import { AppContext } from "../../utils/context";
import { useRouter } from "next/router";
import Input from "../../components/general/input/Input";
import { Select } from "../../components/general/select/Select";
import TextArea from "../../components/general/textArea/TextArea";
import { useRecruitmentDev } from "../../components/pagesComponents/recruitment/recruitment.dev";
import { deepCopy, getFromOjectByKey } from "../../utils";
import { BsPlusSquare } from "@react-icons/all-files/bs/BsPlusSquare";
import { BsTrash } from "@react-icons/all-files/bs/BsTrash";
import { recruitmentPost } from "../../services/generalServices";

export interface IRecruitmentData {
  address: string;
  city_id: number;
  description: string;
  guarantee: boolean;
  job_demand: number;
  mobile: string;
  name: string;
  postal_code: string;
  province_id: number;
  second_mobile: string;
  study_degree: number;
  study_field: string;
  work_experience: string[];
}

const Recruitment = () => {
  const [errMsg, setErrMsg] = useState<{ [key: string]: string[] }>({});
  const router = useRouter();
  const dev = useRecruitmentDev();
  const { provinces } = useContext(AppContext);

  const renderFields = (props: FormikProps<IRecruitmentData>) => {
    const fieldProps = (name: string) => {
      return {
        value: getFromOjectByKey(props.values, name) as any,
        onChange: (e: any) => props.setFieldValue(name, e),
        validationMessage: errMsg ? errMsg[name]?.join(" - ") : "",
      };
    };

    return (
      <div className={styles["form-container"]}>
        <Input
          className="col-12 col-md-6"
          label="نام و نام خانوادگی خود را به طور کامل وارد نمایید"
          {...fieldProps("name")}
        />
        <Input
          className="col-12 col-md-6"
          label="شماره همراه"
          type="number"
          {...fieldProps("mobile")}
        />
        <Input
          className="col-12 col-md-6"
          label="شماره ضروری"
          type="number"
          {...fieldProps("second_mobile")}
        />
        <Input
          className="col-12 col-md-6"
          label="کد پستی"
          type="number"
          {...fieldProps("postal_code")}
        />
        <Select
          id=""
          label="استان"
          className="col-12 col-md-6"
          items={provinces}
          displayExpr="name"
          valueKey="id"
          value={provinces.find((x) => x.id == props.values.province_id)}
          onChange={(e) => {
            if (!(e instanceof Array)) {
              props.setFieldValue("province_id", e?.id);
              props.setFieldValue("city_id", null);
              dev.handleChangeProvince(e?.id || 0);
            }
          }}
          validationMessage={errMsg ? errMsg["province_id"]?.join(" - ") : ""}
        />
        <Select
          id=""
          label="شهر"
          className="col-12 col-md-6"
          items={dev.cities}
          displayExpr="name"
          valueKey="id"
          onChange={(e) => {
            if (!(e instanceof Array)) {
              props.setFieldValue("city_id", e?.id);
            }
          }}
          value={dev.cities?.find((x) => x.id == props.values.city_id)}
          validationMessage={errMsg ? errMsg["city_id"]?.join(" - ") : ""}
        />
        <Input className="col-12" label="آدرس" {...fieldProps("address")} />
        <Input
          className="col-12"
          label="رشته تحصیلی"
          {...fieldProps("study_field")}
        />
        <Select
          id=""
          label="مدرک تحصیلی"
          className="col-12 col-md-6"
          onChange={(e) => {
            if (!(e instanceof Array)) {
              props.setFieldValue("study_degree", e?.value);
            }
          }}
          items={dev.studyDegreeItems}
          value={dev.studyDegreeItems?.find(
            (x) => x.value == props.values.study_degree
          )}
          validationMessage={errMsg ? errMsg["study_degree"]?.join(" - ") : ""}
        />
        <Select
          id=""
          label="شغل مورد تقاضا"
          className="col-12 col-md-6"
          onChange={(e) => {
            if (!(e instanceof Array)) {
              props.setFieldValue("job_demand", e?.value);
            }
          }}
          items={dev.jobItems}
          value={dev.jobItems?.find((x) => x.value == props.values.job_demand)}
          validationMessage={errMsg ? errMsg["job_demand"]?.join(" - ") : ""}
        />
        <div className="divider" />
        <div className="col-12">
          <div className={styles["work-label-container"]}>
            <span>افزودن سوابق شغلی</span>
            <Button
              type="text"
              color="dark"
              onClick={() => dev.setWorkExperienceCount((prev) => prev + 1)}
            >
              <BsPlusSquare fontSize={20} />
            </Button>
          </div>
          {Array.from({ length: dev.workExperienceCount }).map((_, i) => (
            <div className={styles["work-input-container"]} key={i}>
              <Input {...fieldProps(`work_experience[${i}]`)} />
              <Button
                type="text"
                onClick={() => {
                  const work_experience = deepCopy(
                    props.values.work_experience || []
                  );
                  work_experience.splice(i, 1);
                  props.setFieldValue("work_experience", work_experience);
                  dev.setWorkExperienceCount((prev) => prev - 1);
                }}
              >
                <BsTrash fontSize={20} />
              </Button>
            </div>
          ))}
        </div>
        <div className="divider" />
        <Select
          id=""
          label="آیا در صورت لزوم میتوانید تعهد و تضمین بدهید ؟"
          className="col-12 col-md-6"
          onChange={(e) => {
            if (!(e instanceof Array)) {
              props.setFieldValue("guarantee", e?.value);
            }
          }}
          items={dev.guaranteeItems}
          value={dev.guaranteeItems?.find(
            (x) => x.value == props.values.guarantee
          )}
          validationMessage={errMsg ? errMsg["guarantee"]?.join(" - ") : ""}
        />
        <TextArea
          className="col-12"
          label="توضیحات"
          {...fieldProps("description")}
        />
        <div className={styles["btns-container"]}>
          <Button className={styles["send-btn"]} btnType="submit">
            ثبت
          </Button>
        </div>
      </div>
    );
  };

  return (
    <MainLayout title="استخدام">
      <div className={styles.container}>
        <RenderLinks links={[{ title: "استخدام" }]} titleH1/>
        <Formik
          initialValues={{} as IRecruitmentData}
          onSubmit={(values) => {
            dev.loadingContext.setLoading(true);
            recruitmentPost(values)
              .then(() => {
                toast.success("درخواست شما با موفقیت ثبت شد");
                router.back();
              })
              .catch((e) => {
                setErrMsg(e.response.data.errors);
              })
              .finally(() => dev.loadingContext.setLoading(false));
          }}
        >
          {(props) => <Form>{renderFields(props)}</Form>}
        </Formik>
      </div>
    </MainLayout>
  );
};

export default Recruitment;
