import React, {useState} from "react";
import {Button,ButtonGroup, Card, CardBody, CardHeader, CardTitle} from "reactstrap";

const PropertyWrapperContent=({wrapper,history})=>{
  const [show,setShow]=useState(true)

  return (
    <Card className="mb-4">
      <CardHeader className="d-flex align-items-center justify-content-between bg-primary py-2">
        <CardTitle className="mb-0">{wrapper.title}</CardTitle>
        <ButtonGroup>
          <Button color="info" size="sm" onClick={()=>history.push(`edit/${wrapper.id}`)}>
            <span className="fa fa-pencil-alt"/>
          </Button>
          <Button color="info" size="sm" onClick={()=>setShow(!show)}>
            {show?(
              <span className="fa fa-angle-up"/>
            ):(
              <span className="fa fa-angle-down"/>
            )}
          </Button>
        </ButtonGroup>
      </CardHeader>
      <CardBody className={show?'':'d-none'}>

      </CardBody>
    </Card>
  );
}

export default PropertyWrapperContent;
