import { useRouter } from "next/router";
import { useEffect, useState } from "react";
import MainLayout from "../../components/layout/mainLayout/MainLayout";
import ExpertiseRequests from "../../components/pagesComponents/profile/expertiseRequests/ExpertiseRequests";
import MyAds from "../../components/pagesComponents/profile/myAds/MyAds";
import MyRequests from "../../components/pagesComponents/profile/myRequests/MyRequests";
import ProfileBox, {
  profileNavItems,
} from "../../components/pagesComponents/profile/profileBox/ProfileBox";
import RecentAds from "../../components/pagesComponents/profile/recentAds/RecentAds";
import UpdatePass from "../../components/pagesComponents/profile/update-pass/UpdatePass";
import UpdateProfile from "../../components/pagesComponents/profile/update-profile/UpdateProfile";
import VisitingRequests from "../../components/pagesComponents/profile/visitingRequests/VisitingRequests";
import { getUserInfo } from "../../services/ProfileServices";
import { token } from "../../services/sevices";
import { useAppContext } from "../../utils/context";
import styles from "./profile.module.scss";

const Profile = () => {
  const router = useRouter();
  const context = useAppContext();
  const [showUpdateProdile, setShowUpdateProdile] = useState(false);
  const [showUpdatePass, setShowUpdatePass] = useState(false);

  useEffect(() => {
    if (!token()) router.push("/");
    else {
      getUserInfo().then((res) => context.setUser(res.data));
    }
  }, []);

  const renderPageComponents = () => {
    switch (router.query.active_tab) {
      case profileNavItems[0].tab:
        return <MyAds />;
      case profileNavItems[1].tab:
        return <RecentAds />;
      case profileNavItems[2].tab:
        return <MyRequests />;
      // case profileNavItems[3].tab:
      //   return <VisitingRequests />;
      case profileNavItems[3].tab:
        return <ExpertiseRequests />;
      default:
        return;
    }
  };

  return (
    <MainLayout title="پروفایل">
      <div className={styles.container}>
        <aside className={styles["aside-profile"]}>
          <ProfileBox
            showUpdateProfilePopup={() => setShowUpdateProdile(true)}
            showUpdatePassPopup={() => setShowUpdatePass(true)}
          />
        </aside>
        <div className={styles["info-container"]}>{renderPageComponents()}</div>
      </div>
      <UpdateProfile
        visible={showUpdateProdile}
        onHiding={() => setShowUpdateProdile(false)}
      />
      <UpdatePass
        visible={showUpdatePass}
        onHiding={() => setShowUpdatePass(false)}
      />
    </MainLayout>
  );
};

export default Profile;
