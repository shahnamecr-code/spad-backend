import React, { Component } from 'react';
import Lightbox from 'react-image-lightbox';
import 'react-image-lightbox/style.css'; // This only needs to be imported once in your app

export default class LightboxGallery extends Component {
  constructor(props) {
    super(props);

    this.state = {
      photoIndex: props.startIndex??0,
    };
  }

  render() {
    const { photoIndex } = this.state;

    return (
      <Lightbox
        showThumbnails
        mainSrc={this.props.images[photoIndex]}
        nextSrc={this.props.images[(photoIndex + 1) % this.props.images.length]}
        prevSrc={this.props.images[(photoIndex + this.props.images.length - 1) % this.props.images.length]}
        mainSrcThumbnail={this.props.images[photoIndex]}
        nextSrcThumbnail={this.props.images[(photoIndex + 1) % this.props.images.length]}
        prevSrcThumbnail={this.props.images[(photoIndex + this.props.images.length - 1) % this.props.images.length]}
        onCloseRequest={() => this.props.onClose()}
        onMovePrevRequest={() =>
          this.setState({
            photoIndex: (photoIndex + this.props.images.length - 1) % this.props.images.length,
          })
        }
        onMoveNextRequest={() =>
          this.setState({
            photoIndex: (photoIndex + 1) % this.props.images.length,
          })
        }
      />
    );
  }
}
