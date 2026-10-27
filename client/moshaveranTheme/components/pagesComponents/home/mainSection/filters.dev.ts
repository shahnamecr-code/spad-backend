import { useRouter } from "next/router";
import { useState } from "react";
import { toast } from "react-toastify";
import { checkAdExist } from "../../../../services/MainPageServices";
import { IFilterData } from "../../../../types/mainPage.types";
import { deepCopy, paths } from "../../../../utils";
import { useLoadingContext } from "../../../../utils/context";

export const useHomeFiltersDev = (filters: IFilterData[]) => {
  const [searchByCode, setSearchbyCode] = useState(false);
  const [code, setCode] = useState("");
  const router = useRouter();
  const filterInitialValue: {} = {
    transaction: filters[0],
  };
  const loadingContext = useLoadingContext();

  const filtersOnSubmit = (values: { [key: string]: any }) => {
    if (searchByCode) {
      if (!code) toast.error("لطفا کد ملک را وارد کنید");
      else {
        loadingContext.setLoading(true);
        checkAdExist(code)
          .then((res) => {
            if (res.data.status) router.push(`${paths.single}/${code}`);
          })
          .finally(() => loadingContext.setLoading(false));
      }
    } else {
      const valueForQuery = deepCopy(values);
      const query: { [key: string]: any } = {
        transaction: valueForQuery.transaction?.slug,
        application_type: valueForQuery.application_type?.slug,
        land_type: valueForQuery.land_type?.slug,
      };
      delete valueForQuery.transaction;
      delete valueForQuery.application_type;
      delete valueForQuery.land_type;

      Object.keys(valueForQuery).map((key) => {
        if (
          valueForQuery[key]?.from !== undefined ||
          valueForQuery[key]?.to !== undefined
        )
          query[key] = `${valueForQuery[key]?.from || ""}-${
            valueForQuery[key]?.to || ""
          }`;
        else query[key] = valueForQuery[key];
      });
      router.push({ pathname: paths.search, query: query });
    }
  };

  return {
    filtersOnSubmit,
    filterInitialValue,
    searchByCode,
    setSearchbyCode,
    code,
    setCode,
  };
};
