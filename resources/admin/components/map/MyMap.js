import React from 'react';
import ReactMapboxGl from 'react-mapbox-gl'
import mapboxgl from 'mapbox-gl'
import 'mapir-react-component/dist/index.css';
import '@mapbox/mapbox-gl-draw/dist/mapbox-gl-draw.css';
import 'mapbox-gl/dist/mapbox-gl.css';

mapboxgl.setRTLTextPlugin(
  'https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-rtl-text/v0.2.3/mapbox-gl-rtl-text.js'
);

const apiToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6ImUxMTExNzI0ZjkxOGEzY2M4YTgwOWNkZGE4YjAyZTBmMDNkNWM0ZGM2OGJhNGRjMzc4M2RlYzQzMGYzMzliNmE1NzVlMDFjNzI2ZjhiMGJmIn0.eyJhdWQiOiIxMjE4OSIsImp0aSI6ImUxMTExNzI0ZjkxOGEzY2M4YTgwOWNkZGE4YjAyZTBmMDNkNWM0ZGM2OGJhNGRjMzc4M2RlYzQzMGYzMzliNmE1NzVlMDFjNzI2ZjhiMGJmIiwiaWF0IjoxNjA5Njc1MTAwLCJuYmYiOjE2MDk2NzUxMDAsImV4cCI6MTYxMjE4MDcwMCwic3ViIjoiIiwic2NvcGVzIjpbImJhc2ljIl19.FFPeBqOvxdk58CWuIsHLqUVWEisERkttaLNn28QmuLCjqnbsZHRjJDmjhJ6_Pe58u4mMoEIQ1qrF1hq5aOqT1wsJxoF0nwGnZ7_WP6ilMzZhWUElcaI6primQ2spnn42LYx4HA14Filu3pW4NCzqO6i9E923cBN78-4JOZOyUwBEfCb7dBKepKy2_zdXoW6RhmHXoYgxjwAWVAXuLMkX_88Ym6As9mLGSeFDkByzWBgAHxiHBGSZ8wtba3npZPnEbUCvbgEw9fDmUp-WGqPIiV6Y_8YruD93kXmu8fccf-R25XduYXxL7Nq_SDzTaarfA-zFj1PggerxNKXi1ZmHYQ';

const Map = ReactMapboxGl({
  transformRequest: url => {
    return {
      url: url,
      headers: {
        "x-api-key": apiToken, //Mapir api key
        "Mapir-SDK": "reactjs"
      }
    };
  }
});

const MyMap = ({children, ...props}) => {

  const setAttribution = (map) => {
    map.addControl(
      new mapboxgl.GeolocateControl({
        positionOptions: {
          enableHighAccuracy: false,
          timeout: 5000,
          maximumAge: 10000,
        },
        trackUserLocation: true,
      })
    );
    map.addControl(new mapboxgl.NavigationControl());
    if (props.onStyleLoad) {
      props.onStyleLoad(map);
    }
  };


  return (
    <Map
      renderWorldCopies={false}
      style={`https://map.ir/vector/styles/main/mapir-xyz-style.json`}
      center={[51.41, 35.7575]}
      tms={true}
      containerStyle={{height: '500px', width: '100%'}}
      {...props}
      onStyleLoad={(map) => {
        setAttribution(map);
      }}
    >
      {children}
    </Map>
  );
}

export default MyMap;
