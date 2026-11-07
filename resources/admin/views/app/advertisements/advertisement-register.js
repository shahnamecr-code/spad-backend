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
import { uploadTourZip } from "./upload-tour-zip";

const defaultPropertyTools = {
  virtual_tour_enabled: false,
  virtual_tour_files: [],
  virtual_tour_url: "",
  renovation_enabled: false,
  renovation_url: "",
  map_lat: null,
  map_lng: null,
  rental_deposit_ceiling: "",
  rental_monthly_rent: "",
  rental_occupancy_status: "vacant",
  rental_evacuation_date: "",
  mortgage_enabled: false,
  mortgage_max_amount: "",
  deposit_enabled: false,
  deposit_amount: "",
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

// The same quick categories used on /search, resolved against live category IDs.
// Never select an invented/invalid category or bypass its dedicated registration form.
const popularCategories = [
  { key: "apartment", label: "آپارتمان", aliases: ["آپارتمان", "apartment"] },
  { key: "villa-house", label: "خانه ویلایی", aliases: ["خانه(حیاط)", "خانه ویلایی", "ویلا"] },
  { key: "garden-villa", label: "باغ ویلا", aliases: ["فروش ویلا و باغ", "باغ ویلا", "باغ‌ویلا", "garden-villa"] },
  { key: "land", label: "زمین", aliases: ["فروش زمین و کلنگی", "فروش زمین", "زمین", "land"] },
  { key: "commercial", label: "تجاری", aliases: ["فروش تجاری/خدماتی/اداری", "فروش تجاری", "تجاری", "commercial"] },
  { key: "construction-partnership", label: "مشارکت در ساخت", aliases: ["مشارکت در ساخت", "مشارکت", "construction-partnership"] },
  { key: "rental", label: "رهن و اجاره", aliases: ["رهن و اجاره", "رهن‌واجاره", "اجاره مسکونی", "اجاره", "rent"] },
  { key: "pre-sale", label: "پیش فروش", aliases: ["پیش فروش", "پیش‌فروش"] },
  { key: "daily-rent", label: "اجاره روزانه", aliases: ["اجاره روزانه", "daily-rent"] },
];
const normalizeCategoryLabel = value =>
  String(value || "").replace(/ي/g, "ی").replace(/ك/g, "ک")
    .replace(/[\u200c\u200d\s_-]+/g, "").toLowerCase().trim();

const resolveCategoryBranch = (roots, item) => {
  const aliases = item.aliases.map(normalizeCategoryLabel);
  const matches = [];
  const walk = (nodes, parents = []) => {
    (nodes || []).forEach(node => {
      const branch = [...parents, node];
      const slug = normalizeCategoryLabel(node.slug);
      const name = normalizeCategoryLabel(node.name);
      const index = aliases.findIndex(alias => slug === alias || name === alias);
      if (index >= 0) matches.push({ branch, index });
      if (node.customer_children && node.customer_children.length && branch.length < 6) {
        walk(node.customer_children, branch);
      }
    });
  };
  walk(roots);
  matches.sort((a,b) => a.index - b.index || b.branch.length - a.branch.length);
  return matches.length ? matches[0].branch : null;
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
  const [tourZip, setTourZip] = useState(null);
  const [tourZipProgress, setTourZipProgress] = useState(null);
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
      .then(async (response) => {
        const data = response.data || {};
        if (!data.status) {
          NotificationManager.error(validationMessage(data.errors), "اطلاعات آگهی نیاز به بررسی دارد");
          return;
        }
        if (tourZip) {
          const newId = data.advertisement_id;
          if (!newId) {
            NotificationManager.warning("آگهی ثبت شد اما شناسه آن برای آپلود ZIP برنگشت؛ از صفحه ویرایش فایل را مجدداً انتخاب کنید.");
            history.push("/spad-admin/advertisements/adver-list");
            return;
          }
          try {
            const result = await uploadTourZip(newId, tourZip, setTourZipProgress);
            NotificationManager.success(result.message || "فایل ZIP استخراج شد و سکانس‌ها به تور ملک اضافه شدند.");
          } catch (uploadError) {
            setTourZipProgress(prev => ({ ...(prev || {}), phase: "failed" }));
            NotificationManager.error(
              uploadError.response?.data?.message || uploadError.message || "آپلود تور مجازی ناموفق بود. آگهی ذخیره شده است؛ فایل را در فرم ویرایش دوباره بارگذاری کنید.",
              "آگهی ثبت شد؛ خطای تور مجازی"
            );
            history.push(`/spad-admin/advertisements/edit/${newId}`);
            return;
          }
        }
        NotificationManager.success(data.message || "آگهی با موفقیت ثبت شد.");
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
  const categoryRoots = path.length ? path[0] : [];
  const chooseQuickCategory = (item) => {
    const branch = resolveCategoryBranch(categoryRoots, item);
    if (!branch) {
      NotificationManager.warning("این دسته هنوز در تنظیمات دسته‌بندی آگهی تعریف نشده است.", "دسته‌بندی ناموجود");
      return;
    }
    const node = branch[branch.length - 1];
    if (!node.customer_children || !node.customer_children.length) {
      setSelectedCategory(node);
      return;
    }
    setParents(branch);
    setPath([categoryRoots, ...branch.map(category => category.customer_children || [])]);
  };

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
                tourZip={tourZip}
                onTourZipChange={file => { setTourZip(file); setTourZipProgress(null); }}
                tourZipProgress={tourZipProgress}
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

            <div className="spad-ad-quick-categories">
              <div className="spad-ad-quick-categories__head">
                <strong>انتخاب سریع نوع ملک</strong>
                <span>مشابه فیلتر خرید ملک · انتخاب دسته‌بندی واقعی</span>
              </div>
              <div className="spad-ad-quick-categories__grid" role="group" aria-label="دسته‌بندی‌های پرکاربرد ثبت آگهی">
                {popularCategories.map(item => {
                  const branch = resolveCategoryBranch(categoryRoots, item);
                  const enabled = Boolean(branch);
                  const selected = branch && (
                    selectedCategory && selectedCategory.id === branch[branch.length - 1].id ||
                    parents.some(parent => parent.id === branch[branch.length - 1].id)
                  );
                  return (
                    <button key={item.key} type="button"
                      className={`spad-ad-quick-category${selected ? " is-active" : ""}`}
                      disabled={!enabled}
                      title={enabled ? `انتخاب ${item.label}` : `${item.label} هنوز در تنظیمات دسته‌بندی تعریف نشده است`}
                      aria-pressed={Boolean(selected)}
                      onClick={() => chooseQuickCategory(item)}>
                      <span aria-hidden="true" className="spad-ad-quick-category__dot" />
                      {item.label}
                      {selected ? <i className="simple-icon-check" aria-hidden="true" /> : null}
                    </button>
                  );
                })}
              </div>
              <small className="spad-ad-quick-categories__help">
                دسته‌های فعال مستقیماً فرم اختصاصی خود را باز می‌کنند؛ سایر دسته‌ها باید ابتدا در مدیریت دسته‌بندی‌ها تعریف شوند.
              </small>
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
