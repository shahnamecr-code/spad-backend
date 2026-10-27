import React, {useEffect} from 'react';

const UserLayout = ({children}) => {
  useEffect(() => {
    document.body.classList.add('spad-admin-auth-body');
    document.body.classList.add('no-footer');

    return () => {
      document.body.classList.remove('spad-admin-auth-body');
      document.body.classList.remove('no-footer');
    };
  }, []);

  return (
    <main className="spad-admin-auth-page">
      <div className="spad-admin-auth-container">{children}</div>
    </main>
  );
};

export default UserLayout;
