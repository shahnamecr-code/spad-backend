import { IoSearch } from "@react-icons/all-files/io5/IoSearch";
import React from "react";
import Button from "../button/Button";
import styles from "./searchBox.module.scss";

export interface IProps {
  placeHolder?: string;
  width?: string | number;
  height?: string | number;
  className?: string;
  inputClassName?: string;
  value?: string;
  name?: string;
  id?: string;
  onChange?: (value: string) => void;
  onClickSearch: () => void;
}

const SearchBox = (props: IProps) => {
  return (
    <div
      className={`${styles.container} ${props.className}`}
      style={{
        width: props.width,
      }}
      onSubmit={(e) => {
        e.preventDefault();
        e.stopPropagation();
        props.onClickSearch();
      }}
    >
      <input
        id={props.id || "input"}
        name={props.name}
        className={props.inputClassName}
        type="text"
        placeholder={props.placeHolder}
        value={props.value || ""}
        onChange={(e) => {
          props.onChange && props.onChange(e.target.value);
        }}
        onKeyDown={(e) => e.code === "Enter" && props.onClickSearch()}
      />
      <Button type="text" onClick={props.onClickSearch}>
        <IoSearch />
      </Button>
    </div>
  );
};

export default SearchBox;
