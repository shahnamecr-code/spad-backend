import React from "react";

const calculateTimeLeft = () => {
  let t = new Date();
  let difference = t.setSeconds(t.getSeconds() + 5 * 60) - new Date();
  let timeLeft = {};

  if (difference > 0) {
    timeLeft = {
      days: Math.floor(difference / (1000 * 60 * 60 * 24)),
      hours: Math.floor((difference / (1000 * 60 * 60)) % 24),
      minutes: Math.floor((difference / 1000 / 60) % 60),
      seconds: Math.floor((difference / 1000) % 60)
    };

    return timeLeft;

  }
};

const Timer = ({className, onFinish, timerInSecond}) => {
  const [timeLeft, setTimeLeft] = React.useState({
    minutes: "00",
    seconds: "00"
  });
  const now = new Date();
  let timer;

  const timerValue = difference => {
    return {
      minutes: Math.floor((difference / 1000 / 60) % 60).toLocaleString('en-US', {
        minimumIntegerDigits: 2,
        useGrouping: false
      }),
      seconds: Math.floor((difference / 1000) % 60).toLocaleString('en-US', {
        minimumIntegerDigits: 2,
        useGrouping: false
      })
    };
  };

  const countDown = React.useCallback((changeNow) => {
    let difference = changeNow - new Date();
    if (difference > 0) {
      setTimeLeft(timerValue(difference));
    } else {
      clearInterval(timer);
      onFinish();
    }
  }, [now]);

  React.useEffect(() => {
    let changeNow = now.setSeconds(now.getSeconds() + (timerInSecond));
    timer = setInterval(() => {
      countDown(changeNow);
    }, 1000);
  }, []);


  return (
    <div className={className}>
      {timeLeft.minutes ?? null}:{timeLeft.seconds ?? null}
    </div>
  );
};

export default Timer;
