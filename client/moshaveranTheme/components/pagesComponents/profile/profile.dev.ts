import { toast } from "react-toastify";
import { logout } from "../../../services/AuthenticateServices";
import { paths, removeStore } from "../../../utils";
import { useLoadingContext } from "../../../utils/context";
import { TOKEN, FAVORITES, USER } from "../../../utils/stores";

export const useProfileDev = () => {
  const loadingContext = useLoadingContext();

  const logoutOnClick = () => {
    loadingContext.setLoading(true);
    logout()
      .then((res) => {
        if (res.data) {
          toast.success("خروج با موفقیت انجام شد.");
          removeStore(TOKEN);
          removeStore(FAVORITES);
          removeStore(USER);
          if (window.location.pathname === paths.profile)
            window.location.replace("/");
          else window.location.reload();
        }
      })
      .finally(() => loadingContext.setLoading(false));
  };

  return { logoutOnClick };
};
