import React, { useEffect } from "react";
import {
  ActionMeta,
  GroupBase,
  MultiValue,
  PropsValue,
  SingleValue,
} from "react-select";
import CreatableSelect from "react-select/creatable";
import SelectType from "react-select/dist/declarations/src/Select";
import { selectStyles } from "../select/Select";
import styles from "./select.module.scss";

interface DropDownType<T> {
  items?: T[] | null;
  value?: PropsValue<T>;
  disabled?: boolean;
  onChange?: (
    newValue: MultiValue<T> | SingleValue<T>,
    actionMeta: ActionMeta<T>
  ) => void;
  width?: string | number;
  label?: string;
  placeholder?: string;
  isMulti?: boolean;
  id: string;
  displayExpr?: keyof T;
  valueKey?: keyof T;
  removeBorder?: boolean;
  name?: string;
  className?: string;
  validationMessage?: string;
  style?: React.CSSProperties;
}

export function Autocomplete<T = { label: string; value: string | number }>({
  className,
  items = [],
  width = "100%",
  disabled,
  label,
  placeholder = "انتخاب",
  isMulti,
  value,
  onChange,
  id,
  displayExpr = "label" as keyof T,
  removeBorder,
  valueKey = "value" as keyof T,
  name,
  style,
  validationMessage,
}: DropDownType<T>) {
  const ref: React.RefObject<HTMLDivElement> = React.createRef();

  const selectRef: React.RefObject<SelectType<T, boolean, GroupBase<T>>> =
    React.createRef();

  useEffect(() => {
    if (value === undefined) {
      selectRef.current?.clearValue();
    }
  }, [value]);

  return (
    <div
      style={{
        opacity: disabled ? 0.5 : 1,
        width: width,
        ...style,
      }}
      className={`${styles.container} ${className}`}
      ref={ref}
      onClick={(e) => e.stopPropagation()}
    >
      {label && <span className={styles.label}>{label}</span>}
      <CreatableSelect
        options={items || []}
        placeholder={placeholder}
        isClearable
        {...selectStyles(removeBorder)}
        formatCreateLabel={(inputValue) => "ایجاد " + inputValue}
        isMulti={isMulti}
        isValidNewOption={(inputValue, value, options, accessors) => {
          return Number.isInteger(parseInt(inputValue));
        }}
        isDisabled={disabled}
        value={value}
        getOptionLabel={(option) => (option as any)[displayExpr]}
        getOptionValue={(option) => (option as any)[valueKey]}
        onChange={onChange}
        instanceId={id}
        name={name}
        noOptionsMessage={() => "موردی وجود ندارد"}
        ref={selectRef}
        maxMenuHeight={150}
      />
      {validationMessage && (
        <div className="validation-message">{validationMessage}</div>
      )}
    </div>
  );
}
