import { useRouter } from "next/router";
import { useEffect } from "react";
import { deepCopy, paths, setStore } from "../../../utils";
import { AppContextType } from "../../../utils/context";
import { REGION } from "../../../utils/stores";

export const useSearchDev = (context: AppContextType) => {
  const router = useRouter();
  const filterInitialValue: any = {};

  useEffect(() => {
    context.getCategories();
  }, []);

  const filtersOnSubmit = (values: { [key: string]: any }) => {
    const valueForQuery = deepCopy(values);
    const query: { [key: string]: any } = {
      transaction: valueForQuery.transaction?.slug,
      application_type: valueForQuery.application_type?.slug,
      land_type: valueForQuery.land_type?.slug,
    };
    delete valueForQuery.transaction;
    delete valueForQuery.land_type;
    delete valueForQuery.application_type;

    Object.keys(valueForQuery).map((key) => {
      if (key === "position") {
        context.setRegions(valueForQuery[key].region);
        setStore(REGION, valueForQuery[key].region);
        delete valueForQuery.position;
      } else if (valueForQuery[key]?.from || valueForQuery[key]?.to) {
        query[key] = `${valueForQuery[key]?.from || ""}-${
          valueForQuery[key]?.to || ""
        }`;
      } else if (
        typeof valueForQuery[key] !== "object" ||
        Object.keys(valueForQuery[key]).length
      ) {
        query[key] = valueForQuery[key];
      }
    });
    router.push({ pathname: paths.search, query: query });
  };

  return {
    filtersOnSubmit,
    filterInitialValue,
    categories: context.categories,
    labels: context.labels,
  };
};
