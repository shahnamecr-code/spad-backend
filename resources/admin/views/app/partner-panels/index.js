import React, {useEffect, useMemo, useState} from 'react';
import axios from 'axios';
import {
  Badge,
  Button,
  Card,
  CardBody,
  FormGroup,
  Input,
  Label,
  Modal,
  ModalBody,
  ModalFooter,
  ModalHeader,
  Row,
  Table,
} from 'reactstrap';
import {Colxx, Separator} from '../../../components/common/CustomBootstrap';
import Breadcrumb from '../../../containers/navs/Breadcrumb';
import {NotificationManager} from '../../../components/common/react-notifications';
import {adminPathApi} from '../../../constants/defaultValues';
import '../../../assets/css/sass/partner-panels.scss';

const partnerTypeFallback = {
  notary_office: 'دفاتر اسناد رسمی',
  developer: 'سازندگان',
  engineering_organization: 'دفاتر نظام مهندسی',
  investor: 'سرمایه‌گذاران',
};

const emptyForm = {
  user_id: '',
  first_name: '',
  last_name: '',
  mobile: '',
  email: '',
  password: '',
  password_confirmation: '',
  verify_mobile: true,
  type: 'notary_office',
  status: 'active',
  title: '',
  organization_name: '',
  registration_number: '',
  notes: '',
};

