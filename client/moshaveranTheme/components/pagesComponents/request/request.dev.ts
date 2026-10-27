import { useFormikContext } from "formik";
import { useEffect, useState } from "react";
import { getRequestFilters } from "../../../services/RequestServices";
import { ICategory } from "../../../types/createAd.types";
import { IFilter } from "../../../types/mainPage.types";
import { getFromOjectByKey } from "../../../utils";
import { useAppContext, useLoadingContext } from "../../../utils/context";

export const useRequestDev = () => {
  let { values, setFieldValue, setValues, resetForm } = useFormikContext<any>();
  const [showFilters, setShowFilters] = useState(false);
  const [fields, setFields] =
    useState<{ items: IFilter[]; icon?: string; label?: string }[]>();
  const loadingContext = useLoadingContext();
  const context = useAppContext();

  const transaction: ICategory | undefined = getFromOjectByKey(
    values,
    "transaction"
  ) as ICategory;

  const applicationType: ICategory | undefined = getFromOjectByKey(
    values,
    "application_type"
  ) as ICategory;

  const landType: ICategory | undefined = getFromOjectByKey(
    values,
    "land_type"
  ) as ICategory;

  useEffect(() => {
    context.getCategories();
  }, []);

  useEffect(() => {
    if (landType?.slug) {
      loadingContext.setLoading(true);
      getRequestFilters(landType.slug)
        .then((res) => setFields(res.data.searchItems))
        .finally(() => loadingContext.setLoading(false));
    }
  }, [landType]);

  return {
    showFilters,
    setShowFilters,
    values,
    setFieldValue,
    transaction,
    landType,
    applicationType,
    fields,
    resetForm,
    setValues,
    categories: context.categories,
    labels: context.labels,
  };
};
