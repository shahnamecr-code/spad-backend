import React, {useEffect, useState} from "react";
import {Button, Card, CardBody, FormGroup, Input, InputGroup, InputGroupAddon, Label, Row} from "reactstrap";
import {Colxx, Separator} from "../../../components/common/CustomBootstrap";
import { adminPathApi } from "../../../constants/defaultValues";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import classes from './css/visitRequests.module.css'
import AddVisit from './visitRequestPages/addVisit'
import ListVisit from './visitRequestPages/listVisit'


const VisitRequests = ({match}) => {
  const [type , setType] = useState("add");
  const [cate , setCate] = useState([])
  useEffect(()=>{
    axios.get(`${adminPathApi}/advertisement-category/displayCategoryFilters`)
      .then(res =>{
        setCate(res.data.categories)
        console.log(res)
      })
      .catch(({response}) => {
          alert(response)
      })
  },[])
  return (
    <>
      <Row>
        <Colxx md="12">
          <Breadcrumb heading="menu.visit-requests" match={match}/>
          <Separator className="mb-5"/>
        </Colxx>
      </Row>
      <Row>
        <Colxx md="12">
          <Card>
            <CardBody className={`d-flex flex-wrap justify-content-around`}>
              <Colxx
                onClick={()=> setType("add")}
                className={`${classes.glass} ${classes.bg_primaryy} mb-2`} 
              >
              <p>
                    افزودن بازدید
              </p>
              </Colxx>
              <Colxx 
                onClick={()=> setType("waiting")}
                className={`${classes.glass} ${classes.bg_warningg} mb-2`} 
              >
                <p>
                    آخرین درخواست ها
                </p>
              </Colxx>
              <Colxx 
                onClick={()=> setType("done")}
                className={`${classes.glass} ${classes.bg_successs} mb-2`} 
                >
                <p>
                   تایید شده
                </p>
              </Colxx>
              <Colxx 
                onClick={()=> setType("rejected")}
                className={`${classes.glass} ${classes.bg_dangerr} mb-2`} 
              >
                <p>
                  تایید نشده
                </p>
              </Colxx>
              <Colxx 
                onClick={()=> setType("expired")}
                className={`${classes.glass} ${classes.bg_secondaryy} mb-2`} 
              >
                <p>
                    منقضی شده
                </p>
              </Colxx>
            </CardBody>
          </Card>
        </Colxx>
      </Row>
      <Row>
        <Colxx md={12} className='mt-2'>
            {type == "add" ? 
              <AddVisit cate={cate} type={type} />
              :
              <ListVisit cate={cate} type={type} />
            }
        </Colxx>
      </Row>
    </>
  );
}

export default VisitRequests;
