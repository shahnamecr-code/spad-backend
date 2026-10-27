import React, { useEffect, useRef } from "react";
import { useState } from "react";
import { GroupBase, MultiValue, SelectInstance } from "react-select";
import { AsyncPaginate, LoadOptions } from "react-select-async-paginate";
import SelectType from "react-select/dist/declarations/src/Select";
import { getRegionsOfCityWithPage } from "../../../../services/PositionServices";
import { ILocation } from "../../../../types/createAd.types";
import { useAppContext } from "../../../../utils/context";
import { selectStyles } from "../../../general/select/Select";

export interface DropDownType<T> {
  disabled?: boolean;
  onChange: (newValue: MultiValue<T>) => void;
  width?: string | number;
  label?: string;
  placeholder?: string;
  valueKey?: keyof T;
  className?: string;
}

const RegionSelect = (props: DropDownType<ILocation>) => {
  const selectRef: React.RefObject<
    SelectType<ILocation, boolean, GroupBase<ILocation>>
  > = React.createRef();

  const [value, setValue] = useState<ILocation[]>();
  const context = useAppContext();

  useEffect(() => {
    setValue(context.regions);
  }, [context.regions]);

  useEffect(() => {
    if (value === undefined) {
      selectRef.current?.clearValue();
    }
  }, [value]);

  const [hasMore, setHasMore] = useState(false);

  useEffect(() => {
    setHasMore(true);
  }, [context.location]);

  const loadOptions: LoadOptions<
    ILocation,
    GroupBase<ILocation>,
    {
      page: number;
    }
  > = async (searchQuery, _loadedOptions, { page }: any) => {
    const response = await getRegionsOfCityWithPage({
      q: searchQuery,
      page: page,
      citiesId: context.location.cities?.map((x) => x.id || 0),
      provinceId: context.location.province?.id,
    })
      .then((res) => res.data)
      .finally(() => setHasMore(false));

    return {
      options: response.data,
      hasMore: response.next_page_url != null,
      additional: {
        page: searchQuery ? 2 : page + 1,
      },
    };
  };

  const onChange = (newValue: MultiValue<string | ILocation>) => {
    if (newValue) {
      props.onChange(newValue as ILocation[]);
      setValue(newValue as ILocation[]);
    }
  };

  const ref =
    useRef<SelectInstance<ILocation, true, GroupBase<ILocation>>>(null);

  return (
    <div
      className={props.className}
      style={{
        display: "flex",
        width: props.width,
        flexDirection: "column",
        opacity: props.disabled ? 0.5 : 1,
      }}
    >
      {props.label && <span style={{ marginBottom: 8 }}>{props.label}</span>}{" "}
      <AsyncPaginate
        instanceId="region-select"
        value={value}
        loadOptions={loadOptions}
        getOptionValue={(option: ILocation) => `${option.id}`}
        getOptionLabel={(option: ILocation) => option.name || ""}
        onChange={onChange}
        additional={{
          page: 1,
        }}
        isMulti
        placeholder={props.placeholder || "انتخاب"}
        {...selectStyles()}
        selectRef={ref}
        // cacheUniqs={[hasMore]}
        key={`${hasMore}`}
      />
    </div>
  );
};

export default RegionSelect;
