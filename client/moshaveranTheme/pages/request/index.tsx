import { Formik } from "formik";
import { useRouter } from "next/router";
import React, { useState } from "react";
import { toast } from "react-toastify";
import MainLayout from "../../components/layout/mainLayout/MainLayout";
import { postRequest } from "../../services/RequestServices";
import { deepCopy, paths } from "../../utils";
import { useLoadingContext } from "../../utils/context";
import RequestForm from "../../components/pagesComponents/request/requestForm/RequestForm";
import { profileNavItems } from "../../components/pagesComponents/profile/profileBox/ProfileBox";

const Request = () => {
  const [errMsgs, setErrMsgs] = useState<{ [key: string]: string[] }>();
  const context = useLoadingContext();
  const router = useRouter();

  const formOnSubmit = (values: { [key: string]: any }) => {
    context.setLoading(true);
    let newValues = deepCopy(values);

    const category =
      newValues.land_type.slug ||
      newValues.application_type.slug ||
      newValues.transaction.slug;

    delete newValues.transaction;
    delete newValues.application_type;
    delete newValues.land_type;

    postRequest(category, newValues)
      .then(() => {
        toast.success("درخواست شما با موفقیت ثبت شد");
        router.push(`${paths.profile}?active_tab=${profileNavItems[2].tab}`);
      })
      .catch((e) => {
        if (e.response.data.errors) {
          const errors: {
            [key: string]: string[];
          } = {};
          Object.keys(e.response.data.errors).map((key) => {
            errors[key.replace("req.", "")] = e.response.data.errors[key];
          });
          setErrMsgs(errors);
        }
      })
      .finally(() => context.setLoading(false));
  };

  return (
    <MainLayout title="تقاضای ملک">
      <Formik initialValues={{}} onSubmit={formOnSubmit}>
        <RequestForm errMsgs={errMsgs} resetErrMsgs={() => setErrMsgs({})} />
      </Formik>
    </MainLayout>
  );
};

export default Request;
