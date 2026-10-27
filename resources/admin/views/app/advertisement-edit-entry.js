import React, { useState } from "react";
import { Button, Card, CardBody, Input, Label } from "reactstrap";
import { useHistory } from "react-router-dom";
import { adminRoot } from "../../constants/defaultValues";

const AdvertisementEditEntry = () => {
  const [id, setId] = useState("");
  const history = useHistory();
  const go = () => {
    const value = String(id).trim();
    if (!value) return;
    history.push(`${adminRoot}/advertisements/edit/${encodeURIComponent(value)}`);
  };
  return (
    <div className="spad-admin-modern">
      <Card className="spad-edit-entry-card">
        <CardBody>
          <div className="spad-edit-entry-icon"><i className="simple-icon-pencil" /></div>
          <div className="spad-edit-entry-copy">
            <span>ADVERTISEMENT MANAGEMENT</span>
            <h2>ویرایش آگهی</h2>
            <p>کد آگهی را وارد کنید تا مستقیماً وارد صفحه ویرایش همان ملک شوید.</p>
          </div>
          <div className="spad-edit-entry-form">
            <Label>کد آگهی</Label>
            <Input value={id} onChange={(e)=>setId(e.target.value)} onKeyDown={(e)=>e.key==="Enter"&&go()} placeholder="مثلاً 1254" />
            <Button color="primary" onClick={go}>باز کردن صفحه ویرایش</Button>
          </div>
        </CardBody>
      </Card>
    </div>
  );
};
export default AdvertisementEditEntry;
