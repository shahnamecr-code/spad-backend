import { useRouter } from "next/router";
import { useEffect, useState } from "react";

import MainLayout from "../../components/layout/mainLayout/MainLayout";

import Login from "../../components/pagesComponents/auth/login";
import Signup from "../../components/pagesComponents/auth/signup";

import styles from "./login.module.scss";

const Auth = () => {
  const [hasAccount, setHasAccount] = useState(true);
  const router = useRouter();

  useEffect(() => {
    if (router.query.hasAccount != undefined) {
      setHasAccount(router.query.hasAccount == "false" ? false : true);
    }
  }, [router.query]);

  return (
    <MainLayout title="ورود / ثبت نام">
      <div className={styles.container}>
        {hasAccount ? (
          <Login handleClickHasAcc={(value) => setHasAccount(value)} />
        ) : (
          <Signup handleClickHasAcc={(value) => setHasAccount(value)} />
        )}
      </div>
    </MainLayout>
  );
};

export default Auth;
