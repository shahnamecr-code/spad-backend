import axios from "axios";

import ReactDOMServer from "react-dom/server";
import React, { useEffect, useMemo, useState } from "react";

import { Button, Card, Col, Modal, Row, Spinner } from "reactstrap";

import DropzoneComponent from "react-dropzone-component";
import { ContextMenu, ContextMenuTrigger, MenuItem } from "react-contextmenu";

import { renderFileImage } from "./utils";
import { SelectedFiles } from "./selectedFiles";

import { useSelector } from "react-redux";

import { adminPathApi } from "../../constants/defaultValues";
import { NotificationManager } from "../common/react-notifications";

import clsx from "clsx";
import swal from "sweetalert";

import "dropzone/dist/min/dropzone.min.css";
import EmptyFolder from "../../assets/img/empty-folder.png";

export const MediaLibrary = ({
  multiSelect,
  onSubmit,
  defaultFileIdz = [],
  setShow,
}) => {
  const [files, setFiles] = useState([]);
  const [loading, setLoading] = useState(true);
  const [uploading, setUploading] = useState(false);
  const [directories, setDirectories] = useState([]);
  const [selectedFiles, setSelectedFiles] = useState([...defaultFileIdz]);
  const [showDropzone, setShowDropzone] = useState(false);
  const [activeDirectories, setActiveDirectories] = useState([]);
  const [selectedDirectories, setSelectedDirectories] = useState([]);
  const [isCopiedToClipboard, setIsCopiedToClipboard] = useState(false);
  const [movementIdz, setMovementIdz] = useState({
    directories: [],
    files: [],
  });

  const shopId = useSelector((state) => state.authUser.shopId.value);
  const currentDirectoryId = useMemo(
    () =>
      activeDirectories.length
        ? activeDirectories[activeDirectories.length - 1]
        : "",
    [activeDirectories]
  );

  // ** general
  const goBack = () => {
    activeDirectories.splice(-1);
    setActiveDirectories(activeDirectories.splice(-1));
  };

  const getMedia = () => {
    setLoading(true);
    axios
      .get(`${adminPathApi}/media/get/${currentDirectoryId}`)
      .then((res) => res.data)
      .then((data) => {
        setDirectories(data.directories);
        setFiles(data.files);
        setLoading(false);
      });
  };

  const moveMedia = ({ type, id }) => {
    let movements = {
      directories: [...selectedDirectories],
      files: [...selectedFiles],
      from: currentDirectoryId,
    };
    if (type === "file") {
      if (!selectedFiles.includes(id)) {
        setSelectedFiles((old) => [...old, id]);
        movements.files.push(id);
      }
    }
    if (type === "directory") {
      if (!selectedDirectories.includes(id)) {
        setSelectedDirectories((old) => [...old, id]);
        movements.directories.push(id);
      }
    }
    navigator.clipboard.writeText(JSON.stringify(movements)).then(() => {
      setIsCopiedToClipboard(true);
      setMovementIdz(movements);
    });
  };

  const deleteMedia = ({ type, id }) => {
    swal({
      dangerMode: true,
      text: "از حذف انتخاب شده ها اطمینان دارید؟\nفایل های داخل پوشه ها حذف خواهند شد",
      buttons: ["انصراف", "حذف"],
      icon: "warning",
    }).then((result) => {
      if (result) {
        let deleteItems = {
          directories: [...selectedDirectories],
          files: [...selectedFiles],
        };
        if (type === "file") {
          if (!selectedFiles.includes(id)) {
            setSelectedFiles((old) => [...old, id]);
            deleteItems.files.push(id);
          }
        }
        if (type === "directory") {
          if (!selectedDirectories.includes(id)) {
            setSelectedDirectories((old) => [...old, id]);
            deleteItems.directories.push(id);
          }
        }
        deleteItems.directories = deleteItems.directories.map((item) =>
          item.toString()
        );
        deleteItems.files = deleteItems.files.map((item) => item.toString());
        console.log(deleteItems);
        axios
          .delete(`${adminPathApi}/media/remove`, {
            data: deleteItems,
          })
          .then((response) => {
            console.log(response);
            return response.data;
          })
          .then((result) => {
            if (result.status) {
              NotificationManager.success(result.message);
              setSelectedDirectories([]);
              setSelectedFiles([]);
              setMovementIdz({
                directories: [],
                files: [],
              });
              refreshMedia();
            } else {
              NotificationManager.error(result.message);
            }
          });
      }
    });
  };

  const submitSelectedFiles = () => {
    const returnValues = [];
    files.map((file) => {
      if (selectedFiles.includes(file.id) && !defaultFileIdz.includes(file.id))
        returnValues.push(file);
    });
    setShow(false);
    setSelectedFiles([]);
    onSubmit(returnValues);
  };

  const refreshMedia = () => {
    setShowDropzone(false);
    setSelectedDirectories([]);
    setSelectedFiles([]);
    getMedia();
  };

  const moveSelecteds = () => {
    navigator.clipboard.readText().then((res) => {
      const data = JSON.parse(res);
      if (data.from === currentDirectoryId) {
        setMovementIdz({
          directories: [],
          files: [],
        });
        setSelectedDirectories([]);
        setSelectedFiles([]);
      } else {
        axios
          .post(`${adminPathApi}/media/move`, {
            dist: currentDirectoryId,
            directories: data.directories,
            files: data.files,
          })
          .then((response) => response.data)
          .then((result) => {
            if (result.status) {
              NotificationManager.success(result.message);
              setIsCopiedToClipboard(false);
              setSelectedDirectories([]);
              setSelectedFiles([]);
              setMovementIdz({
                directories: [],
                files: [],
              });
              refreshMedia();
            } else {
              NotificationManager.error(result.message);
            }
          });
      }
    });
  };

  // ** directories
  const createFolder = () => {
    swal({
      title: "نام پوشه را وارد کنید",
      content: {
        element: "input",
        attributes: {
          type: "text",
        },
      },
      buttons: ["لغو", "ایجاد"],
    }).then((result) => {
      if (result.length > 0) {
        let body = {
          name: result,
        };
        if (activeDirectories.length) {
          body.directory = activeDirectories[activeDirectories.length - 1];
        }
        axios
          .post(`${adminPathApi}/directory`, body)
          .then((res) => res.data)
          .then((data) => {
            if (data.status) {
              getMedia();
              swal(data.message, {
                icon: "success",
                timer: 3000,
                buttons: false,
              });
            } else {
              swal(data.message, {
                icon: "error",
                timer: 3000,
                buttons: false,
              });
            }
          });
      }
    });
  };

  const openDirectory = (id) => {
    if (!activeDirectories.includes(id)) {
      setActiveDirectories((old) => [...old, id]);
    }
    setSelectedDirectories([]);
    setSelectedFiles([]);
  };

  const renameDirectory = (id) => {
    swal({
      title: "نام پوشه را وارد کنید",
      content: {
        element: "input",
        attributes: {
          type: "text",
        },
      },
      buttons: ["لغو", "ذخیره"],
    }).then((result) => {
      if (result.length > 0) {
        axios
          .put(`${adminPathApi}/directory/${id}/rename`, { name: result })
          .then((res) => res.data)
          .then((data) => {
            if (data.status) {
              getMedia();
              NotificationManager.success(data.message);
            } else {
              NotificationManager.error(data.message);
            }
          });
      }
    });
  };

  const removeDirectory = (id) => {};

  const handleSelectDirectory = (directoryId) => {
    if (!selectedDirectories.includes(directoryId)) {
      setSelectedDirectories((old) => [...old, directoryId]);
    } else {
      setSelectedDirectories(
        selectedDirectories.filter((dir_id) => dir_id !== directoryId)
      );
    }
  };

  // ** file
  const renameFile = (id) => {
    swal({
      title: "نام فایل را وارد کنید",
      content: {
        element: "input",
        attributes: {
          type: "text",
        },
      },
      buttons: ["لغو", "ذخیره"],
    }).then((result) => {
      if (result.length > 0) {
        axios
          .put(`${adminPathApi}/file/${id}/rename`, { name: result })
          .then((res) => res.data)
          .then((data) => {
            if (data.status) {
              getMedia();
              NotificationManager.success(data.message);
            } else {
              NotificationManager.error(data.message);
            }
          });
      }
    });
  };

  const searchFiles = (e) => {
    console.log(e.target.value);
  };

  const handleSelectFile = (fileId) => {
    if (multiSelect) {
      if (!selectedFiles.includes(fileId)) {
        setSelectedFiles((old) => [...old, fileId]);
      } else {
        setSelectedFiles(selectedFiles.filter((file_id) => file_id !== fileId));
      }
    } else {
      setSelectedFiles([fileId]);
    }
  };

  // ** config
  const dropzoneConfig = {
    headers: {
      "X-CSRF-TOKEN": document.querySelector('meta[name="token"]').content,
      shopId,
    },
    params: {
      directory: currentDirectoryId,
    },

    dictDefaultMessage: "فایل ها را برای ارسال اینجا بکشید",
    dictFallbackMessage:
      "مرورگر شما از کشیدن و رها سازی برای ارسال فایل پشتیبانی نمی کند.",
    dictFallbackText:
      "لطفا از فرم زیر برای ارسال فایل های خود مانند گذشته استفاده کنید.",
    dictInvalidFileType: "شما مجاز به ارسال این نوع فایل نیستید.",
    dictCancelUpload: "لغو ارسال",
    dictUploadCanceled: "ارسال لغو شد.",
    dictCancelUploadConfirmation: "آیا از لغو این ارسال اطمینان دارید؟",
    dictRemoveFile: "حذف فایل",
    dictMaxFilesExceeded: "شما نمی توانید فایل دیگری ارسال کنید.",
    addRemoveLinks: true,
    previewTemplate: ReactDOMServer.renderToStaticMarkup(
      <div className="dz-preview dz-file-preview mb-3">
        <div className="d-flex flex-row ">
          <div className="p-0 w-30 position-relative">
            <div className="dz-error-mark">
              <span>
                <i />{" "}
              </span>
            </div>
            <div className="dz-success-mark">
              <span>
                <i />
              </span>
            </div>
            <div className="preview-container">
              {/*  eslint-disable-next-line jsx-a11y/alt-text */}
              <img data-dz-thumbnail className="img-thumbnail border-0" />
              <i className="simple-icon-doc preview-icon" />
            </div>
          </div>
          <div className="pl-3 pt-2 pr-2 pb-1 w-70 dz-details position-relative">
            <div>
              {" "}
              <span data-dz-name />{" "}
            </div>
            <div className="text-primary text-extra-small" data-dz-size />
            <div className="dz-error-message">
              <span data-dz-errormessage />
            </div>
          </div>
        </div>
      </div>
    ),
  };

  // ** render sections
  const _renderFiles = useMemo(() => {
    return files.map((file) => {
      return (
        <React.Fragment key={file.id}>
          <ContextMenuTrigger id={`contextmenu-file-${file.id}`}>
            <div onClick={() => handleSelectFile(file.id)} key={file.id}>
              <Card
                className={clsx(
                  `file-manager__file-card`,
                  selectedFiles.includes(file.id) &&
                    "file-manager__file-card-selected",
                  movementIdz.files.includes(file.id) &&
                    "file-manager__file-card-movement"
                )}
              >
                {renderFileImage(file)}
                <span className={"file-manager__file-name"}>{file.name}</span>
              </Card>
            </div>
          </ContextMenuTrigger>

          <ContextMenu id={`contextmenu-file-${file.id}`}>
            <MenuItem onClick={() => renameFile(file.id)}>
              <span>تغییر نام</span>
            </MenuItem>
            <MenuItem onClick={() => handleSelectFile(file.id)}>
              <span>
                {selectedFiles.includes(file.id) ? "حذف انتخاب" : "انتخاب"}
              </span>
            </MenuItem>
            <MenuItem onClick={() => moveMedia({ type: "file", id: file.id })}>
              <span>
                {selectedDirectories.length > 0 || selectedFiles.length > 0
                  ? "انتقال انتخاب شده"
                  : "انتقال"}
              </span>
            </MenuItem>
            <MenuItem
              onClick={() => deleteMedia({ type: "file", id: file.id })}
            >
              <span>
                {selectedDirectories.length > 0 || selectedFiles.length > 0
                  ? "حذف انتخاب شده"
                  : "حذف"}
              </span>
            </MenuItem>
          </ContextMenu>
        </React.Fragment>
      );
    });
  }, [files, selectedFiles, movementIdz.files]);

  const _renderDirectories = useMemo(() => {
    return directories.map((directory) => {
      return (
        <React.Fragment key={directory.id}>
          <ContextMenuTrigger id={`contextmenu-directory-${directory.id}`}>
            <Button
              color="transparent"
              onDoubleClick={() =>
                !selectedDirectories.includes(directory.id) &&
                openDirectory(directory.id)
              }
              onClick={() => handleSelectDirectory(directory.id)}
              className={clsx(
                "file-manager__directory",
                selectedDirectories.includes(directory.id) &&
                  "file-manager__directory-selected",
                movementIdz.directories.includes(directory.id) &&
                  "file-manager__directory-movement"
              )}
            >
              <Row className="my-1 mr-1">
                <Col className="d-flex align-items-center">
                  <span
                    className={`simple-icon-folder-alt file-manager__icon`}
                  />
                  <span className="file-name m-0 ml-1 h6">
                    {directory.name}
                  </span>
                </Col>
              </Row>
            </Button>
          </ContextMenuTrigger>

          <ContextMenu id={`contextmenu-directory-${directory.id}`}>
            <MenuItem onClick={() => renameDirectory(directory.id)}>
              <span>تغییر نام</span>
            </MenuItem>
            <MenuItem onClick={() => handleSelectDirectory(directory.id)}>
              <span>
                {selectedDirectories.includes(directory.id)
                  ? "حذف انتخاب"
                  : "انتخاب"}
              </span>
            </MenuItem>
            <MenuItem
              onClick={() => moveMedia({ type: "directory", id: directory.id })}
            >
              <span>
                {selectedDirectories.length > 0 || selectedFiles.length > 0
                  ? "انتقال انتخاب شده"
                  : "انتقال"}
              </span>
            </MenuItem>
            <MenuItem
              onClick={() =>
                deleteMedia({ type: "directory", id: directory.id })
              }
            >
              <span>
                {selectedDirectories.length > 0 || selectedFiles.length > 0
                  ? "حذف انتخاب شده"
                  : "حذف"}
              </span>
            </MenuItem>
          </ContextMenu>
        </React.Fragment>
      );
    });
  }, [directories, selectedDirectories, movementIdz.directories]);

  useEffect(() => {
    getMedia();
  }, [activeDirectories]);

  return (
    <div className="file-manager">
      <div className="p-3 file-manager__content">
        <Row className="justify-content-between align-items-center">
          <Col className="align-items-center">
            <Button
              color="transparent"
              title="به روزرسانی"
              disabled={uploading}
              onClick={refreshMedia}
            >
              <span className="simple-icon-refresh file-manager__icon" />
            </Button>
            <Button
              color="transparent"
              title="آپلود فایل"
              onClick={() => setShowDropzone((prev) => !prev)}
            >
              <span
                className={`simple-icon-cloud-upload file-manager__icon
                                    ${
                                      showDropzone &&
                                      "file-manager__icon-active"
                                    }`}
              />
            </Button>
            <Button
              color="transparent"
              title="ایجاد پوشه"
              disabled={uploading}
              onClick={createFolder}
            >
              <span className="simple-icon-folder-alt file-manager__icon" />
            </Button>

            <Button
              color="transparent"
              title="ثبت"
              disabled={uploading}
              onClick={submitSelectedFiles}
            >
              <span className="simple-icon-check file-manager__icon" />
            </Button>

            {isCopiedToClipboard && (
              <Button
                color="transparent"
                disabled={uploading}
                title="انتقال انتخاب شده ها"
                onClick={moveSelecteds}
              >
                <span className="iconsminds-down-1 file-manager__icon" />
              </Button>
            )}
          </Col>

          <Col className="align-items-center">
            <Row className="align-items-center justify-content-end">
              {/* <Col md={8}>
                                <Input placeholder='جستجو' type='search' onChange={searchFiles} />
                            </Col> */}
              <Button
                color="transparent"
                disabled={activeDirectories.length < 1 || uploading}
                onClick={goBack}
              >
                <span className="simple-icon-arrow-left-circle file-manager__icon" />
              </Button>
            </Row>
          </Col>
        </Row>

        {showDropzone && (
          <div className="mt-3">
            <DropzoneComponent
              config={{
                postUrl: `${adminPathApi}/file/upload`,
              }}
              djsConfig={dropzoneConfig}
              eventHandlers={{
                addedfile: (file) => {
                  setUploading(true);
                },
                success: (event, response) => {
                  if (response.status) {
                    setTimeout(() => {
                      setShowDropzone(false);
                      getMedia();
                    }, 2000);
                  } else {
                    event._removeLink.click();
                    NotificationManager.error(response.message);
                  }
                },
                queuecomplete: (file) => {
                  setUploading(false);
                },
              }}
            />
          </div>
        )}

        {loading ? (
          <div className="file-manager__loading">
            <Spinner color="dark" size={"sm"} />
          </div>
        ) : (
          <Col>
            <Row className="mt-3">
              <div className="file-manager__directories">
                {_renderDirectories}
              </div>
            </Row>
            <Row className="mt-3">
              <div className="file-manager__files">{_renderFiles}</div>
              {!directories.length && !files.length && (
                <div className="file-manager__no-items mt-3">
                  <img
                    src={EmptyFolder}
                    className="file-manager__no-items-image"
                  />
                  <span className="file-manager__no-items-text">
                    فایلی وجود ندارد
                  </span>
                  {!showDropzone && (
                    <Button
                      className="d-flex align-items-center"
                      color="primary"
                      title="آپلود فایل"
                      onClick={() => setShowDropzone((prev) => !prev)}
                    >
                      <span>آپلود فایل جدید</span>
                      <span
                        className={clsx(
                          "simple-icon-cloud-upload",
                          "mx-2",
                          showDropzone && "file-manager__icon-active"
                        )}
                      />
                    </Button>
                  )}
                </div>
              )}
            </Row>
          </Col>
        )}
      </div>
    </div>
  );
};

