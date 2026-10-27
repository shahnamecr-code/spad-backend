import Head from "next/head";
import { useRouter } from "next/router";
import { ReactNode, useEffect, useState } from "react";
import { getStore, paths } from "../../../utils";
import { useAppContext } from "../../../utils/context";
import { LOCATION } from "../../../utils/stores";
import BottomNavigation from "../bottomNavigation/BottomNavigation";
import CityPopup from "../cityPopup/CityPopup";
import Footer from "../footer/Footer";
import Header from "../header/Header";
import styles from "./mainLayout.module.scss";

interface IProps {
  title: string;
  children: ReactNode;
  hideLayout?: boolean;
  location?: any;
}

const MainLayout = (props: IProps) => {
  const router = useRouter();
  const [showCityPopup, setShowCityPopup] = useState(false);
  const context = useAppContext();

  useEffect(() => {
    if (
      !props.location &&
      router.pathname !== paths["about-us"] &&
      router.pathname !== paths["contact-us"] &&
      router.pathname !== paths.rules
    ) {
      if (!getStore(LOCATION)) {
        setShowCityPopup(true);
      }
    }
  }, []);

  return (
    <div className={styles["main-layout"]}>
      <Head>
        <title>{props.title}</title>
        <link rel="manifest" href="/manifest.json" />
        <link
          href={context.options.general_favicon}
          rel="icon"
          type="image/png"
          sizes="16x16"
        />
        <link
          rel="apple-touch-icon"
          href={context.options.general_favicon}
        ></link>
        {/* <meta name="theme-color" content="#707070" /> */}
      </Head>

      {!props.hideLayout && (
        <Header
          showCityPopup={showCityPopup}
          setShowCityPopup={setShowCityPopup}
          title={props.title}
        />
      )}
      <main className={styles.main}>
        <div
          className={router.pathname !== "/" ? "container" : "container-fluid"}
        >
          {props.children}
        </div>
      </main>
      {!props.hideLayout && (
        <>
          <BottomNavigation />
          <Footer />
          <CityPopup
            visible={showCityPopup}
            onHiding={() => setShowCityPopup(false)}
          />
        </>
      )}
    </div>
  );
};

export default MainLayout;
