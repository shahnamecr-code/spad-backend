import { useFormikContext } from "formik";
import { useRouter } from "next/router";
import { useEffect, useState } from "react";
import { getSearchFilters } from "../../../../services/SearchServices";
import { ICategory } from "../../../../types/createAd.types";
import { IFilter } from "../../../../types/mainPage.types";
import { deepCopy, getFromOjectByKey } from "../../../../utils";
import { IFiltersProps } from "./Filters";

export const useFiltersDev = ({ onSearch, categories }: IFiltersProps) => {
  let { values, setFieldValue, setValues, resetForm } = useFormikContext<any>();
  const [showFilters, setShowFilters] = useState(false);
  const [filters, setFilters] = useState<
    {
      items: IFilter[];
      icon?: string | undefined;
      label?: string | undefined;
    }[]
  >([]);
  const router = useRouter();
  const [loading, setLoading] = useState(false);

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
    if (Object.keys(router.query).length && categories.length) {
      const newValues: any = {};
      const s = categories.find(
        (x) => x.slug == (router.query.transaction as string)
      );
      const application_type = s?.customer_children?.find(
        (x) => x.slug == (router.query.application_type as string)
      );
      const land_type = application_type?.customer_children?.find(
        (x) => x.slug == (router.query.land_type as string)
      );
      newValues.transaction = s;
      newValues.application_type = application_type;
      newValues.land_type = land_type;
      Object.keys(router.query).map((key) => {
        if (
          key !== "transaction" &&
          key !== "application_type" &&
          key !== "land_type"
        ) {
          let value: any = router.query[key];
          if (value == "false") {
            newValues[key] = false;
          } else if (value == "true") {
            newValues[key] = true;
          } else {
            if (typeof value === "string" && value.includes("-")) {
              const valArr = value.split("-");
              value = { from: valArr[0], to: valArr[1] };
            }
            newValues[key] = value;
          }
        }
      });
      console.log('newValues', newValues)
      getAds(router.query);
      setValues(newValues);
    }
  }, [router.query, categories]);

  useEffect(() => {
    if (transaction?.slug && !applicationType?.slug) {
      setLoading(true);
      getSearchFilters(transaction.slug)
        .then((res) => setFilters(res.data.searchItems))
        .finally(() => setLoading(false));
    }
  }, [transaction]);

  useEffect(() => {
    if (applicationType?.slug && !landType?.slug) {
      setLoading(true);
      getSearchFilters(applicationType.slug)
        .then((res) => setFilters(res.data.searchItems))
        .finally(() => setLoading(false));
    }
  }, [applicationType]);

  useEffect(() => {
    if (landType?.slug) {
      setLoading(true);
      getSearchFilters(landType.slug)
        .then((res) => setFilters(res.data.searchItems))
        .finally(() => setLoading(false));
    }
  }, [landType]);

  const getAds = (values: any) => {
    let newValues = deepCopy(values);
    newValues.category =
      values.land_type || values.application_type || values.transaction;
    delete newValues.transaction;
    delete newValues.land_type;
    delete newValues.application_type;
    onSearch(newValues);
  };

  return {
    showFilters,
    setShowFilters,
    values,
    setFieldValue,
    transaction,
    landType,
    applicationType,
    filters,
    resetForm,
    setValues,
    loading,
  };
};