const PartnerPanels = ({match}) => {
  const [rows, setRows] = useState([]);
  const [meta, setMeta] = useState({types: {}, statuses: {}, total: 0, active: 0, counts: []});
  const [loading, setLoading] = useState(true);
  const [modalOpen, setModalOpen] = useState(false);
  const [saving, setSaving] = useState(false);
  const [editing, setEditing] = useState(null);
  const [form, setForm] = useState(emptyForm);
  const [filters, setFilters] = useState({search: '', type: '', status: ''});
  const [pagination, setPagination] = useState({current_page: 1, last_page: 1});

  const load = async (page = 1) => {
    setLoading(true);
    try {
      const {data} = await axios.get(`${adminPathApi}/partner-panels`, {
        params: {...filters, page, per_page: 20},
      });
      setRows(data.data.data || []);
      setPagination({
        current_page: data.data.current_page || 1,
        last_page: data.data.last_page || 1,
      });
      setMeta(data.meta || meta);
    } catch (error) {
      NotificationManager.error(error.response?.data?.message || 'دریافت پنل‌های همکاری انجام نشد.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    load(1);
  }, []);

  const partnerTypes = useMemo(() => (
    Object.keys(meta.types || {}).length ? meta.types : partnerTypeFallback
  ), [meta.types]);

  const statsByType = useMemo(() => {
    const output = {};
    (meta.counts || []).forEach((item) => {
      output[item.type] = (output[item.type] || 0) + Number(item.total || 0);
    });
    return output;
  }, [meta.counts]);

  const openCreate = () => {
    setEditing(null);
    setForm({...emptyForm, type: Object.keys(partnerTypes)[0] || 'notary_office'});
    setModalOpen(true);
  };

  const openEdit = (item) => {
    setEditing(item);
    setForm({
      ...emptyForm,
      user_id: item.user_id,
      first_name: item.user?.first_name || '',
      last_name: item.user?.last_name || '',
      mobile: item.user?.mobile || '',
      email: item.user?.email || '',
      verify_mobile: Boolean(item.user?.phone_verified_at || item.phone_verified),
      type: item.type,
      status: item.status,
      title: item.title || '',
      organization_name: item.organization_name || '',
      registration_number: item.registration_number || '',
      notes: item.notes || '',
    });
    setModalOpen(true);
  };

  const set = (key, value) => setForm((prev) => ({...prev, [key]: value}));

  const submit = async () => {
    const selectedType = form.type || Object.keys(partnerTypes)[0];
    if (!selectedType || !partnerTypes[selectedType]) {
      NotificationManager.error('لطفاً نوع پنل همکاری را انتخاب کنید.');
      return;
    }

    setSaving(true);
    try {
      const payload = editing
        ? {
            type: selectedType,
            status: form.status,
            title: form.title || null,
            organization_name: form.organization_name || null,
            registration_number: form.registration_number || null,
            notes: form.notes || null,
            password: form.password || null,
            password_confirmation: form.password_confirmation || null,
            verify_mobile: Boolean(form.verify_mobile),
          }
        : {
            first_name: form.first_name,
            last_name: form.last_name,
            mobile: form.mobile,
            email: form.email || null,
            password: form.password || null,
            password_confirmation: form.password_confirmation || null,
            verify_mobile: Boolean(form.verify_mobile),
            type: selectedType,
            status: form.status,
            title: form.title || null,
            organization_name: form.organization_name || null,
            registration_number: form.registration_number || null,
            notes: form.notes || null,
          };

      const response = editing
        ? await axios.put(`${adminPathApi}/partner-panels/${editing.id}`, payload)
        : await axios.post(`${adminPathApi}/partner-panels`, payload);

      NotificationManager.success(response.data.message || 'ذخیره شد.');
      setModalOpen(false);
      await load(pagination.current_page);
    } catch (error) {
      const errors = error.response?.data?.errors;
      const first = errors ? Object.values(errors).flat()[0] : null;
      NotificationManager.error(first || error.response?.data?.message || 'ذخیره پنل انجام نشد.');
    } finally {
      setSaving(false);
    }
  };

  const remove = async (item) => {
    if (!window.confirm(`پنل «${item.type_label}» این کاربر حذف شود؟ حساب کاربری اصلی حذف نخواهد شد.`)) return;
    try {
      const {data} = await axios.delete(`${adminPathApi}/partner-panels/${item.id}`);
      NotificationManager.success(data.message || 'حذف شد.');
      await load(pagination.current_page);
    } catch (error) {
      NotificationManager.error(error.response?.data?.message || 'حذف انجام نشد.');
    }
  };

  const toggleStatus = async (item) => {
    const next = item.status === 'active' ? 'suspended' : 'active';
    try {
      const {data} = await axios.put(`${adminPathApi}/partner-panels/${item.id}`, {
        type: item.type,
        status: next,
        title: item.title,
        organization_name: item.organization_name,
        registration_number: item.registration_number,
        notes: item.notes,
      });
      NotificationManager.success(data.message || 'وضعیت تغییر کرد.');
      await load(pagination.current_page);
    } catch (error) {
      NotificationManager.error(error.response?.data?.message || 'تغییر وضعیت انجام نشد.');
    }
  };

  return (
    <div className="spad-partner-admin">
      <Row>
        <Colxx xxs="12">
          <div className="text-zero top-right-button-container">
            <Button color="primary" size="lg" className="top-right-button" onClick={openCreate}>
              <i className="simple-icon-plus mr-2"/> ایجاد پنل همکاری
            </Button>
          </div>
          <Breadcrumb heading="menu.partner-panels" match={match}/>
          <Separator className="mb-4"/>
        </Colxx>
      </Row>

      <div className="spad-partner-admin__hero">
        <div>
          <span className="spad-partner-admin__eyebrow">SPAD Partner Access</span>
          <h2>مدیریت پنل‌های همکاری</h2>
          <p>ایجاد و کنترل دسترسی دفاتر اسناد رسمی، سازندگان، نظام مهندسی و سرمایه‌گذاران فقط از این بخش انجام می‌شود.</p>
        </div>
        <div className="spad-partner-admin__hero-lock"><i className="simple-icon-lock"/><span>Admin only</span></div>
      </div>

      <div className="spad-partner-admin__stats">
        <div><span>کل پنل‌ها</span><strong>{meta.total || 0}</strong></div>
        <div><span>پنل فعال</span><strong>{meta.active || 0}</strong></div>
        {Object.entries(partnerTypes).map(([key, label]) => (
          <div key={key}><span>{label}</span><strong>{statsByType[key] || 0}</strong></div>
        ))}
      </div>

      <Card className="mb-4 spad-partner-admin__card">
        <CardBody>
          <div className="spad-partner-admin__filters">
            <Input
              value={filters.search}
              onChange={(e) => setFilters({...filters, search: e.target.value})}
              onKeyDown={(e) => e.key === 'Enter' && load(1)}
              placeholder="جستجو: نام، موبایل، سازمان یا شماره ثبت"
            />
            <Input type="select" value={filters.type} onChange={(e) => setFilters({...filters, type: e.target.value})}>
              <option value="">همه انواع پنل</option>
              {Object.entries(partnerTypes).map(([value, label]) => <option key={value} value={value}>{label}</option>)}
            </Input>
            <Input type="select" value={filters.status} onChange={(e) => setFilters({...filters, status: e.target.value})}>
              <option value="">همه وضعیت‌ها</option>
              {Object.entries(meta.statuses || {}).map(([value, label]) => <option key={value} value={value}>{label}</option>)}
            </Input>
            <Button color="primary" onClick={() => load(1)}>اعمال فیلتر</Button>
          </div>

          <div className="spad-partner-admin__table-wrap">
            <Table responsive hover className="spad-partner-admin__table">
              <thead>
                <tr>
                  <th>کاربر</th>
                  <th>نوع پنل</th>
                  <th>عنوان / سازمان</th>
                  <th>وضعیت</th>
                  <th>تأیید موبایل</th>
                  <th>ایجاد</th>
                  <th className="text-center">مدیریت</th>
                </tr>
              </thead>
              <tbody>
                {loading && <tr><td colSpan="7" className="text-center py-5"><div className="loading"/></td></tr>}
                {!loading && !rows.length && <tr><td colSpan="7" className="text-center py-5 text-muted">هنوز پنل همکاری ایجاد نشده است.</td></tr>}
                {!loading && rows.map((item) => (
                  <tr key={item.id}>
                    <td>
                      <strong>{item.user ? `${item.user.first_name || ''} ${item.user.last_name || ''}` : '—'}</strong>
                      <div className="small text-muted" dir="ltr">{item.user?.mobile || '—'}</div>
                    </td>
                    <td><span className={`spad-partner-admin__type type-${item.type || 'unknown'}`}>{item.type_label || partnerTypes[item.type] || item.type || '—'}</span></td>
                    <td>
                      <strong>{item.title || item.organization_name || '—'}</strong>
                      {item.organization_name && item.title ? <div className="small text-muted">{item.organization_name}</div> : null}
                    </td>
                    <td><Badge color={item.status === 'active' ? 'success' : 'warning'}>{item.status_label}</Badge></td>
                    <td><Badge color={(item.phone_verified || item.user?.phone_verified_at) ? 'success' : 'secondary'}>{(item.phone_verified || item.user?.phone_verified_at) ? 'تأیید شده' : 'تأیید نشده'}</Badge></td>
                    <td className="small text-muted">{item.created_at ? new Date(item.created_at).toLocaleDateString('fa-IR') : '—'}</td>
                    <td>
                      <div className="spad-partner-admin__actions">
                        <Button size="sm" outline color="primary" onClick={() => openEdit(item)}>ویرایش</Button>
                        <Button size="sm" outline color={item.status === 'active' ? 'warning' : 'success'} onClick={() => toggleStatus(item)}>
                          {item.status === 'active' ? 'تعلیق' : 'فعال‌سازی'}
                        </Button>
                        <Button size="sm" outline color="danger" onClick={() => remove(item)}>حذف پنل</Button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </Table>
          </div>

          <div className="spad-partner-admin__pagination">
            <Button outline disabled={pagination.current_page <= 1 || loading} onClick={() => load(pagination.current_page - 1)}>قبلی</Button>
            <span>صفحه {pagination.current_page} از {pagination.last_page}</span>
            <Button outline disabled={pagination.current_page >= pagination.last_page || loading} onClick={() => load(pagination.current_page + 1)}>بعدی</Button>
          </div>
        </CardBody>
      </Card>

      <Modal isOpen={modalOpen} toggle={() => !saving && setModalOpen(false)} size="lg" className="spad-partner-admin__modal">
        <ModalHeader toggle={() => !saving && setModalOpen(false)}>{editing ? 'ویرایش پنل همکاری' : 'ایجاد پنل همکاری جدید'}</ModalHeader>
        <ModalBody>
          {!editing && (
            <>
              <div className="spad-partner-admin__section-title"><span>01</span><div><strong>حساب کاربر</strong><small>اگر این موبایل از قبل عضو SPAD باشد، همان حساب به پنل متصل می‌شود.</small></div></div>
              <Row>
                <Colxx md="6"><FormGroup><Label>نام</Label><Input value={form.first_name} onChange={(e) => set('first_name', e.target.value)}/></FormGroup></Colxx>
                <Colxx md="6"><FormGroup><Label>نام خانوادگی</Label><Input value={form.last_name} onChange={(e) => set('last_name', e.target.value)}/></FormGroup></Colxx>
                <Colxx md="6"><FormGroup><Label>شماره موبایل</Label><Input dir="ltr" value={form.mobile} onChange={(e) => set('mobile', e.target.value)} placeholder="09123456789"/></FormGroup></Colxx>
                <Colxx md="6"><FormGroup><Label>ایمیل (اختیاری)</Label><Input dir="ltr" type="email" value={form.email} onChange={(e) => set('email', e.target.value)}/></FormGroup></Colxx>
                <Colxx md="6"><FormGroup><Label>رمز عبور حساب</Label><Input dir="ltr" type="password" autoComplete="new-password" value={form.password} onChange={(e) => set('password', e.target.value)} placeholder="حداقل ۶ کاراکتر"/><small className="text-muted">برای حساب جدید الزامی است. اگر موبایل از قبل عضو SPAD باشد، رمز فعلی او تغییر نمی‌کند.</small></FormGroup></Colxx>
                <Colxx md="6"><FormGroup><Label>تکرار رمز عبور</Label><Input dir="ltr" type="password" autoComplete="new-password" value={form.password_confirmation} onChange={(e) => set('password_confirmation', e.target.value)} placeholder="تکرار رمز عبور"/></FormGroup></Colxx>
                <Colxx md="12">
                  <div className={`alert ${form.verify_mobile ? 'alert-success' : 'alert-light'} border mb-3`}>
                    <FormGroup check className="mb-1">
                      <Label check>
                        <Input type="checkbox" checked={Boolean(form.verify_mobile)} onChange={(e) => set('verify_mobile', e.target.checked)}/>{' '}
                        <strong>تأیید شماره تلفن همراه توسط ادمین</strong>
                      </Label>
                    </FormGroup>
                    <small>در صورت فعال بودن، شماره همین حالا در SPAD تأیید می‌شود و کاربر برای اولین ورود با رمز عبور نیازی به دریافت کد تأیید پیامکی ندارد.</small>
                  </div>
                </Colxx>
                <Colxx md="12"><div className="alert alert-light border mb-0"><strong>ورود پنل همکاری:</strong> <span dir="ltr">https://spad.estate/partner-login</span><br/><small>در صفحه اختصاصی پنل همکاری، ورود با رمز عبور و کد یکبارمصرف در دسترس است.</small></div></Colxx>
              </Row>
            </>
          )}

          {editing && (
            <>
              <div className="spad-partner-admin__user-summary">
                <i className="simple-icon-user"/>
                <div><strong>{form.first_name} {form.last_name}</strong><span dir="ltr">{form.mobile}</span></div>
                <Badge color="light">حساب کاربری #{editing.user_id}</Badge>
              </div>
              <div className="spad-partner-admin__section-title"><span>01</span><div><strong>رمز ورود حساب</strong><small>برای حفظ رمز فعلی، این دو فیلد را خالی بگذارید.</small></div></div>
              <Row>
                <Colxx md="6"><FormGroup><Label>رمز عبور جدید (اختیاری)</Label><Input dir="ltr" type="password" autoComplete="new-password" value={form.password} onChange={(e) => set('password', e.target.value)} placeholder="حداقل ۶ کاراکتر"/></FormGroup></Colxx>
                <Colxx md="6"><FormGroup><Label>تکرار رمز عبور جدید</Label><Input dir="ltr" type="password" autoComplete="new-password" value={form.password_confirmation} onChange={(e) => set('password_confirmation', e.target.value)} placeholder="تکرار رمز عبور"/></FormGroup></Colxx>
                <Colxx md="12">
                  <div className={`alert ${form.verify_mobile ? 'alert-success' : 'alert-warning'} border mb-3`}>
                    <FormGroup check className="mb-1">
                      <Label check>
                        <Input
                          type="checkbox"
                          checked={Boolean(form.verify_mobile)}
                          disabled={Boolean(editing?.user?.phone_verified_at || editing?.phone_verified)}
                          onChange={(e) => set('verify_mobile', e.target.checked)}
                        />{' '}
                        <strong>{form.verify_mobile ? 'شماره تلفن همراه تأیید شده است' : 'تأیید شماره تلفن همراه توسط ادمین'}</strong>
                      </Label>
                    </FormGroup>
                    <small>{form.verify_mobile ? 'این حساب می‌تواند مستقیماً با رمز عبور وارد شود.' : 'با فعال‌کردن این گزینه، تأیید شماره بدون ارسال کد پیامکی ثبت می‌شود.'}</small>
                  </div>
                </Colxx>
                <Colxx md="12"><div className="alert alert-light border mb-0"><strong>ورود پنل همکاری:</strong> <span dir="ltr">https://spad.estate/partner-login</span></div></Colxx>
              </Row>
            </>
          )}

          <div className="spad-partner-admin__section-title"><span>02</span><div><strong>نوع و وضعیت پنل</strong><small>این مقادیر تعیین می‌کنند کاربر در مرحله بعد به کدام امکانات دسترسی داشته باشد.</small></div></div>
          <Row>
            <Colxx md="6"><FormGroup><Label>نوع پنل</Label><Input type="select" value={form.type} onChange={(e) => set('type', e.target.value)}>{Object.entries(partnerTypes).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</Input></FormGroup></Colxx>
            <Colxx md="6"><FormGroup><Label>وضعیت دسترسی</Label><Input type="select" value={form.status} onChange={(e) => set('status', e.target.value)}>{Object.entries(meta.statuses || {}).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</Input></FormGroup></Colxx>
          </Row>

          <div className="spad-partner-admin__section-title"><span>03</span><div><strong>اطلاعات همکاری</strong><small>این فیلدها عمومی نیستند و مبنای توسعه امکانات اختصاصی هر پنل خواهند بود.</small></div></div>
          <Row>
            <Colxx md="6"><FormGroup><Label>عنوان پنل</Label><Input value={form.title} onChange={(e) => set('title', e.target.value)} placeholder="مثلاً دفترخانه ۱۲۳ شیراز"/></FormGroup></Colxx>
            <Colxx md="6"><FormGroup><Label>نام سازمان / مجموعه</Label><Input value={form.organization_name} onChange={(e) => set('organization_name', e.target.value)}/></FormGroup></Colxx>
            <Colxx md="6"><FormGroup><Label>شماره ثبت / مجوز</Label><Input value={form.registration_number} onChange={(e) => set('registration_number', e.target.value)}/></FormGroup></Colxx>
            <Colxx md="12"><FormGroup><Label>یادداشت داخلی ادمین</Label><Input type="textarea" rows="3" value={form.notes} onChange={(e) => set('notes', e.target.value)} placeholder="این متن در پنل کاربر نمایش داده نمی‌شود."/></FormGroup></Colxx>
          </Row>
        </ModalBody>
        <ModalFooter>
          <Button color="primary" disabled={saving} onClick={submit}>{saving ? 'در حال ذخیره...' : editing ? 'ذخیره تغییرات' : 'ایجاد پنل'}</Button>
          <Button color="secondary" outline disabled={saving} onClick={() => setModalOpen(false)}>انصراف</Button>
        </ModalFooter>
      </Modal>
    </div>
  );
};

export default PartnerPanels;
