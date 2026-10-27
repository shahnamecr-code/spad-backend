import React from "react";

const CrmRequestPreviewPropertyItem=({property})=>{
  return (
    <p className={"crm-preview-property-group"}>
      <span>{property.title}</span>
      <span>
        {property.range_search?(
          <span>{`${property.range_search ? property.range.from : ''} - ${property.range_search ? property.range.to : ''} ${property.unit??''}`}</span>
        ):(
          <>
            {property.type=='exact' && (
              <span>
                {`${property.exact??''} ${property.unit??''}`}
              </span>
            )}
            {property.type=='in' && (
              <span>
                {`${property.in?property.in.join(' | '):''} ${property.unit??''}`}
              </span>
            )}
            {property.type=='has' && (
              <span>
                {property.has ? <span className="fa fa-check"/> : ''}
              </span>
            )}
          </>
        )}
      </span>
    </p>
  );
}

export default CrmRequestPreviewPropertyItem;
