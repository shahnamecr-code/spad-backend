import React from 'react'
import RenderLinks from '../../components/general/renderLinks/RenderLinks';
import MainLayout from '../../components/layout/mainLayout/MainLayout';

const Crm = () => {
  return (
    <MainLayout title="اخبار و مقالات">
    <RenderLinks
      links={[{ title: "اخبار و مقالات" }]}
    />

  </MainLayout>
  )
}

export default Crm;