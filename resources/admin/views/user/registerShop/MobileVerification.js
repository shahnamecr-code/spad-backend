import React from "react";
import {Button, FormGroup, InputGroup, InputGroupAddon, InputGroupText, Label} from "reactstrap";
import {Field, useFormikContext} from "formik";
import Timer from "../../../components/Timer";
import axios from "axios";
import {adminPathApi} from "../../../constants/defaultValues";
import {NotificationManager} from "../../../components/common/react-notifications";


const MobileVerification = ({location}) => {
  const {setFieldValue, setTouched, values} = useFormikContext();
  const [btnSendAgain, setBtnSendAgain] = React.useState(true);
  const [editMobileBtn, setMobileBtn] = React.useState(true);

  const onHandleVerifyMobile = async (values) => {
    await axios.put(`${adminPathApi}/verifyMobile`, values)
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
    setMobileBtn(true);
    onHandleVerifyMobile(values)
  };

  React.useEffect(() => {
    if (!values.mobile) {
      setFieldValue('mobile', location.state.mobile);
    }
  }, [location.state.mobile]);


  return (
    <>
      <FormGroup className={'mb-4'}>
        <Label>شماره موبایل</Label>
        <InputGroup className="mb-3">
          <Field name={'mobile'} className={'form-control'} disabled={editMobileBtn}/>
          <InputGroupAddon addonType="append" onClick={() => setMobileBtn(false)}
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

export default MobileVerification;
