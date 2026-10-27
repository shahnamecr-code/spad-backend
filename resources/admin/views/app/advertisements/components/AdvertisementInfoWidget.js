import React, {useState} from "react";
import {Row} from "reactstrap";
import {items} from "../../../../data/carouselItems";
import {Colxx} from "../../../../components/common/CustomBootstrap";

const AdvertisementInfoWidget=({widget})=>{

  return (
    <>
      {(widget.widget_type=="default" && widget.info)?(
        <div className={"mb-3 default_widget"}>
          <p className="border-bottom pb-2 h6">{widget.info.title}</p>
          <div><p>{widget.info.value}</p></div>
        </div>
      ):''}
      {(widget.widget_type=="vertical" && widget.items)?(
        <Row className={"mx-0"}>
          {widget.items.map((item,index)=>(
            <Colxx key={index} lg={4} md={12} className={"vertical_widget_item mb-3"}>
              <div>
                <span className="border-bottom pb-1">{item.info.title}</span>
                <span>{item.info.value} {item.info.unit}</span>
              </div>
            </Colxx>
          ))}
        </Row>
      ):''}
      {(widget.widget_type=="group" && widget.items && widget.front_layout == "layout1")&&(
        <div className={"group_widget p-3 border mb-3"}>
          <div className={"group_widget_title h6"}>
            {widget.title}
          </div>
          <div className={`group_widget_content`}>
            <>
              {widget.items.map((item,index)=>(
                <Colxx lg={6} md={12}>
                  <div className={"vertical_widget_item"}>
                    <span>{item.info.title}</span>
                    <span>{item.info.value} {item.info.unit}</span>
                  </div>
                </Colxx>
              ))}
            </>
          </div>
        </div>
      )}
      {(widget.widget_type=="group" && widget.items && widget.front_layout == "layout2")&&(
        <div className={"group_widget2 p-3 border mb-3"}>
          <div className={"group_widget_title2 h6"}>
            {widget.title}
          </div>
          <div className={`group_widget_content2`}>
            <>
              {widget.items.map((item,index)=>(
                <div className={"vertical_widget_item2"}>
                  <span><img src={item.info.icon} /> {item.info.title} </span>
                </div>
              ))}
            </>
          </div>
        </div>
      )}
    </>
  );
}
export default AdvertisementInfoWidget;
