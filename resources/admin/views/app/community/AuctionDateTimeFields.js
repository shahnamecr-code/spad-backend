import React,{useState} from "react";

// The auction API stores Gregorian wall-clock values in Asia/Tehran.
// Convert calendars without browser-local Date parsing or third-party widgets.
const TEHRAN="Asia/Tehran";
const persian=new Intl.DateTimeFormat("fa-IR-u-ca-persian-nu-latn",{timeZone:"UTC",year:"numeric",month:"numeric",day:"numeric"});
const tehran=new Intl.DateTimeFormat("en-US-u-nu-latn",{timeZone:TEHRAN,year:"numeric",month:"numeric",day:"numeric",hour:"2-digit",minute:"2-digit",hourCycle:"h23"});
const monthNames=["فروردین","اردیبهشت","خرداد","تیر","مرداد","شهریور","مهر","آبان","آذر","دی","بهمن","اسفند"];
const weekdays=["ش","ی","د","س","چ","پ","ج"];
const pad=(n)=>String(n).padStart(2,"0");
const values=(formatter,date)=>Object.fromEntries(formatter.formatToParts(date).filter(p=>["year","month","day","hour","minute"].includes(p.type)).map(p=>[p.type,Number(p.value)]));
const jalaliOf=(date)=>values(persian,date);
const tehranNow=()=>{
 const now=values(tehran,new Date());
 return {...jalaliOf(new Date(Date.UTC(now.year,now.month-1,now.day))),hour:now.hour,minute:now.minute};
};
const yearStart=(year)=>{
 // Nowruz always lies within a few days of March 20 of Gregorian year + 621.
 const approx=Date.UTC(year+621,2,20);
 for(let i=-4;i<=4;i++){
  const day=approx+i*86400000;
  const p=jalaliOf(new Date(day));
  if(p.year===year&&p.month===1&&p.day===1)return day;
 }
 throw new Error("Persian calendar start unavailable");
};
const gregorianOf=(year,month,day)=>{
 const passed=(month<=7?(month-1)*31:186+(month-7)*30)+(day-1);
 const d=new Date(yearStart(year)+passed*86400000);
 return [d.getUTCFullYear(),d.getUTCMonth()+1,d.getUTCDate()];
};
const monthLength=(year,month)=>{
 if(month<=6)return 31;
 if(month<=11)return 30;
 return Math.round((yearStart(year+1)-yearStart(year))/86400000)-336;
};
export const normalizeAuctionTime=(value)=>{
 const result=value?String(value).replace("T"," ").slice(0,19):"";
 if(!/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(result))return "";
 const [y,m,d,h,minute,s]=result.match(/\d+/g).map(Number);
 const parsed=new Date(Date.UTC(y,m-1,d,h,minute,s));
 return parsed.getUTCFullYear()===y&&parsed.getUTCMonth()===m-1&&parsed.getUTCDate()===d&&h<24&&minute<60&&s<60?result:"";
};
const partsOf=(value)=>{
 const text=normalizeAuctionTime(value);
 if(!text)return null;
 const [y,m,d,h,minute]=text.match(/\d+/g).map(Number);
 try{return {...jalaliOf(new Date(Date.UTC(y,m-1,d))),hour:h,minute};}
 catch(e){return null;}
};
const serverTime=(y,m,d,h,minute)=>{
 const [gy,gm,gd]=gregorianOf(y,m,d);
 return [gy,pad(gm),pad(gd)].join("-")+" "+[pad(h),pad(minute),"00"].join(":");
};
export const persianAuctionTime=(value)=>{
 const p=partsOf(value);
 return p?[p.year,pad(p.month),pad(p.day)].join("/")+" - "+pad(p.hour)+":"+pad(p.minute):"هنوز تعیین نشده";
};
export const validAuctionTimeRange=(start,end)=>{
 const a=normalizeAuctionTime(start),b=normalizeAuctionTime(end);
 return Boolean(a&&b&&a<b);
};
const fields=[["starts_at","تاریخ و ساعت آغاز مزایده"],["ends_at","تاریخ و ساعت پایان مزایده"]];
const box={flex:"1 1 260px",minWidth:0,padding:12,background:"#f5faff",border:"1px solid #dbeaf6",borderRadius:12};
const control={border:"1px solid #c9e0ee",borderRadius:9,color:"#175a84",background:"#fff",fontFamily:"inherit",fontSize:12,cursor:"pointer"};
const calendarBtn={...control,height:34,minWidth:0,width:"100%",border:"none",background:"transparent",padding:0};
export default function AuctionDateTimeFields({values:dateValues,onChange,disabled=false}){
 const [open,setOpen]=useState(null);
 const [months,setMonths]=useState({});
 const show=(key)=>{
  if(disabled)return;
  const selected=partsOf(dateValues[key])||tehranNow();
  setMonths(prev=>({...prev,[key]:{year:selected.year,month:selected.month}}));
  setOpen(prev=>prev===key?null:key);
 };
 const move=(key,offset)=>{
  setMonths(prev=>{
   const current=prev[key]||tehranNow();
   let year=current.year,month=current.month+offset;
   if(month>12){month=1;year++;}
   if(month<1){month=12;year--;}
   return {...prev,[key]:{year,month}};
  });
 };
 const chooseDay=(key,year,month,day)=>{
  const prev=partsOf(dateValues[key]);
  const clock=prev||{hour:12,minute:0};
  onChange(key,serverTime(year,month,day,clock.hour,clock.minute));
 };
 const changeTime=(key,part,newValue)=>{
  const previous=partsOf(dateValues[key])||tehranNow();
  onChange(key,serverTime(previous.year,previous.month,previous.day,
   part==="hour"?Number(newValue):previous.hour,part==="minute"?Number(newValue):previous.minute));
 };
 return <div style={{display:"flex",gap:12,flexWrap:"wrap",direction:"rtl",margin:"8px 0 10px"}}>
  {fields.map(([key,label])=>{
   const selected=partsOf(dateValues[key]);
   const shown=months[key]||selected||tehranNow();
   const count=monthLength(shown.year,shown.month);
   const [startY,startM,startD]=gregorianOf(shown.year,shown.month,1);
   const weekday=(new Date(Date.UTC(startY,startM-1,startD)).getUTCDay()+1)%7;
   return <section key={key} style={box}>
    <label style={{display:"block",fontSize:12,color:"#1d6089",marginBottom:9,fontWeight:600}}>{label}</label>
    <button type="button" aria-expanded={open===key} onClick={()=>show(key)} disabled={disabled}
      style={{...control,width:"100%",padding:"10px 12px",height:44,textAlign:"center",fontWeight:600}}>
     {selected?persianAuctionTime(dateValues[key]):"انتخاب تاریخ و ساعت  ◷"}
    </button>
    {open===key&&<div style={{background:"#fff",marginTop:10,padding:10,border:"1px solid #d5e7f4",borderRadius:12,boxShadow:"0 9px 25px rgba(15,89,145,.08)"}}>
     <div style={{display:"flex",alignItems:"center",justifyContent:"space-between",gap:6,marginBottom:9}}>
      <button type="button" style={{...control,padding:"4px 10px"}} onClick={()=>move(key,-1)}>ماه قبل</button>
      <strong style={{fontSize:12,color:"#115e8e"}}>{monthNames[shown.month-1]} {shown.year.toLocaleString("fa-IR",{useGrouping:false})}</strong>
      <button type="button" style={{...control,padding:"4px 10px"}} onClick={()=>move(key,1)}>ماه بعد</button>
     </div>
     <div style={{display:"grid",gridTemplateColumns:"repeat(7,minmax(0,1fr))",gap:3}}>
      {weekdays.map((day,i)=><div key={i} style={{fontSize:10,textAlign:"center",color:"#7595ae",padding:5}}>{day}</div>)}
      {Array.from({length:weekday},(_,i)=><span key={"pad"+i}/>)}
      {Array.from({length:count},(_,i)=>i+1).map(day=><button key={day} type="button"
        aria-label={day+" "+monthNames[shown.month-1]}
        style={{...calendarBtn,background:selected&&selected.year===shown.year&&selected.month===shown.month&&selected.day===day?"#087ac0":"transparent",
         color:selected&&selected.year===shown.year&&selected.month===shown.month&&selected.day===day?"#fff":"#24617e",borderRadius:7}}
        onClick={()=>chooseDay(key,shown.year,shown.month,day)}>{day.toLocaleString("fa-IR")}</button>)}
     </div>
     <div style={{display:"flex",alignItems:"center",gap:8,borderTop:"1px solid #e6f0f8",marginTop:9,paddingTop:10}}>
      <span style={{fontSize:11,color:"#5e829c"}}>ساعت تهران</span>
      <select aria-label="ساعت" value={selected?selected.hour:12} onChange={e=>changeTime(key,"hour",e.target.value)}
       style={{...control,flex:1,padding:7}}>{Array.from({length:24},(_,i)=><option key={i} value={i}>{pad(i)}</option>)}</select>
      <span>:</span>
      <select aria-label="دقیقه" value={selected?selected.minute:0} onChange={e=>changeTime(key,"minute",e.target.value)}
       style={{...control,flex:1,padding:7}}>{Array.from({length:60},(_,i)=><option key={i} value={i}>{pad(i)}</option>)}</select>
     </div>
     <button type="button" style={{...control,width:"100%",background:"#0b7abe",color:"#fff",marginTop:10,padding:9}}
      onClick={()=>setOpen(null)}>تأیید زمان انتخاب‌شده</button>
    </div>}
    <div style={{fontSize:10,color:"#6b90a7",marginTop:8,lineHeight:1.9}}>{selected?"ساعت تهران · "+persianAuctionTime(dateValues[key]):"برای انتخاب تاریخ، کادر بالا را باز کنید."}</div>
   </section>;
  })}
 </div>;
}
