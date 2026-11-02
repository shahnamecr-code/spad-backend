import React,{useCallback,useEffect,useMemo,useState} from 'react';
import axios from 'axios';
import {Link,useHistory,useLocation} from 'react-router-dom';
import {useSelector} from 'react-redux';
import {adminPathApi,adminRoot} from '../../../constants/defaultValues';

const BASE=adminPathApi+'/wallets';
const DRIVERS=[['zarinpal','زرین‌پال'],['zibal','زیبال'],['idpay','آیدی‌پی'],['payir','Pay.ir']];
const DEFAULTS={wallet_enabled:true,topup_enabled:true,wallet_title:'کیف پول اسپاد',wallet_intro:'',min_topup:100000,max_topup:100000000};
const emptyGateway=()=>({id:null,driver:'zarinpal',title:'زرین‌پال',enabled:false,is_default:false,config:{merchantId:'',currency:'T',mode:'normal'}});
const money=value=>Number(value||0).toLocaleString('fa-IR')+' تومان';
const n=value=>Number(value||0).toLocaleString('fa-IR');
const date=value=>value?new Date(value).toLocaleString('fa-IR'):'—';
const errorText=e=>e?.response?.data?.message||'ارتباط با سرور برقرار نشد. تغییرات ذخیره نشده‌اند.';
const CHANNELS=[
 {id:'overview',label:'نمای کلی',icon:'simple-icon-grid'},
 {id:'gateways',label:'درگاه پرداخت',icon:'simple-icon-credit-card'},
 {id:'wallets',label:'کیف پول کاربران',icon:'simple-icon-wallet'},
 {id:'transactions',label:'تراکنش‌ها',icon:'simple-icon-list'},
 {id:'settings',label:'تنظیمات کیف پول',icon:'simple-icon-settings'}
];
const PAID={paid:'پرداخت موفق',pending:'در انتظار پرداخت',failed:'ناموفق',cancelled:'لغوشده',expired:'منقضی‌شده'};
const moneyStatus=v=>v==='paid'?'good':v==='pending'?'wait':'bad';
const gatewayStatus=g=>!g.enabled?['غیرفعال','neutral']:!g.credential_registered?['اطلاعات اتصال ناقص','bad']:['پیکربندی ثبت‌شده؛ آزمون نشده','wait'];
const Badge=({children,tone='neutral'})=><span className={'spad-payment-pill '+tone}>{children}</span>;
const Loading=()=> <div className="spad-payment-empty">در حال دریافت اطلاعات از سرور…</div>;
const Pagination=({data,onPage})=><div className="spad-payment-pagination">
 <span>نتایج: {n(data?.total)} مورد</span>
 <div><button type="button" disabled={!data||data.current_page<=1} onClick={()=>onPage(data.current_page-1)}>قبلی</button>
 <span>صفحه {n(data?.current_page||1)} از {n(data?.last_page||1)}</span>
 <button type="button" disabled={!data||data.current_page>=data.last_page} onClick={()=>onPage(data.current_page+1)}>بعدی</button></div>
</div>;

