import Link from "next/link";
import React, { useState } from "react";
import { useOutsideClick } from "../../../utils";
import Button from "../button/Button";
import classes from "./dropdown.module.scss";
export type ActionType = {
  text?: string;
  icon?: string;
  onClick?: (event: any) => void;
};

export interface DropDownType<T> {
  items?: T[];
  displayIcon?: keyof T;
  displayExpr?: keyof T;
  linkItem?: keyof T;
  typeItem?: keyof T;
  componentItem?: keyof T;
  containerClassName?: string;
  disabled?: boolean;
  onClickItem?: (item: T, index: number) => void;
  readOnly?: boolean;
  id?: string;
  BtnComponent?: JSX.Element;
}

export function DropDown<
  T extends { icon?: any; link?: string; title?: string; id?: string | number }
>({
  items = [],
  displayExpr = "title" as keyof T,
  displayIcon = "icon" as keyof T,
  linkItem = "link" as keyof T,
  typeItem = "type" as keyof T,
  componentItem = "component" as keyof T,
  containerClassName,
  disabled,
  id,
  onClickItem,
  readOnly,
  BtnComponent,
}: DropDownType<T>) {
  const ref: React.RefObject<HTMLDivElement> = React.createRef();
  const containerREF: React.RefObject<HTMLDivElement> = React.createRef();
  const [visible, setVisible] = useState(false);

  const renderItems = () =>
    visible &&
    items &&
    items.map((item, index) => {
      return (
        <React.Fragment key={index}>
          <li
            className={classes.dropdown_item_main}
            onClick={() => {
              onClickItem && onClickItem(item, index);
              // setVisible(false);
            }}
            title={`${item[displayExpr]}`}
            id={`${id}-${index + 1}`}
          >
            {item[linkItem] ? (
              <div className={classes.dropdown_item_container}>
                <Link href={`${item[linkItem]}`}>
                  <div className={classes.dropdown_item}>
                    {item.icon && item.icon}
                    <i className={`${item[displayIcon]}`}></i>
                    {`${item[displayExpr]}`}
                  </div>
                </Link>
              </div>
            ) : `${item[typeItem]}` == "btn" ? (
              <div>{`${item[componentItem]}`}</div>
            ) : (
              <div className={classes.dropdown_item_container}>
                <div className={classes.dropdown_item}>
                  <i className={`${item[displayIcon]}`}></i>
                  {`${item[displayExpr]}`}
                </div>
              </div>
            )}
          </li>
        </React.Fragment>
      );
    });

  useOutsideClick(ref, () => setVisible(false));

  return (
    <div className={`${classes.root} ${containerClassName} `} ref={ref}>
      <Button
        type="outlined"
        color="white"
        onClick={() => !readOnly && !disabled && setVisible(!visible)}
      >
        {BtnComponent}
      </Button>
      <div className={classes.dropdown_items} ref={containerREF}>
        {visible && <ul>{renderItems()}</ul>}
      </div>
    </div>
  );
}
