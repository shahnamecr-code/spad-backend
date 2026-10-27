import React from "react";
const GetDefaultQueryString = (search) => {
  const query = new URLSearchParams(search);
  let data = {};
  for (const [key, value] of query.entries()) {
    if (value === 'true') {
      data[key] = true;
    } else if (value === 'false') {
      data[key] = false;
    } else if (value === undefined) {
      data[key] = undefined;
    } else if (typeof value === "object") {
      data[key] = ''
    } else if (value.includes('-') && typeof value === "string") {
      let split = value.split('-');
      data[key] = {
        min:split[0] || undefined,
        max:split[1] || undefined
      }
    } else {
      data[key] = value;
    }
  }

  return {...data};
};
const GetQueryStringTextMode=(input)=>{
  let data={...input}
  Object.keys(data).map((key) => {
    if (typeof data[key] == "object") {
      if (data[key].hasOwnProperty('min') || data[key].hasOwnProperty('max')) {
        data[key] = `${data[key].min || ''}-${data[key].max || ''}`
      }else{
        delete data[key];
      }
    }else if(typeof data[key] === "undefined"){
      delete data[key];
    }else if(data[key] === 'undefined'){
      delete data[key];
    }
  });
  return data;
}

export {GetDefaultQueryString,GetQueryStringTextMode};
