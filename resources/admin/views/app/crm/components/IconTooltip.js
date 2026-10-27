import React, {useState} from "react";
import {Tooltip} from "reactstrap";

const IconTooltip = ({ id, prop,placement }) => {
  const [tooltipOpen, setTooltipOpen] = useState(false);

  return (
    <>
      <img className={"ads-detail-icon"} alt={prop.title} src={prop.icon} id={`tooltip_${id}`}/>
      <Tooltip
        placement={placement}
        isOpen={tooltipOpen}
        target={`tooltip_${id}`}
        toggle={() => setTooltipOpen(!tooltipOpen)}
      >
        {prop.title}
      </Tooltip>
    </>
  );
};
export default IconTooltip;
