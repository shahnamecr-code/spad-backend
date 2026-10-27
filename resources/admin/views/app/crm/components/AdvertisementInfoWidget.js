import React, {useState} from "react";
import {Row} from "reactstrap";
import {items} from "../../../../data/carouselItems";
import {Colxx} from "../../../../components/common/CustomBootstrap";

const AdvertisementInfoWidget=({widget})=>{
  const [show,setShow]=useState(true);

  return (
    <>
      {(widget.widget_type=="default" && widget.info)?(
        <div className={"mb-2 default_widget"}>
          {widget.info.value}
        </div>
      ):''}
      {(widget.widget_type=="horizontal" && widget.items)?(
        <Row className={"mb-2"}>
          {widget.items.map((item,index)=>(
            <Colxx key={index} xs={4} className={"horizontal_widget_item"}>
                <span>{item.info.title}</span>
                <span className={"text-center"}>{item.info.value} {item.info.unit}</span>
            </Colxx>
          ))}
        </Row>
      ):''}
      {(widget.widget_type=="vertical" && widget.items)?(
        <Row className={"mb-2 mx-0"}>
          {widget.items.map((item,index)=>(
            <Colxx key={index} xs={12} className={"vertical_widget_item"}>
              <span>{item.info.title}</span>
              <span>{item.info.value} {item.info.unit}</span>
            </Colxx>
          ))}
        </Row>
      ):''}
      {(widget.widget_type=="group" && widget.items)?(
        <div className={"group_widget"}>
          <div className={"group_widget_title"} onClick={()=>setShow(!show)}>
            {widget.title}
            <span className={`fa ${show?'fa-angle-up':'fa-angle-down'}`}/>
          </div>
          <div className={`group_widget_content ${show?'':'d-none'}`}>
            <>
              {widget.items.map((item,index)=>(
                <div className={"vertical_widget_item"}>
                  <span>{item.info.title}</span>
                  <span>{item.info.value} {item.info.unit}</span>
                </div>
              ))}
            </>
          </div>
        </div>
      ):''}
    </>
  );
}
export default AdvertisementInfoWidget;
