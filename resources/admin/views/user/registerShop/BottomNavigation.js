import {WithWizard} from "react-albus";
import {Button} from "reactstrap";
import React from "react";


const BottomNavigation = ({className, onClickPrev, prevLabel = false, onClickNext, btnLoading}) => {

  return (
    <WithWizard
      render={({next, previous, step, steps, push}) => (
        <div className={`wizard-buttons ${className}`}>
          {prevLabel &&
          <Button
            color="primary"
            className={`mr-1 ${steps.indexOf(step) <= 0 ? 'disabled' : ''}`}
            onClick={() => {
              onClickPrev(previous, steps, step);
            }}>
            {"بازگشت"}
          </Button>
          }
          <Button
            color="primary"
            size={'lg'}
            className={
              `btn-multiple-state btn-shadow
              ${steps.indexOf(step) >= steps.length - 1 ? ' disabled' : ''}
              ${btnLoading ? ' show-spinner' : ''}`
            }
            onClick={() => {
              onClickNext(next, steps, step, push);
            }}
          >
            <span className="spinner d-inline-block">
              <span className="bounce1"/>
              <span className="bounce2"/>
              <span className="bounce3"/>
            </span>
            <span className={'label'}>{step.label}</span>
          </Button>
        </div>
      )}
    />
  );
};


export default BottomNavigation;
