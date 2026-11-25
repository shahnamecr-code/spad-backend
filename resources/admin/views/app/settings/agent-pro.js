import React, { useEffect, useState } from 'react';
import axios from 'axios';
import { Button, Card, CardBody } from 'reactstrap';
import { adminPathApi } from '../../../constants/defaultValues';

const labels = { trial: 'دفاتر آزمایشی', active: 'دفاتر فعال', suspended: 'دفاتر تعلیق‌شده' };
const providers = {
  payments: 'پرداخت و اشتراک', registry: 'ثبت رسمی و استعلام', sms: 'پیامک', ai: 'هوش مصنوعی', bills: 'قبوض', social: 'انتشار بیرونی',
};
const statusText = { blocked: 'نیازمند اتصال', configured: 'پیکربندی‌شده', verified: 'اتصال تأییدشده' };

export default function AgentProSettings() {
  const [data, setData] = useState(null);
  const [attempt, setAttempt] = useState(0);
  const [error, setError] = useState('');

  useEffect(() => {
    let active = true;
    setData(null);
    setError('');
    axios.get(`${adminPathApi}/settings/agent-pro`).then(response => {
      if (active) setData(response.data.data);
    }).catch(e => {
      if (active) setError(e.response?.data?.message || 'وضعیت پنل مشاورین دریافت نشد.');
    });
    return () => { active = false; };
  }, [attempt]);

  return <Card dir="rtl">
    <CardBody>
      <h2>مدیریت پنل مشاورین املاک</h2>
      {error && <p role="alert">{error}</p>}
      {!error && !data && <p role="status">در حال دریافت وضعیت پنل مستقل…</p>}
      {data?.status === 'requires_configuration' && <p role="status">برای دریافت وضعیت، اتصال امن Agent را روی سرور پیکربندی کنید.</p>}
      {data?.status === 'unavailable' && <p role="status">پاسخ معتبر از پنل مستقل دریافت نشد.</p>}
      {data?.status === 'connected' && <>
        <h3>وضعیت دفاتر</h3>
        <dl>{Object.entries(data.offices).map(([key, count]) => <React.Fragment key={key}><dt>{labels[key]}</dt><dd>{Number(count).toLocaleString('fa-IR')}</dd></React.Fragment>)}</dl>
        <h3>اتصال سرویس‌ها</h3>
        <dl>{Object.entries(data.providers).map(([key, status]) => <React.Fragment key={key}><dt>{providers[key]}</dt><dd>{statusText[status]}</dd></React.Fragment>)}</dl>
        <a href="https://agent.spad.estate/" target="_blank" rel="noopener noreferrer">باز کردن پنل مستقل</a>
      </>}
      <div className="mt-3"><Button color="primary" onClick={() => setAttempt(value => value + 1)} disabled={!data && !error}>بررسی دوباره</Button></div>
    </CardBody>
  </Card>;
}
