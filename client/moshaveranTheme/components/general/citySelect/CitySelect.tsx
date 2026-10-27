import React, { useEffect } from "react";
import { useState } from "react";
import { SingleValue, GroupBase } from "react-select";
import { AsyncPaginate, LoadOptions } from "react-select-async-paginate";
import SelectType from "react-select/dist/declarations/src/Select";
import { IPagingCity } from "../../../types/createAd.types";
import { selectStyles } from "../select/Select";
import { getCitiesWithPage } from "../../../services/PositionServices";
import styles from "./citySelect.module.scss";

export interface DropDownType<T> {
  disabled?: boolean;
  onChange: (newValue: SingleValue<T>) => void;
  width?: string | number;
  label?: string;
  placeholder?: string;
  valueKey?: keyof T;
  className?: string;
  validationMessage?: string;
}

const CitySelect = (props: DropDownType<IPagingCity>) => {
  const selectRef: React.RefObject<
    SelectType<IPagingCity, boolean, GroupBase<IPagingCity>>
  > = React.createRef();

  const [value, setValue] = useState<IPagingCity>();

  useEffect(() => {
    if (value === undefined) {
      selectRef.current?.clearValue();
    }
  }, [value]);

  const loadOptions: LoadOptions<
    IPagingCity,
    GroupBase<IPagingCity>,
    {
      page: number;
    }
  > = async (searchQuery, _loadedOptions, { page }: any) => {
    const response = await getCitiesWithPage(page, searchQuery).then(
      (res) => res.data
    );

    return {
      options: response.data,
      hasMore: response.hasMore,
      additional: {
        page: searchQuery ? 2 : page + 1,
      },
    };
  };

  const onChange = (newValue: SingleValue<string | IPagingCity>) => {
    if (newValue && !(newValue instanceof Array)) {
      props.onChange(newValue as IPagingCity);
      setValue(newValue as IPagingCity);
    }
  };

  return (
    <div
      className={`${styles.container} ${props.className}`}
      style={{
        width: props.width,
        opacity: props.disabled ? 0.5 : 1,
      }}
    >
      {props.label && <span className={styles.label}>{props.label}</span>}{" "}
      <AsyncPaginate
        instanceId="city-select"
        value={value}
        loadOptions={loadOptions}
        getOptionValue={(option: any) => option.name}
        getOptionLabel={(option: any) => option.name}
        onChange={onChange}
        additional={{
          page: 1,
        }}
        placeholder={props.placeholder || "انتخاب"}
        {...selectStyles()}
      />
      {props.validationMessage && (
        <div className="validation-message">
          {props.validationMessage}
        </div>
      )}
    </div>
  );
};

export default CitySelect;
