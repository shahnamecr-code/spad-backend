import React, {useEffect, useState} from "react";
import axios from "axios";
import {adminPathApi} from "../../constants/defaultValues";
import {FormGroup, Label} from "reactstrap";
import Select from "react-select";

const RegionSelect = ({value, name, field_name, setFieldValue, city_id, isMulti = false}) => {
  const [options, setOptions] = useState([]);
  const [loading, setLoading] = useState(false);

  const getList = async () => {
    setLoading(true);
    await axios
      .get(
        `${adminPathApi}/location/city/${city_id}/regions?type=all`,
      )
      .then((res) => {
          return res.data;
        })
        .then((data) => {
          setOptions(data);
        });
      setLoading(false);
    };

    const onChange = (selected) => {
      setFieldValue(name, selected);
      if (field_name) {
        setFieldValue(field_name, selected.id)
      }
    }

    useEffect(() => {
      if (city_id) {
        getList();
      }
    }, [city_id]);

    if (!city_id) {
      return <></>
    }

    return (
      <FormGroup>
        <Label>خیابان</Label>
        <Select
          isMulti={isMulti}
          className={'react-select'}
          classNamePrefix={"react-select"}
          options={options}
          isLoading={loading}
          value={value}
          onChange={onChange}
          getOptionLabel={(item) => item.name}
          getOptionValue={(item) => item.id}
        />
      </FormGroup>
    )
  }
;


export default RegionSelect;
