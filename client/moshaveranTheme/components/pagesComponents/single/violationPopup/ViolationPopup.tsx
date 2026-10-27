import { useState } from "react";
import { colors } from "react-select/dist/declarations/src/theme";
import { toast } from "react-toastify";
import { postAdReport } from "../../../../services/AdsServices";
import { ISingleAd } from "../../../../types/ads.types";
import Button from "../../../general/button/Button";
import Popup, { IPopupProps } from "../../../general/popup/Popup";
import { RadioButton } from "../../../general/radioButton/RadioButton";
import styles from "./violationPopup.module.scss";

interface IViolationPopup extends IPopupProps {
  ads_report_reasons: ISingleAd["ads_report_reasons"];
  advId: string;
}
const ViolationPopup = (props: IViolationPopup) => {
  const [checkedReportType, SetCheckedReportType] = useState<string>("");
  const [reportDesc, SetReportDesc] = useState<string>("");

  const handleReport = () => {
    if (checkedReportType.length > 0 && reportDesc.length > 0) {
      postAdReport(props.advId, {
        reason: checkedReportType,
        description: reportDesc,
      }).then((res) => {
        if (res.data.status) {
          toast.success(res.data.message);
          props.onHiding()
        } else {
          toast.error(res.data.message);
          props.onHiding()
        }
      });
    } else {
      toast.error("لطفا فیلد توضیحات و نوع تخلف را پر کنید.");
    }
  };
  return (
    <Popup width={400} {...props} title="ثبت مشکل آگهی">
      <div className={styles.container}>
        <h3 className={styles["main-title"]}>دقیقاً چه مشکلی وجود دارد؟</h3>
        <p className={styles.title}>
          نزدیک ترین گزینه را انتخاب کنید. ارائه جزییات بیشتر، به ما امکان بررسی
          سریع‌تر و دقیق‌تر مشکل را می‌دهد.
        </p>
        <div
          style={{ display: "flex", width: "100%", flexDirection: "column" }}
        >
          {props.ads_report_reasons?.length > 0 &&
            props.ads_report_reasons.map((item, i) => (
              <RadioButton
                key={i}
                label={item.title}
                className={styles["violation-item"]}
                onChange={(e) => SetCheckedReportType(e.target.id)}
                checked={checkedReportType === item.title ? true : false}
              />
            ))}
        </div>
        <textarea
          placeholder="توضیحات"
          rows={3}
          style={{ marginTop: 8 }}
          onChange={(e) => SetReportDesc(e.target.value)}
        />
        <hr className={styles.divider} />
        <div className="flex w-100">
          <Button
            onClick={props.onHiding}
            btnType="button"
            className={styles["cancel-btn"]}
            type="outlined"
          >
            انصراف
          </Button>
          <Button className={styles["confirm-btn"]} onClick={handleReport}>
            تأیید
          </Button>
        </div>
      </div>
    </Popup>
  );
};

export default ViolationPopup;
