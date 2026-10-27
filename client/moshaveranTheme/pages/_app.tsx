import "../styles/globals.scss";
import "../styles/bootstrap-grid.css";
import "../styles/font-size.scss";
import type { AppProps } from "next/app";
import "swiper/css";
import "swiper/css/lazy";
import "swiper/css/pagination";
import "swiper/css/navigation";
import { ContextProvider, LoadingContext } from "../utils/context";
import { ToastContainer } from "react-toastify";
import "react-toastify/dist/ReactToastify.css";
import { useEffect, useState } from "react";
import { useRouter } from "next/router";
import SimpleLoading from "../components/general/simpleLoading/SimpleLoading";
import NewLoading from "../components/general/newLoading/NewLoading";

function MyApp({ Component, pageProps }: AppProps) {
  const [pageLoading, setPageLoading] = useState<boolean>(false);
  const router = useRouter();
  const [loading, setLoading] = useState(false);

  //show route change loading
  useEffect(() => {
    const handleStart = () => {
      setPageLoading(true);
    };
    const handleComplete = () => {
      setTimeout(() => {
        setPageLoading(false);
      }, 500);
    };

    router.events.on("routeChangeStart", handleStart);
    router.events.on("routeChangeComplete", handleComplete);
    router.events.on("routeChangeError", handleComplete);
  }, [router]);

  return (
    <ContextProvider>
      <LoadingContext.Provider
        value={{ loading: loading, setLoading: setLoading }}
      >
        <ToastContainer rtl/>
        {loading && <SimpleLoading />}
        <NewLoading isVisible={pageLoading} />
        <Component {...pageProps} />
      </LoadingContext.Provider>
    </ContextProvider>
  );
}

export default MyApp;
