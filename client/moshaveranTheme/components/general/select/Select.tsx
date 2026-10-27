import React, { ReactNode, useEffect } from "react";
import ReactSelect, {
  ActionMeta,
  CSSObjectWithLabel,
  GroupBase,
  MultiValue,
  SelectComponentsConfig,
  SingleValue,
  Theme,
} from "react-select";
import SelectType, {
  FormatOptionLabelMeta,
} from "react-select/dist/declarations/src/Select";
import styles from "./select.module.scss";

export type ActionType = {
  text?: string;
  icon?: string;
  onClick?: (event: any) => void;
};

export interface DropDownType<T> {
  items?: T[] | null;
  value?: T | T[];
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
  formatOptionLabel?: (
    data: T,
    formatOptionLabelMeta: FormatOptionLabelMeta<T>
  ) => ReactNode;
}

export const selectStyles = (removeBorder?: boolean) => {
  return {
    styles: {
      control: (provided: CSSObjectWithLabel) => ({
        ...provided,
        borderRadius: 10,
        // height: 33,
        minHeight: 34,
        boxShadow: "none",
        borderWidth: removeBorder ? 0 : 1,
        fontSize: 12,
      }),
      dropdownIndicator: (provided: CSSObjectWithLabel) => ({
        ...provided,
        padding: 6,
        svg: { width: 16, height: 16 },
      }),
      indicatorSeparator: (provided: CSSObjectWithLabel) => ({
        ...provided,
        display: "none",
      }),
      valueContainer: (provided: CSSObjectWithLabel) => ({
        ...provided,
        padding: "0px 12px",
      }),
      placeholder: (provided: CSSObjectWithLabel) => ({
        ...provided,
        fontWeight: "bold",
        fontSize: 12,
      }),
      option: (provided: CSSObjectWithLabel) => ({
        ...provided,
        fontSize: 12,
      }),
      indicatorsContainer: (provided: CSSObjectWithLabel) => ({
        ...provided,
        minHeight: 30,
      }),
      input: (provided: CSSObjectWithLabel) => ({
        ...provided,
        padding: 0,
        margin: 0,
      }),
    },
    theme: (theme: Theme) => ({
      ...theme,
      borderRadius: 0,
      colors: {
        ...theme.colors,
        primary: "#0984e3",
      },
    }),
  };
};

export function Select<T = { label: string; value: string | number }>({
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
  formatOptionLabel,
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
      <ReactSelect
        options={items || []}
        placeholder={placeholder}
        {...selectStyles(removeBorder)}
        isMulti={isMulti}
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
        formatOptionLabel={formatOptionLabel}
      />
      {validationMessage && (
        <div className="validation-message">{validationMessage}</div>
      )}
    </div>
  );
}
