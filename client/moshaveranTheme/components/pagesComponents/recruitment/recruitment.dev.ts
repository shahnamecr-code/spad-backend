import { useState } from "react";
import { getCitiesOfProvince } from "../../../services/PositionServices";
import { ILocation } from "../../../types/createAd.types";
import { useLoadingContext } from "../../../utils/context";

export const useRecruitmentDev = () => {
  const [cities, setCities] = useState<ILocation[]>();
  const [workExperienceCount, setWorkExperienceCount] = useState(1);
  const loadingContext = useLoadingContext();
  const studyDegreeItems = [
    { value: 0, label: "دیپلم" },
    { value: 1, label: "فوق دیپلم" },
    { value: 2, label: "لیسانس" },
    { value: 3, label: "فوق لیسانس" },
  ];
  const jobItems = [
    { value: 0, label: "مدیر فروش" },
    { value: 1, label: "کارشناس فروش – حرفه ای" },
    { value: 2, label: "مشاور – مبتدی" },
    { value: 3, label: "منشی(فقط خانم)" },
    { value: 4, label: "سرایدار" },
    { value: 5, label: "حسابدار" },
    { value: 6, label: "برنامه نویس" },
    { value: 7, label: "طراح و بلاگر" },
  ];

  const guaranteeItems = [
    { value: true, label: "بله" },
    { value: false, label: "خیر" },
  ];

  const handleChangeProvince = (provinceId: number) => {
    if (provinceId) {
      loadingContext.setLoading(true);
      getCitiesOfProvince(provinceId)
        .then((res) => {
          setCities(res.data);
        })
        .finally(() => loadingContext.setLoading(false));
    }
  };

  return {
    handleChangeProvince,
    cities,
    loadingContext,
    studyDegreeItems,
    jobItems,
    guaranteeItems,
    workExperienceCount,
    setWorkExperienceCount,
  };
};
