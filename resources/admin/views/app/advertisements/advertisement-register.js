import React, { Suspense, useEffect, useState } from "react";
import { Button, Card, CardBody, Row } from "reactstrap";
import { Colxx, Separator } from "../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import { Formik, Form } from "formik";
import axios from "axios";
import { useHistory } from "react-router-dom";
import { NotificationManager } from "../../../components/common/react-notifications";
import { adminPathApi } from "../../../constants/defaultValues";
import AdvertisementFormLayout from "./components/AdvertisementFormLayout";

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

const initialFormValues = {
  important_places: [{ distance: "", place: "" }],
  property_tools: defaultPropertyTools,
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

const AdvertisementRegister = ({ match }) => {
  const [normalBox, setNormalBox] = useState([]);
  const [sideBox, setSideBox] = useState([]);
  const [properties, setProperties] = useState({});
  const [loading, setLoading] = useState(true);
  const [parents, setParents] = useState([]);
  const [path, setPath] = useState([]);
  const [selectedCategory, setSelectedCategory] = useState(null);
  const [options, setOptions] = useState([]);
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

  const onSubmit = (values) => {
    if (!selectedCategory) return;
    setSubmitting(true);

    axios
      .post(`${adminPathApi}/advertisements/register`, {
        ...values,
        category: selectedCategory.id,
      })
      .then((response) => {
        const data = response.data || {};
        if (!data.status) {
          NotificationManager.error(validationMessage(data.errors), "اطلاعات آگهی نیاز به بررسی دارد");
          return;
        }

        NotificationManager.success(
          data.message || "آگهی با موفقیت ثبت شد.",
          "عملیات با موفقیت انجام شد"
        );
        history.push("/spad-admin/advertisements/adver-list");
      })
      .catch((error) => handleApiError(error, "ثبت آگهی انجام نشد."))
      .finally(() => setSubmitting(false));
  };

  useEffect(() => {
    axios
      .get(`${adminPathApi}/advertisements/showCategories`)
      .then((res) => {
        const categories = res.data || [];
        setParents([]);
        setPath([categories]);
      })
      .catch((error) => handleApiError(error, "دریافت دسته‌بندی‌ها انجام نشد."))
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => {
    if (!selectedCategory) return;

    setLoading(true);
    axios
      .get(`${adminPathApi}/advertisement-category/layout/${selectedCategory.id}`)
      .then((res) => {
        const data = res.data || {};
        setNormalBox(data.normal || []);
        setSideBox(data.side || []);
        setProperties(data.properties || {});
        setOptions(data.important_places || []);
      })
      .catch((error) => {
        handleApiError(error, "دریافت فرم این دسته‌بندی انجام نشد.");
        setSelectedCategory(null);
      })
      .finally(() => setLoading(false));
  }, [selectedCategory && selectedCategory.id]);

  const resetCategory = () => {
    setSelectedCategory(null);
    setParents([]);
    setPath(path.length ? [path[0]] : []);
    setNormalBox([]);
    setSideBox([]);
    setProperties({});
    setOptions([]);
  };

  const goBackCategory = () => {
    if (path.length <= 1) return;
    setPath((current) => current.slice(0, -1));
    setParents((current) => current.slice(0, -1));
  };

  if (loading) return <div className="loading" />;

  const currentCategories = path.length ? path[path.length - 1] : [];

  return (
    <Suspense fallback={<div className="loading" />}>
      <div className="spad-ad-admin-page">
        <Row>
          <Colxx xxs="12">
            <Breadcrumb heading="ثبت آگهی" match={match} />
            <Separator className="mb-4" />
          </Colxx>
        </Row>

        {selectedCategory ? (
          <Formik enableReinitialize initialValues={initialFormValues} onSubmit={onSubmit}>
            <Form className="av-tooltip tooltip-label-right spad-ad-form">
              <AdvertisementFormLayout
                mode="create"
                normalBox={normalBox}
                sideBox={sideBox}
                properties={properties}
                options={options}
                categoryName={selectedCategory.name}
                submitting={submitting}
                onBack={resetCategory}
              />
            </Form>
          </Formik>
        ) : (
          <div className="spad-ad-category-stage">
            <div className="spad-ad-category-hero">
              <div>
                <span className="spad-ad-eyebrow">SPAD PROPERTY PUBLISHER</span>
                <h2>دسته‌بندی آگهی را انتخاب کنید</h2>
                <p>برای نمایش فرم دقیق و ویژگی‌های مرتبط، ابتدا نوع فایل ملکی را مشخص کنید.</p>
              </div>
              <div className="spad-ad-category-hero__icon">
                <i className="iconsminds-folder-add--" />
              </div>
            </div>

            <Card className="spad-ad-category-card">
              <CardBody>
                <div className="spad-ad-category-toolbar">
                  <div className="spad-ad-category-path">
                    <span className="spad-ad-category-path__root">همه دسته‌ها</span>
                    {parents.map((parent) => (
                      <React.Fragment key={parent.id}>
                        <i className="simple-icon-arrow-left" />
                        <span>{parent.name}</span>
                      </React.Fragment>
                    ))}
                  </div>

                  {path.length > 1 ? (
                    <Button type="button" color="light" className="spad-ad-secondary-btn" onClick={goBackCategory}>
                      <i className="simple-icon-arrow-right ml-1" />
                      بازگشت
                    </Button>
                  ) : null}
                </div>

                <div className="spad-ad-category-grid">
                  {currentCategories.map((category) => {
                    const hasChildren = category.customer_children && category.customer_children.length > 0;
                    return (
                      <button
                        type="button"
                        className="spad-ad-category-option"
                        key={category.id}
                        onClick={() => {
                          if (hasChildren) {
                            setParents((current) => [...current, category]);
                            setPath((current) => [...current, category.customer_children]);
                          } else {
                            setSelectedCategory(category);
                          }
                        }}
                      >
                        <span className="spad-ad-category-option__icon">
                          {category.icon ? <i className={category.icon} /> : <i className="simple-icon-home" />}
                        </span>
                        <span className="spad-ad-category-option__body">
                          <strong>{category.name}</strong>
                          <small>{hasChildren ? "مشاهده زیرمجموعه‌ها" : "باز کردن فرم ثبت آگهی"}</small>
                        </span>
                        <span className="spad-ad-category-option__arrow">
                          <i className="simple-icon-arrow-left" />
                        </span>
                      </button>
                    );
                  })}
                </div>

                {!currentCategories.length ? (
                  <div className="spad-ad-empty-category">
                    <i className="simple-icon-info" />
                    دسته‌بندی قابل انتخابی پیدا نشد.
                  </div>
                ) : null}
              </CardBody>
            </Card>
          </div>
        )}
      </div>
    </Suspense>
  );
};

export default AdvertisementRegister;