const MediaLibraryButton = ({
  multiple,
  defaultFileIdz,
  onSubmit,
  onChange,
}) => {

  const [show, setShow] = useState(false);
  const [files, setFiles] = useState([]);

  const handleDelete = (id) => {
    setFiles(files.filter((f) => f.id !== id));
  };

  useEffect(() => {
    onChange(files);
  }, [files]);

  useEffect(() => {
    if (defaultFileIdz && defaultFileIdz.length) setFiles(defaultFileIdz);
  }, [defaultFileIdz]);

  return (
    <>
      <Button color={"primary"} onClick={() => setShow(true)} className="ml-1">
        انتخاب
      </Button>
      <div className="d-flex flex-wrap">
        <SelectedFiles files={files} onDelete={handleDelete} />
      </div>
      <Modal
        size="xl"
        className="p-2"
        style={{ background: "transparent" }}
        isOpen={show}
        toggle={() => setShow(!show)}
      >
        <MediaLibrary
          multiSelect={multiple}
          onSubmit={(filesValues) => {
            setFiles(filesValues);
            onSubmit(filesValues);
          }}
          defaultFileIdz={defaultFileIdz && defaultFileIdz.map((el) => el.id)}
          setShow={setShow}
        />
      </Modal>
    </>
  );
};

export default MediaLibraryButton;
