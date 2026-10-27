import React from "react";
import {Alert} from "reactstrap";

const AlertError = ({errors = {}}) => {
  const error_list = (typeof errors === 'object' && errors !== null && Object.keys(errors).length > 0) ? Object.values(errors) : [];

  const LiError = ({error, error_index}) =>
    error.map((value, index) => <li key={`${error_index}_${index}`}>{value}</li>);

  React.useEffect(() => {
    let top = document.getElementById("errors-box");
    if (top) {
      window.scrollTo(0, top.offsetTop)
      // top.scrollIntoView({ behavior: 'smooth', block: 'start'});
    }
    // console.log(top)
  }, [errors]);

  return (
    <>
      {error_list.length > 0 &&
      <Alert id="errors-box" color="danger" className={'rounded-sm'}>
        <ul className={'my-0 pl-3'}>
          {error_list.map((error, index) => (
            <LiError error={error} key={index} error_index={index}/>
          ))}
        </ul>
      </Alert>
      }
    </>
  )
}

export default AlertError;
