import styles from "./fileUploader.module.scss";
import { ChangeEvent, useEffect, useState } from "react";
import { RiImageAddFill } from "@react-icons/all-files/ri/RiImageAddFill";
import { FaTrashAlt } from "@react-icons/all-files/fa/FaTrashAlt";
import { toast } from "react-toastify";
import { uploadFile } from "../../../services/AdsServices";
import Image from "next/image";

interface IFile {
  preview?: string;
  raw?: File;
  visible: boolean;
}
interface IProps {
  onChange: (
    imagesAdress?: { id: string; url: string; index: number }[]
  ) => void;
  imagesAdress: { id: string; url: string; index: number }[];
}

const FileUploader = (props: IProps) => {
  const [images, setImages] = useState<IFile[]>([]);
  const [imagesAddress, setImagesAddress] = useState<
    { id: string; url: string; index: number }[]
  >([]);

  useEffect(() => {
    props.onChange(imagesAddress);
  }, [imagesAddress]);

  const handleChange = async (e: ChangeEvent<HTMLInputElement>) => {
    setValue(e.target.value);
    let fileArr: IFile[] = [];
    if (e.target?.files?.length)
      for (let index = 0; index < e.target?.files?.length; index++) {
        const file = e.target.files[index];
        upload(file, images.length + index);
        fileArr.push({
          preview: URL.createObjectURL(file),
          raw: file,
          visible: true,
        });
      }
    setImages((prev) => [...prev, ...fileArr]);
  };

  const upload = (currentFile: File, index: number) => {
    return uploadFile(currentFile, (event) => {
      const progress = Math.round((100 * event.loaded) / event.total);
      const el = document.getElementById(`progress-${index}`);
      if (el) el.style.width = `${progress}%`;
    })
      .then((response: any) => {
        setImagesAddress((prev) => [
          ...prev,
          {
            id: response.data.file?.id,
            url: response.data.file?.url,
            index: index,
          },
        ]);
      })
      .catch((e) => {
        if (!e.response.data.message) toast.error("خطا در بارگذاری فایل");
      })
      .finally(() => {
        setValue("");
      });
  };

  const removeImage = (index: number) => {
    const reducedArr = [...images];
    reducedArr[index].visible = false;
    setImages(reducedArr);
    setImagesAddress((prev) => prev.filter((x) => x.index !== index));
    setValue("");
  };

  const [value, setValue] = useState("");

  return (
    <div style={{ display: "flex", flexWrap: "wrap" }}>
      <label htmlFor="upload-button" className={styles["input-label"]}>
        <RiImageAddFill />
      </label>
      <input
        type="file"
        id="upload-button"
        style={{ display: "none" }}
        onChange={handleChange}
        multiple
        value={value}
      />
      {images.map(
        (img, i) =>
          img.visible && (
            <div key={i} className={styles["img-container"]}>
              <Image
                src={img.preview || ""}
                width="100%"
                height="100%"
                style={{ borderRadius: 6, objectFit: "cover" }}
                alt=""
              />
              <div
                className={styles["delete-btn"]}
                onClick={() => {
                  removeImage(i);
                }}
              >
                <FaTrashAlt />
              </div>
              {imagesAddress.findIndex((x) => x.index === i) === -1 && (
                <div className={styles["progress-container"]}>
                  <div className={styles.progress}>
                    <div
                      id={`progress-${i}`}
                      className={styles["progress-value"]}
                    ></div>
                  </div>
                </div>
              )}
            </div>
          )
      )}
    </div>
  );
};

export default FileUploader;
