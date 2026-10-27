import React, {Component} from 'react';

import AsyncSelect from 'react-select/async';
import PropTypes from "prop-types";

class SearchSelect extends Component {
  constructor(props) {
    super(props);
    this.state = {inputValue: '', options: [], timeout: null, value: props.value ? props.value : undefined};
  }

  handleInputChange = (newValue) => {
    // inputValue = newValue.replace(/\W/g, '');
    //clearTimeout(this.state.timeout)
    //this.state.timeout=setTimeout(()=>{
    //if(newValue.length>0)
    this.props.request(this.setloadOptions, newValue);
    //},2000);
    return newValue;
  };

  filterColors = (inputValue) => {
    return this.state.options;
    return this.state.options.filter(i =>
      i.label.toLowerCase().includes(inputValue.toLowerCase())
    );
  };

  loadOptions = (inputValue, callback) => {
    setTimeout(() => {
      this.props.request(callback, inputValue);
      //callback(this.state.options);
    }, 1000);
  };

  setloadOptions = (options) => {
    this.setState({options: options})
  }

  componentDidMount() {
    //this.props.request(this.setloadOptions)
  }

  handleChange = (selected) => {
    this.setState({value: selected})

    if (this.props.setList) {
      this.props.setList((list) => [
        ...list,
        {...selected, options: selected.options1},
      ])
    }
    if (this.props.setData) {
      this.props.setData(selected)

    }
    if (this.props.showModal) {
      this.props.showModal(false)
    }
    if (this.props.onChange) {
      this.props.onChange(this.props.name, selected);
    }
  }

  render() {
    return (
      <div>
        <AsyncSelect
          className={'react-select'}
          classNamePrefix={"react-select"}
          cacheOptions
          loadOptions={this.loadOptions}
          //onInputChange={this.handleInputChange}
          onChange={this.handleChange}
          defaultOptions={true}
          value={this.props.value}
          getOptionLabel={this.props.getOptionLabel ? this.props.getOptionLabel : undefined}
          getOptionValue={this.props.getOptionValue ? this.props.getOptionValue : undefined}
        />
      </div>
    );
  }
}

SearchSelect.propTypes = {
  name: PropTypes.string,
  value: PropTypes.oneOfType([PropTypes.string, PropTypes.array]),
  request: PropTypes.func.isRequired,
  onChange: PropTypes.func,
  setData: PropTypes.func,
  showModal: PropTypes.func,
  setList: PropTypes.func,
};

export default SearchSelect;
