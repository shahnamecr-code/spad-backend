import { RiUser3Fill } from "@react-icons/all-files/ri/RiUser3Fill";
import { AiFillHeart } from "@react-icons/all-files/ai/AiFillHeart";
import { AiFillPlusCircle } from "@react-icons/all-files/ai/AiFillPlusCircle";
import { AiFillHome } from "@react-icons/all-files/ai/AiFillHome";
import { AiOutlineUnorderedList } from "@react-icons/all-files/ai/AiOutlineUnorderedList";
import Link from "next/link";
import { paths } from "../../../utils";
import styles from "./bottomNavigation.module.scss";
import { useRouter } from "next/router";
import { token } from "../../../services/sevices";
import { useAppContext } from "../../../utils/context";

const BottomNavigation = () => {
  const router = useRouter();
  const context = useAppContext();
  const navItems = [
    context.user.id
      ? { title: "پروفایل", url: paths.profile, icon: <RiUser3Fill /> }
      : { title: "ورود", url: paths.login, icon: <RiUser3Fill /> },
    {
      title: "ثبت آگهی",
      url: token() ? paths.create : paths.login,
      icon: <AiFillPlusCircle />,
    },
    { title: "خانه", url: "/", icon: <AiFillHome /> },
    { title: "آگهی ها", url: paths.search, icon: <AiOutlineUnorderedList /> },
    { title: "علاقه مندی", url: paths.favorites, icon: <AiFillHeart /> },
  ];
  return (
    <div className={`sm-hide ${styles.container}`}>
      {navItems.map((item, i) => (
        <Link key={i} href={item.url}>
          <a
            className={`${styles["nav-item"]} ${
              router.pathname === item.url ? styles.active : ""
            }`}
          >
            {item.icon}
            <span>{item.title}</span>
          </a>
        </Link>
      ))}
    </div>
  );
};

export default BottomNavigation;
