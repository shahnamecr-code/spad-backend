import Button from "../button/Button";
import Popup, { IPopupProps } from "../popup/Popup";
import styles from "./messageBox.module.scss";

interface IProps extends IPopupProps {
  confirmMessage?: string;
  cancelMessage?: string;
  question?: string;
  onConfirm: () => void;
}

const MessageBox = ({
  confirmMessage = "بله",
  cancelMessage = "خیر",
  question = "اطلاعات حذف خواهد شد. آیا مطمئنید؟",
  ...props
}: IProps) => {
  return (
    <Popup {...props} width={300}>
      <div className={styles.container}>
        <p>{question}</p>
        <div className="btn-container">
          <Button type="outlined" color="gray" onClick={props.onHiding}>
            {cancelMessage}
          </Button>
          <Button onClick={props.onConfirm}>{confirmMessage}</Button>
        </div>
      </div>
    </Popup>
  );
};

export default MessageBox;