export default function WalletCenter(){
 const user=useSelector(s=>s.authUser.currentUser);
 const superAdmin=Boolean(user&&Array.isArray(user.roles)&&user.roles.includes('Super Admin'));
 const history=useHistory(),location=useLocation();
 const initial=new URLSearchParams(location.search).get('tab');
 const [tab,setTab]=useState(CHANNELS.some(x=>x.id===initial)?initial:'overview');
 const [overview,setOverview]=useState(null),[settings,setSettings]=useState({...DEFAULTS}),[gateways,setGateways]=useState([]);
 const [editing,setEditing]=useState(null),[busy,setBusy]=useState(false),[loading,setLoading]=useState(true);
 const [failure,setFailure]=useState(''),[notice,setNotice]=useState('');
 const [users,setUsers]=useState(null),[userPage,setUserPage]=useState(1),[userSearch,setUserSearch]=useState(''),[userQuery,setUserQuery]=useState('');
 const [transactions,setTransactions]=useState(null),[txPage,setTxPage]=useState(1),[txSearch,setTxSearch]=useState(''),[txQuery,setTxQuery]=useState(''),[direction,setDirection]=useState('');
 const load=useCallback(async()=>{
  if(!superAdmin)return;
  setLoading(true);setFailure('');
  try{
   const [a,b]=await Promise.all([axios.get(BASE+'/center/overview'),axios.get(BASE+'/settings/all')]);
   setOverview(a.data.data||{});
   setGateways(b.data.gateways||[]);
   const v=b.data.settings||{};
   setSettings({...DEFAULTS,...v,wallet_enabled:String(v.wallet_enabled??'1')==='1',
    topup_enabled:String(v.topup_enabled??'1')==='1',min_topup:Number(v.min_topup||DEFAULTS.min_topup),
    max_topup:Number(v.max_topup||DEFAULTS.max_topup)});
  }catch(e){setFailure(errorText(e));}
  finally{setLoading(false);}
 },[superAdmin]);
 const loadUsers=useCallback(async()=>{
  if(!superAdmin)return;
  try{const {data}=await axios.get(BASE,{params:{page:userPage,per_page:15,search:userQuery}});
   setUsers(data);
  }catch(e){setFailure(errorText(e));}
 },[superAdmin,userPage,userQuery]);
 const loadTx=useCallback(async()=>{
  if(!superAdmin)return;
  try{const {data}=await axios.get(BASE+'/transactions/all',{params:{page:txPage,per_page:15,search:txQuery,direction}});
   setTransactions(data);
  }catch(e){setFailure(errorText(e));}
 },[superAdmin,txPage,txQuery,direction]);
 useEffect(()=>{load();},[load]);
 useEffect(()=>{const value=new URLSearchParams(location.search).get('tab');setTab(CHANNELS.some(x=>x.id===value)?value:'overview');},[location.search]);
 useEffect(()=>{if(tab==='wallets')loadUsers();},[tab,loadUsers]);
 useEffect(()=>{if(tab==='transactions')loadTx();},[tab,loadTx]);
 const selectTab=value=>{setTab(value);setFailure('');setNotice('');history.replace({pathname:location.pathname,search:'?tab='+value});};
 const commit=async(action,success)=>{
  setBusy(true);setFailure('');setNotice('');
  try{await action();setNotice(success);await load();return true;}
  catch(e){setFailure(errorText(e));return false;}
  finally{setBusy(false);}
 };
 const saveSettings=async e=>{
  e.preventDefault();
  if(Number(settings.max_topup)<Number(settings.min_topup)){setFailure('حداکثر شارژ باید از حداقل آن بزرگ‌تر باشد.');return;}
  await commit(()=>axios.post(BASE+'/settings/save',{
   wallet_enabled:Boolean(settings.wallet_enabled),topup_enabled:Boolean(settings.topup_enabled),
   wallet_title:settings.wallet_title,wallet_intro:settings.wallet_intro,
   min_topup:Number(settings.min_topup),max_topup:Number(settings.max_topup)
  }),'تنظیمات کیف پول ذخیره شد.');
 };
 const editGateway=item=>{
  setFailure('');setNotice('');
  setEditing(item?{
   id:item.id,driver:item.driver,title:item.title,enabled:Boolean(item.enabled),
   is_default:Boolean(item.is_default),
   config:{merchantId:'',currency:item.config?.currency||'T',mode:item.config?.mode||'normal'}
  }:emptyGateway());
 };
 const saveGateway=async e=>{
  e.preventDefault();if(!editing)return;
  const payload={...editing,config:{
   currency:editing.config.currency,mode:editing.config.mode,
   ...(editing.config.merchantId.trim()?{merchantId:editing.config.merchantId.trim()}:{})
  }};
  const url=BASE+'/gateways/'+(editing.id?editing.id+'/save':'save');
  const ok=await commit(()=>axios.post(url,payload),'اطلاعات درگاه ذخیره شد؛ اتصال واقعی هنوز آزمایش نشده است.');
  if(ok)setEditing(null);
 };
 const deleteGateway=async g=>{
  if(!window.confirm('درگاه «'+g.title+'» حذف شود؟ درگاهی که سابقه پرداخت دارد فقط غیرفعال می‌شود.'))return;
  await commit(()=>axios.delete(BASE+'/gateways/'+g.id),'درخواست حذف یا غیرفعال‌سازی درگاه ثبت شد.');
 };
 const configured=overview?.gateways?.filter(g=>g.enabled&&g.credential_registered).length||0;
 if(!superAdmin)return <div className="spad-payment-center" dir="rtl"><div className="spad-payment-alert">مدیریت درگاه‌های پرداخت و کیف پول کاربران فقط برای مدیر اصلی اسپاد قابل دسترسی است.</div></div>;
 return <div className="spad-payment-center" dir="rtl">
  <header className="spad-payment-hero">
   <div><span className="spad-payment-overline"><i className="simple-icon-wallet"/> مدیریت مالی اسپاد</span>
    <h1>درگاه و کیف پول</h1>
    <p>تنظیم درگاه‌های پرداخت، وب‌سرویس مالی، کیف پول کاربران و نظارت بر تراکنش‌های واقعی در یک مرکز یکپارچه</p>
   </div>
   <div className="spad-payment-hero__actions">
    <button type="button" disabled={busy||loading} onClick={load}><i className="simple-icon-refresh"/> به‌روزرسانی اطلاعات</button>
    <Link to={adminRoot+'/web-services'}>سایر وب‌سرویس‌ها</Link>
   </div>
  </header>
  <nav className="spad-payment-tabs" aria-label="بخش‌های درگاه و کیف پول">{CHANNELS.map(item=>
   <button type="button" key={item.id} className={tab===item.id?'active':''} onClick={()=>selectTab(item.id)}>
    <i className={item.icon}/> {item.label}
   </button>)}</nav>
  {failure&&<div className="spad-payment-alert" role="alert">{failure}</div>}
  {notice&&<div className="spad-payment-success" role="status">{notice}</div>}
  {loading&&!overview?<Loading/>:null}
  <div className="spad-payment-metrics">
   <article><small>مجموع موجودی کیف پول‌ها</small><strong>{money(overview?.total_wallet_balance)}</strong><i className="simple-icon-wallet"/></article>
   <article><small>شارژ آنلاین تأییدشده</small><strong>{money(overview?.total_paid_amount)}</strong><i className="simple-icon-check"/></article>
   <article><small>درگاه پیکربندی‌شده فعال</small><strong>{n(configured)} <small>از {n(overview?.gateways?.length)}</small></strong><i className="simple-icon-credit-card"/></article>
   <article><small>کاربران دارای کیف پول</small><strong>{n(overview?.total_wallet_users)}</strong><i className="simple-icon-people"/></article>
  </div>
  {tab==='overview'&&<div className="spad-payment-columns">
   <section className="spad-payment-card">
    <div className="spad-payment-section-head"><div><h2>وضعیت اتصال درگاه‌ها</h2><p>بر اساس تنظیمات ثبت‌شده، نه تست تراکنش بانکی</p></div><button onClick={()=>selectTab('gateways')}>مدیریت درگاه‌ها</button></div>
    {overview?.gateways?.length?overview.gateways.map(g=><div className="spad-payment-gateway-row" key={g.id}>
     <div className="spad-payment-provider-icon"><i className="simple-icon-credit-card"/></div>
     <div><b>{g.title}</b><small>{g.driver} · واحد: {g.currency==='R'?'ریال':'تومان'} {g.is_default?'· پیش‌فرض':''}</small></div>
     <Badge tone={gatewayStatus(g)[1]}>{gatewayStatus(g)[0]}</Badge>
    </div>):<div className="spad-payment-empty">درگاهی برای کیف پول ثبت نشده است.</div>}
    <div className="spad-payment-provider-hint"><i className="simple-icon-shield"/> کلیدهای درگاه به‌صورت رمزنگاری‌شده ذخیره می‌شوند؛ وضعیت «پیکربندی‌شده» به معنی تأیید اتصال واقعی نیست.</div>
   </section>
   <section className="spad-payment-card">
    <div className="spad-payment-section-head"><div><h2>آخرین درخواست‌های شارژ</h2><p>وضعیت ثبت‌شده در سامانه پس از بازگشت از درگاه</p></div><button onClick={()=>selectTab('transactions')}>تراکنش‌ها</button></div>
    <div className="spad-payment-table-wrap"><table><thead><tr><th>کاربر</th><th>درگاه</th><th>مبلغ (تومان)</th><th>وضعیت</th></tr></thead>
    <tbody>{(overview?.topups||[]).slice(0,9).map(x=><tr key={x.id}>
     <td><b>{x.user?.full_name||'نامشخص'}</b><small dir="ltr">{x.user?.mobile||''}</small></td>
     <td>{x.gateway?.title||'—'}</td><td>{n(x.amount)}</td>
     <td><Badge tone={moneyStatus(x.status)}>{PAID[x.status]||x.status}</Badge></td>
    </tr>)}
    {!(overview?.topups||[]).length&&<tr><td colSpan="4" className="empty">هنوز درخواستی ثبت نشده است.</td></tr>}</tbody></table></div>
   </section>
  </div>}
  {tab==='gateways'&&<div className="spad-payment-columns gateways">
   <section className="spad-payment-card">
    <div className="spad-payment-section-head"><div><h2>وب‌سرویس درگاه‌های پرداخت</h2><p>مدیریت اطلاعات اتصال، وضعیت و درگاه پیش‌فرض کیف پول</p></div><button className="primary" type="button" onClick={()=>editGateway(null)}>+ افزودن درگاه</button></div>
    {gateways.map(g=><div className="spad-payment-gateway-row" key={g.id}>
     <div className="spad-payment-provider-icon"><i className="simple-icon-credit-card"/></div><div>
      <b>{g.title}</b><small>{g.driver} {g.is_default?'· درگاه پیش‌فرض':''}</small></div>
     <Badge tone={g.enabled?'good':'neutral'}>{g.enabled?'فعال':'غیرفعال'}</Badge>
     <button onClick={()=>editGateway(g)}>تنظیمات</button>
     <button className="danger-quiet" onClick={()=>deleteGateway(g)} disabled={busy}>حذف</button>
    </div>)}
    {!gateways.length&&<div className="spad-payment-empty">درگاه فعالی ثبت نشده است.</div>}
    <div className="spad-payment-provider-hint">درگاه‌های پشتیبانی‌شده: زرین‌پال، زیبال، آیدی‌پی و Pay.ir. ذخیره کلید به تنهایی آزمون درخواست/تأیید تراکنش نیست.</div>
   </section>
   <section className="spad-payment-card">
    <h2>فرایند اتصال امن درگاه</h2>
    <div className="spad-payment-steps">
     <div><span>۱</span><p><b>اطلاعات وب‌سرویس</b><small>کلید پذیرندگی و واحد پول در تنظیمات درگاه ذخیره می‌شود.</small></p></div>
     <div><span>۲</span><p><b>تعیین وضعیت و پیش‌فرض</b><small>فقط درگاه‌های فعال برای شارژ کیف پول در دسترس کاربران هستند.</small></p></div>
     <div><span>۳</span><p><b>پرداخت و تأیید</b><small>تراکنش پس از اعتبارسنجی پاسخ درگاه در سامانه ثبت می‌شود.</small></p></div>
    </div>
    <div className="spad-payment-provider-hint"><i className="simple-icon-exclamation"/> این مرکز به جریان موجود شارژ کیف پول متصل است؛ برای پرداخت سایر سرویس‌های سایت، مسیرهای پرداخت مستقل همچنان جدا هستند.</div>
   </section>
  </div>}
  {tab==='settings'&&<section className="spad-payment-card">
   <div className="spad-payment-section-head"><div><h2>تنظیمات کیف پول کاربران</h2><p>تنظیمات عمومی و محدودیت‌های شارژ آنلاین</p></div></div>
   <form onSubmit={saveSettings} className="spad-payment-settings">
    <label><span>عنوان کیف پول</span><input required maxLength={200} value={settings.wallet_title||''} onChange={e=>setSettings({...settings,wallet_title:e.target.value})}/></label>
    <label><span>حداقل شارژ (تومان)</span><input required type="number" min="1000" value={settings.min_topup} onChange={e=>setSettings({...settings,min_topup:Number(e.target.value)})}/></label>
    <label><span>حداکثر شارژ (تومان)</span><input required type="number" min="1000" value={settings.max_topup} onChange={e=>setSettings({...settings,max_topup:Number(e.target.value)})}/></label>
    <label className="wide"><span>متن معرفی کیف پول</span><textarea rows={3} maxLength={1000} value={settings.wallet_intro||''} onChange={e=>setSettings({...settings,wallet_intro:e.target.value})}/></label>
    <label className="check"><input type="checkbox" checked={Boolean(settings.wallet_enabled)} onChange={e=>setSettings({...settings,wallet_enabled:e.target.checked})}/> فعال‌بودن سیستم کیف پول</label>
    <label className="check"><input type="checkbox" checked={Boolean(settings.topup_enabled)} onChange={e=>setSettings({...settings,topup_enabled:e.target.checked})}/> امکان شارژ آنلاین توسط کاربران</label>
    <div className="wide spad-payment-actions"><button className="primary" disabled={busy} type="submit">{busy?'در حال ثبت…':'ذخیره تنظیمات کیف پول'}</button></div>
   </form>
  </section>}
  {tab==='wallets'&&<section className="spad-payment-card">
   <div className="spad-payment-section-head"><div><h2>کیف پول کاربران</h2><p>موجودی واقعی کاربران، وضعیت حساب و مدیریت موردی</p></div><Badge tone="neutral">{n(users?.total)} کاربر</Badge></div>
   <form className="spad-payment-filter" onSubmit={e=>{e.preventDefault();setUserPage(1);setUserQuery(userSearch);}}>
    <input value={userSearch} onChange={e=>setUserSearch(e.target.value)} placeholder="جستجوی نام، موبایل یا ایمیل"/>
    <button className="primary" type="submit">جستجو</button></form>
   <div className="spad-payment-table-wrap"><table><thead><tr><th>کاربر</th><th>شماره موبایل</th><th>موجودی (تومان)</th><th>وضعیت</th><th>عملیات</th></tr></thead>
    <tbody>{(users?.data||[]).map(x=><tr key={x.id}>
     <td><b>{x.full_name||((x.first_name||'')+' '+(x.last_name||''))||'کاربر'}</b><small>شناسه {n(x.id)}</small></td>
     <td dir="ltr">{x.mobile||'—'}</td>
     <td>{n(x.wallet_balance)}</td>
     <td><Badge tone={x.wallet_status==='blocked'?'bad':'good'}>{x.wallet_status==='blocked'?'مسدود':'فعال'}</Badge></td>
     <td><Link className="spad-payment-view" to={adminRoot+'/wallets/user/'+x.id}>مدیریت کیف پول</Link></td>
    </tr>)}{users&&!users.data?.length&&<tr><td colSpan="5" className="empty">موردی یافت نشد.</td></tr>}</tbody></table>
   </div>{users?<Pagination data={users} onPage={setUserPage}/>:<Loading/>}
  </section>}
  {tab==='transactions'&&<section className="spad-payment-card">
   <div className="spad-payment-section-head"><div><h2>دفتر تراکنش‌های کیف پول</h2><p>ثبت‌های واقعی افزایش، کاهش و پرداخت‌های کیف پول کاربران</p></div><Badge>{n(transactions?.total)} تراکنش</Badge></div>
   <form className="spad-payment-filter" onSubmit={e=>{e.preventDefault();setTxPage(1);setTxQuery(txSearch);}}>
    <input placeholder="نام مشتری، موبایل یا کد مرجع" value={txSearch} onChange={e=>setTxSearch(e.target.value)}/>
    <select value={direction} onChange={e=>{setDirection(e.target.value);setTxPage(1);}}><option value="">همه تراکنش‌ها</option><option value="credit">واریز</option><option value="debit">برداشت</option></select>
    <button className="primary" type="submit">اعمال فیلتر</button></form>
   <div className="spad-payment-table-wrap"><table><thead><tr><th>کاربر</th><th>نوع</th><th>مبلغ (تومان)</th><th>موجودی بعد</th><th>شرح</th><th>تاریخ</th></tr></thead>
    <tbody>{(transactions?.data||[]).map(x=><tr key={x.id}>
     <td><b>{x.user?.full_name||'—'}</b><small dir="ltr">{x.user?.mobile||''}</small></td>
     <td><Badge tone={x.direction==='credit'?'good':'bad'}>{x.direction==='credit'?'واریز':'برداشت'}</Badge></td>
     <td>{x.direction==='credit'?'+':'−'}{n(x.amount)}</td><td>{n(x.balance_after)}</td>
     <td>{x.description||x.title||x.type||'—'}</td><td>{date(x.created_at)}</td>
    </tr>)}{transactions&&!transactions.data?.length&&<tr><td colSpan="6" className="empty">تراکنشی یافت نشد.</td></tr>}</tbody></table></div>
   {transactions?<Pagination data={transactions} onPage={setTxPage}/>:<Loading/>}
  </section>}
  {editing&&<div className="spad-payment-overlay" role="presentation" onMouseDown={e=>{if(e.target===e.currentTarget&&!busy)setEditing(null);}}>
   <form className="spad-payment-modal" onSubmit={saveGateway} role="dialog" aria-modal="true" aria-label="تنظیم درگاه پرداخت">
    <header><div><small>تنظیمات اتصال وب‌سرویس پرداخت</small><h2>{editing.id?'ویرایش درگاه پرداخت':'ثبت درگاه جدید'}</h2></div>
     <button type="button" disabled={busy} onClick={()=>setEditing(null)}>×</button></header>
    <div className="spad-payment-settings">
     <label><span>شرکت ارائه‌دهنده</span><select disabled={Boolean(editing.id)} value={editing.driver} onChange={e=>{const value=e.target.value;setEditing({...editing,driver:value,title:DRIVERS.find(x=>x[0]===value)?.[1]||value,config:{merchantId:'',currency:'T',mode:'normal'}});}}>
      {DRIVERS.map(([value,label])=><option value={value} key={value}>{label}</option>)}</select></label>
     <label><span>عنوان نمایشی</span><input required maxLength={100} value={editing.title} onChange={e=>setEditing({...editing,title:e.target.value})}/></label>
     <label className="wide"><span>کلید پذیرندگی / Merchant ID / API Key</span><input type="password" autoComplete="new-password" dir="ltr" placeholder={editing.id?'برای حفظ کلید فعلی خالی بگذارید':'کلید دریافتی از ارائه‌دهنده'} value={editing.config.merchantId||''} onChange={e=>setEditing({...editing,config:{...editing.config,merchantId:e.target.value}})}/><small>کلید قبلی نمایش داده نمی‌شود؛ واردکردن مقدار جدید آن را جایگزین می‌کند.</small></label>
     <label><span>واحد مبلغ ارسالی به درگاه</span><select value={editing.config.currency||'T'} onChange={e=>setEditing({...editing,config:{...editing.config,currency:e.target.value}})}><option value="T">تومان</option><option value="R">ریال</option></select></label>
     {editing.driver==='zarinpal'&&<label><span>حالت اتصال زرین‌پال</span><select value={editing.config.mode||'normal'} onChange={e=>setEditing({...editing,config:{...editing.config,mode:e.target.value}})}><option value="normal">عادی</option><option value="sandbox">آزمایشی (Sandbox)</option><option value="zaringate">زرین‌گیت</option></select></label>}
     <label className="check"><input type="checkbox" checked={Boolean(editing.enabled)} onChange={e=>setEditing({...editing,enabled:e.target.checked})}/> فعال‌بودن درگاه</label>
     <label className="check"><input type="checkbox" checked={Boolean(editing.is_default)} onChange={e=>setEditing({...editing,is_default:e.target.checked})}/> درگاه پیش‌فرض شارژ</label>
    </div>
    <p className="spad-payment-provider-hint">ذخیره تنظیمات اتصال، تراکنش آزمایشی ایجاد نمی‌کند؛ وضعیت واقعی نیازمند آزمون مستقل پرداخت و بازگشت است.</p>
    <footer><button type="button" disabled={busy} onClick={()=>setEditing(null)}>انصراف</button><button className="primary" type="submit" disabled={busy}>{busy?'در حال ذخیره…':'ذخیره ایمن درگاه'}</button></footer>
   </form>
  </div>}
 </div>;
}
