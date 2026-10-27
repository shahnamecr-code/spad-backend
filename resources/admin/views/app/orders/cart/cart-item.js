import React, {Suspense, useState} from 'react';
import { Row , Card,CardBody, CardTitle, FormGroup, Label,Input, CustomInput,CardSubtitle, CardText ,Button, Modal, ModalHeader, ModalBody, ModalFooter} from 'reactstrap';
import {Colxx, Separator} from "../../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../../containers/navs/Breadcrumb";
import {Field, Form, Formik} from "formik";
import {FormikReactSelect} from "../../../../containers/form-validations/FormikFields";
import Select from "react-select";
import SearchSelect from "../../../../components/SearchSelect";
import axios from "axios";
import {adminPathApi} from "../../../../constants/defaultValues";

const CartItem = ({modal,toggle,submit,initialValues})=>{
  // from server
  const [super_attributes,setSuperAttributes]=React.useState([
/*    {code:'size',name:'اندازه',options:[
        {id:46,value:'s'},
        {id:47,value:'m'},
        {id:48,value:'lg'},
      ]},
    {code:'guaranty',name:'گارانتی',options:[
        {id:60,value:'6 month'},
        {id:61,value:'12 month'},
        {id:62,value:'18 month'},
      ]},
    {code:'color',name:'رنگ',options:[
        {id:50,value:'red'},
        {id:51,value:'blue'},
        {id:52,value:'green'},
      ]},*/
  ]);
  const [combines, setCombines] = React.useState({
    // 78: {size: 46, guaranty: 60, color: 50},
    // 79: {size: 48, guaranty: 61, color: 52},
    // 80: {size: 46, guaranty: 62, color: 52},
  });

  const [show_attributes,setShowAttributes]=React.useState([
/*    {code:'size',name:'اندازه',options:[
        {id:46,value:'s'},
        {id:47,value:'m'},
        {id:48,value:'lg'},
      ]},
    {code:'guaranty',name:'گارانتی',options:[
        {id:60,value:'6 month'},
        {id:61,value:'12 month'},
        {id:62,value:'18 month'},
      ]},
    {code:'color',name:'رنگ',options:[
        {id:50,value:'red'},
        {id:51,value:'blue'},
        {id:52,value:'green'},
      ]},*/
  ]);
  const [select_product,setSelectProduct]=React.useState(false);
  const [all_combinations,setAllCombinations]=React.useState([]);
  const [inventories,setInventories]=React.useState([]);
  const [variant_id,setVariantId]=React.useState(undefined);

  // current combine state
  const [current_combine,setCurrentCombine]=React.useState({});

  const getShopProductsList = (setloadOptions, search = '') => {
    axios
      .get(`${adminPathApi}/product/buy-products?page=1&per_page=10&search=${search}`)
      .then(res => res.data)
      .then(data => {
        setloadOptions(data.data)
      })
  }

  const getProductInventories=(product)=>{
    axios
      .get(`${adminPathApi}/product/${product}/product-variants`)
      .then(res=>res.data)
      .then(data=>{
        setAllCombinations(data.combinations)
        setSuperAttributes(data.super_attributes)
        setCombines(data.combines)
        setCurrentCombine({})
        setVariantId(undefined)
        setCombination(data.combinations,data.super_attributes,data.combines,{})
        // setFetch(prevState => !prevState)
      })
  }

  const setCombination=(arg_all_combinations,arg_super_attributes,arg_combines,fresh_combine)=>{
    if(!arg_super_attributes || Object.keys(arg_super_attributes).length==0){
      if(arg_all_combinations.length && arg_all_combinations[0].inventories){
        setInventories(arg_all_combinations[0].inventories)
        setVariantId(arg_all_combinations[0].id)
      }
      return false;
    }
    let codes=arg_super_attributes.map((attribute=>attribute.code))

    let filtered_combines={...arg_combines};
    if(fresh_combine) {
      let current_combine_props=Object.keys(fresh_combine)
      let current_combine_values=Object.values(fresh_combine)
      current_combine_props.map((prop,index)=>{
        if(!arg_super_attributes.find((attribute=>attribute.code==prop))){
          return false;
        }
        filtered_combines=Object.keys(filtered_combines)
          .filter((key) => filtered_combines[key][prop] == current_combine_values[index])
          .reduce((obj, key) => {
            obj[key] = filtered_combines[key];
            return obj;
          }, {})
      })
    }
    if(codes.length==Object.keys(fresh_combine).length){
      setVariantId(Object.keys(filtered_combines)[0])
      let inventories=all_combinations.find(cmb=>cmb.id)
      setInventories((inventories && inventories.inventories)?inventories.inventories:[])
    }else{
      setVariantId(undefined)
      setInventories([]);
    }

    let super_attributes_clone=[...arg_super_attributes];
    let selected={};
    Object.keys(filtered_combines).map((key) => {
      let filtered_combine=filtered_combines[key]
      let current_combine_props = Object.keys(filtered_combine)
      let current_combine_values = Object.values(filtered_combine)
      current_combine_props.map((prop, index) => {
        if (selected[prop]) {
          if (!selected[prop].includes(current_combine_values[index]))
            selected[prop].push(current_combine_values[index])
        } else {
          selected[prop] = [current_combine_values[index]];
        }
      })
    })
    super_attributes_clone.map((attribute,index)=>{
      super_attributes_clone[index]={...super_attributes_clone[index],options: attribute.options.filter(option=>selected[attribute.code].includes(option.id) || selected[attribute.code].includes(option.id.toString()))}
    })
    setShowAttributes(super_attributes_clone);
  };

  React.useEffect(()=>{
    setCombination(all_combinations,super_attributes,combines,current_combine)
  },[]);

  const onSubmit = (val) => {
    submit({...val,variant_id:variant_id,product_id:val.product?val.product.id:''});
  }

  return(
    <React.Fragment>
      <Modal size="lg" isOpen = {modal} toggle = {toggle}>
        <ModalHeader className = "bg-info text-white" toggle={toggle}>افزودن به سبد</ModalHeader>
        <ModalBody>
          <Formik enableReinitialize initialValues = {initialValues} onSubmit = {onSubmit}>
            {
              ({errors, touched, setFieldValue, setFieldTouched, values,setValues})=>(
                <Form>
                  <Row>
                    <Colxx md="12">
                      <FormGroup>
                        <label>محصول</label>
                        <SearchSelect
                          request={getShopProductsList}
                          setData={(product) => {
                            // setFieldValue('product', product)
                            getProductInventories(product.id)
                            setValues((values)=>{
                              return {...values,inventory_id:'',quantity:'','product':product}
                            })
                          }}
                          getOptionLabel={option=>option.product_code}
                          getOptionValue={option=>option.id}
                          name={"product"}
                          value={values.product}/>
                      </FormGroup>
                    </Colxx>
                    {(show_attributes && super_attributes.length>0 && show_attributes && show_attributes.length>0)?(
                      <>
                        <Colxx md="12">
                          <CardTitle>ویژگی های محصول</CardTitle>
                          <Separator className="my-3"/>
                        </Colxx>
                        {show_attributes.map(attribute => (
                          <Colxx md="6">
                            <FormGroup>
                              <label>
                                {attribute.name}
                              </label>
                              <Select
                                className="react-select"
                                classNamePrefix="react-select"
                                value={(current_combine[attribute.code] && attribute.options.find(option => option.id == current_combine[attribute.code])) ? attribute.options.find(option => option.id == current_combine[attribute.code]) : ''}
                                isClearable={true}
                                onChange={(value, item) => {
                                  let fresh = {...current_combine};
                                  switch (item.action) {
                                    case 'clear':
                                      delete fresh[attribute.code];
                                      setCurrentCombine(fresh)
                                      setCombination(all_combinations,super_attributes, combines, fresh)
                                      setValues((values)=>{
                                        return {...values,inventory_id:'',quantity:''}
                                      })
                                      break;
                                    case 'select-option':
                                      fresh[attribute.code] = value.id;
                                      setCurrentCombine(fresh)
                                      setCombination(all_combinations,super_attributes, combines, fresh)
                                      setValues((values)=>{
                                        return {...values,inventory_id:'',quantity:''}
                                      })
                                      break;
                                  }
                                }}
                                options={attribute.options}
                                getOptionLabel={option => option.value}
                                getOptionValue={option => option.id}/>
                            </FormGroup>
                          </Colxx>
                        ))}
                      </>
                    ):('')}
                    <Colxx md="12">
                      <CardTitle>انتخاب انبار</CardTitle>
                      <Separator className="my-3"/>
                    </Colxx>
                    <Colxx md = "6">
                      <FormGroup>
                        <Label>انبار</Label>
                        <FormikReactSelect
                          name = "inventory_id"
                          value = { values.inventory_id }
                          onChange = { setFieldValue }
                          onBlur = { setFieldTouched }
                          options = {inventories}
                          getOptionLabel={option=>`${option.inventory.name}/${option.sale_price}`}
                          getOptionValue={option=>option.id}
                        />
                      </FormGroup>
                    </Colxx>
                    <Colxx md = "6">
                      <FormGroup>
                        <Label>تعداد</Label>
                        <Field
                          name = "quantity"
                          className = "form-control text-left"
                          placeholder = {`موجودی : ${(inventories && inventories.length>0 && values.inventory_id)?inventories.find(item=>item.id==values.inventory_id).quantity:''}`}
                        />
                      </FormGroup>
                    </Colxx>
                  </Row>

                  <ModalFooter>
                    <Button type = "submit" className = "primary">افزودن</Button>
                    <Button outline className = "primary" onClick = {toggle}>بستن</Button>
                  </ModalFooter>
                </Form>
              )
            }
          </Formik>
        </ModalBody>

      </Modal>
    </React.Fragment>
  )
}

export default CartItem;
