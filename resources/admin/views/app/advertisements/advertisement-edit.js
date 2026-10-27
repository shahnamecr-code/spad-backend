import React, { Suspense, useEffect, useState } from "react";
import { Row } from "reactstrap";
import { Colxx, Separator } from "../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import { Form, Formik } from "formik";
import axios from "axios";
import { useHistory } from "react-router-dom";
import { NotificationManager } from "../../../components/common/react-notifications";
import { adminPathApi } from "../../../constants/defaultValues";
import AdvertisementFormLayout from "./components/AdvertisementFormLayout";
import VirtualTourPropertyCard from "../virtual-tours/components/VirtualTourPropertyCard";

const defaultPropertyTools = {
  virtual_tour_enabled: false,
  virtual_tour_files: [],
  virtual_tour_url: "",
  renovation_enabled: false,
  renovation_url: "",
  rental_deposit_ceiling: "",
  rental_monthly_rent: "",
  rental_occupancy_status: "vacant",
  rental_evacuation_date: "",
  mortgage_enabled: false,
  mortgage_max_amount: "",
  document_type: "",
  owner_identity_verified: false,
  documents_verified: false,
  below_expert_price: false,
  floorplan_3d_enabled: false,
  floorplan_3d_url: "",
};

const validationMessage = (errors) => {
  if (!errors) return "برخی اطلاعات فرم کامل یا معتبر نیستند.";
  if (Array.isArray(errors)) return errors.filter(Boolean).join("، ") || "برخی اطلاعات فرم کامل نیستند.";
  if (typeof errors === "object") {
    const messages = Object.keys(errors).reduce((result, key) => {
      const value = errors[key];
      if (Array.isArray(value)) return result.concat(value);
      if (value) result.push(value);
      return result;
    }, []);
    return messages.join("، ") || "برخی اطلاعات فرم کامل نیستند.";
  }
  return String(errors);
};

const AdvertisementEdit = ({ match }) => {
  const [initialValues, setInitialValues] = useState(null);
  const [fileUrls, setFileUrls] = useState([]);
  const [normalBox, setNormalBox] = useState([]);
  const [sideBox, setSideBox] = useState([]);
  const [properties, setProperties] = useState({});
  const [options, setOptions] = useState([]);
  const [categoryName, setCategoryName] = useState("");
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const history = useHistory();

  const handleApiError = (error, fallback) => {
    if (error.response && error.response.status === 402 && error.response.data && error.response.data.url) {
      window.location.replace(error.response.data.url);
      return;
    }
    const responseData = error.response && error.response.data ? error.response.data : {};
    const message = responseData.errors
      ? validationMessage(responseData.errors)
      : responseData.message || error.message || fallback;
    NotificationManager.error(message, "خطا");
  };

  useEffect(() => {
    let mounted = true;
    setLoading(true);

    const loadAdvertisement = async () => {
      try {
        const response = await axios.get(`${adminPathApi}/advertisements/edit/${match.params.id}`);
        const data = response.data || {};

        let propertyTools = { ...defaultPropertyTools };
        try {
          const toolsResponse = await axios.get(
            `${adminPathApi}/advertisements/property-tools/${match.params.id}`
          );
          propertyTools = {
            ...defaultPropertyTools,
            ...((toolsResponse.data && toolsResponse.data.property_tools) || {}),
          };
        } catch (toolsError) {
          // آگهی‌های قدیمی ممکن است هنوز رکورد ابزار نداشته باشند؛ فرم با مقادیر پیش‌فرض باز می‌شود.
        }

        if (!mounted) return;

        const advertisementData = data.data || {};
        const importantPlaces =
          Array.isArray(advertisementData.important_places) && advertisementData.important_places.length
            ? advertisementData.important_places
            : [{ distance: "", place: "" }];

        setNormalBox(data.normal || []);
        setSideBox(data.side || []);
        setProperties(data.properties || {});
        setOptions(data.important_places || []);
        setCategoryName((data.category && data.category.name) || "");
        setFileUrls(Array.isArray(advertisementData.gallery) ? advertisementData.gallery : []);
        setInitialValues({
          ...advertisementData,
          important_places: importantPlaces,
          property_tools: propertyTools,
        });
      } catch (error) {
        if (mounted) handleApiError(error, "دریافت اطلاعات آگهی انجام نشد.");
      } finally {
        if (mounted) setLoading(false);
      }
    };

    loadAdvertisement();
    return () => {
      mounted = false;
    };
  }, [match.params.id]);

  const onSubmit = (values) => {
    setSubmitting(true);
    axios
      .post(`${adminPathApi}/advertisements/edit/${match.params.id}`, values)
      .then((response) => {
        const data = response.data || {};
        if (!data.status) {
          NotificationManager.error(validationMessage(data.errors), "اطلاعات آگهی نیاز به بررسی دارد");
          return;
        }

        NotificationManager.success(data.message || "تغییرات آگهی با موفقیت ذخیره شد.");
        history.push("/spad-admin/advertisements/adver-list");
      })
      .catch((error) => handleApiError(error, "ذخیره تغییرات آگهی انجام نشد."))
      .finally(() => setSubmitting(false));
  };

  if (loading || !initialValues) return <div className="loading" />;

  return (
    <Suspense fallback={<div className="loading" />}>
      <div className="spad-ad-admin-page">
        <Row>
          <Colxx xxs="12">
            <Breadcrumb heading="ویرایش آگهی" match={match} />
            <Separator className="mb-4" />
          </Colxx>
        </Row>

        <Formik enableReinitialize initialValues={initialValues} onSubmit={onSubmit}>
          <Form className="av-tooltip tooltip-label-right spad-ad-form">
            <AdvertisementFormLayout
              mode="edit"
              normalBox={normalBox}
              sideBox={sideBox}
              properties={properties}
              options={options}
              fileUrls={fileUrls}
              setFileUrls={setFileUrls}
              categoryName={categoryName}
              submitting={submitting}
              onBack={() => history.push("/spad-admin/advertisements/adver-list")}
              extraContent={<VirtualTourPropertyCard advertisementId={match.params.id} />}
            />
          </Form>
        </Formik>
      </div>
    </Suspense>
  );
};

export default AdvertisementEdit;
