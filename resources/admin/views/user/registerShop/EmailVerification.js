import React from "react";
import {Button, FormGroup, InputGroup, InputGroupAddon, InputGroupText, Label} from "reactstrap";
import {Field, useFormikContext} from "formik";
import Timer from "../../../components/Timer";
import axios from "axios";
import {adminPathApi} from "../../../constants/defaultValues";
import {NotificationManager} from "../../../components/common/react-notifications";


const EmailVerification = ({location}) => {
  const {setFieldValue, setTouched, values} = useFormikContext();
  const [btnSendAgain, setBtnSendAgain] = React.useState(true);
  const [editEmailBtn, setEmailBtn] = React.useState(true);

  const onHandleVerifyEmail = async (values) => {
    await axios.put(`${adminPathApi}/verifyEmail`, values)
      .then(res => {
        if (res.data.status) {
          NotificationManager.success(res.data.message);
        } else {
          NotificationManager.warning(res.data.message);
        }
      })
      .catch(error => {

      });
  }

  const sendRequestForCode = () => {
    setBtnSendAgain(true);
    setEmailBtn(true);
    onHandleVerifyEmail(values)
  };

  React.useEffect(() => {
    if (!values.email) {
      setFieldValue('email', location.state.email);
    }
  }, [location.state.email]);


  return (
    <>
      <FormGroup className={'mb-4'}>
        <Label>ایمیل</Label>
        <InputGroup className="mb-3">
          <Field name={'email'} className={'form-control'} disabled={editEmailBtn}/>
          <InputGroupAddon addonType="append" onClick={() => setEmailBtn(false)}
                           style={{cursor: "pointer"}}>
            <InputGroupText><i className={'simple-icon-pencil'}/></InputGroupText>
          </InputGroupAddon>
        </InputGroup>
      </FormGroup>
      <FormGroup className={'mb-4'}>
        <Label>کد تایید</Label>
        <Field className="form-control" name={'verify_code'}/>
      </FormGroup>
      <div className={'d-flex align-items-center'}>
        <Button color={'secondary'} outline
                className={'d-flex justify-content-start align-items-center'}
                disabled={btnSendAgain}
                onClick={sendRequestForCode}>
          ارسال مجدد
        </Button>
        {btnSendAgain &&
        <Timer timerInSecond={5 * 60} className={'p-2'} onFinish={() => setBtnSendAgain(false)}/>
        }
      </div>
    </>
  )
};
export default EmailVerification;
