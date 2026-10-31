import React from "react";
import DateObject from "react-date-object";
import DatePicker from "react-multi-date-picker";
import TimePicker from "react-multi-date-picker/plugins/time_picker";
import persian from "react-date-object/calendars/persian";
import persianFa from "react-date-object/locales/persian_fa";
import gregorian from "react-date-object/calendars/gregorian";
import gregorianEn from "react-date-object/locales/gregorian_en";

// Server-side Laravel time zone: Asia/Tehran. These are wall-clock values:
// convert calendars only; NEVER parse through browser Date (which would
// silently shift Tehran times for administrators outside Iran).
export const normalizeAuctionTime = (value) => {
 const result = value ? String(value).replace("T", " ").slice(0, 19) : "";
 return /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(result) ? result : "";
};
const toPersianObject = (value) => {
 const text = normalizeAuctionTime(value);
 if (!text) return null;
 const date = new DateObject({date:text,format:"YYYY-MM-DD HH:mm:ss",calendar:gregorian,locale:gregorianEn});
 return date.isValid ? date.convert(persian, persianFa) : null;
};
const toServerTime = (selected) => {
 if (!selected || !selected.isValid) return "";
 // Clone so the date-picker can keep its own Persian DateObject.
 return new DateObject(selected).convert(gregorian, gregorianEn).format("YYYY-MM-DD HH:mm:ss");
};
export const persianAuctionTime = (value) => {
 const date = toPersianObject(value);
 return date ? date.format("YYYY/MM/DD - HH:mm") : "هنوز تعیین نشده";
};
export const validAuctionTimeRange = (start, end) => {
 const a=normalizeAuctionTime(start),b=normalizeAuctionTime(end);
 return Boolean(a&&b&&a<b);
};
const fields=[
 ["starts_at","تاریخ و ساعت آغاز مزایده"],
 ["ends_at","تاریخ و ساعت پایان مزایده"]
];
const card={flex:"1 1 240px",minWidth:0,padding:13,background:"#f5faff",border:"1px solid #dbeaf6",borderRadius:12};
const input={width:"100%",height:43,border:"1px solid #c9e0ee",borderRadius:9,color:"#175a84",background:"#fff",fontFamily:"inherit",fontSize:13,fontWeight:500,textAlign:"center",cursor:"pointer"};
export default function AuctionDateTimeFields({values,onChange,disabled=false}){
 return <div style={{display:"flex",gap:12,flexWrap:"wrap",direction:"rtl",margin:"8px 0 10px"}}>
  {fields.map(([key,label])=><div key={key} style={card}>
   <label style={{display:"block",fontSize:12,color:"#1d6089",marginBottom:9,fontWeight:600}}>{label}</label>
   <DatePicker
    value={toPersianObject(values[key])}
    onChange={(selected)=>onChange(key,toServerTime(selected))}
    calendar={persian}
    locale={persianFa}
    format="YYYY/MM/DD HH:mm"
    plugins={[<TimePicker key="clock" position="bottom" hideSeconds/>]}
    calendarPosition="bottom-center"
    zIndex={200000}
    portal
    editable={false}
    disabled={disabled}
    placeholder="انتخاب تاریخ و ساعت"
    inputMode="none"
    containerStyle={{width:"100%"}}
    style={input}
   />
   <div style={{fontSize:10,lineHeight:1.9,color:"#6b90a7",marginTop:8}}>
    {values[key]?"ساعت تهران · "+persianAuctionTime(values[key]):"برای باز کردن تقویم شمسی، روی کادر بزنید."}
   </div>
  </div>)}
 </div>;
}
