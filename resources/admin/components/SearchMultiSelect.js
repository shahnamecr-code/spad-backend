import React, {Component} from 'react';
import PropTypes from 'prop-types';
import AsyncSelect from 'react-select/async';

class SearchMultiSelect extends Component {
  constructor(props) {
    super(props);
    this.state = {inputValue: '', options: [], timeout: null, value: this.props.value};
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
    }, 1000);
  };

  setloadOptions = (options) => {
    this.setState({options: options})
  }

  componentDidMount() {
    //this.props.request(this.setloadOptions);
    this.onChange(this.props.value ? this.props.value : []);
  }

  handleChange = (selected) => {
    this.setState({value: selected})
    this.onChange(selected);
  }

  onChange = (selected) => {
    if (this.props.onChange) {
      let ids = [];
      selected && selected.map((item) => {
        ids.push(item.id)
      });
      this.props.onChange(this.props.name, ids, selected);
    }
  }

  render() {
    return (
      <div>
        <AsyncSelect
          className={'react-select'}
          classNamePrefix={"react-select"}
          cacheOptions
          isMulti
          loadOptions={this.loadOptions}
          //onInputChange={this.handleInputChange}
          onChange={this.handleChange}
          defaultOptions={true}
          value={this.state.value}
          getOptionLabel={this.props.getOptionLabel ?? undefined}
          getOptionValue={this.props.getOptionLabel ?? undefined}
          menuPlacement={'auto'}
        />
      </div>
    );
  }
}


SearchMultiSelect.propTypes = {
  name: PropTypes.string,
  value: PropTypes.oneOfType([PropTypes.string, PropTypes.array]),
  request: PropTypes.func.isRequired,
  onChange: PropTypes.func,
  getOptionLabel: PropTypes.func,
  getOptionValue: PropTypes.func
};

export default SearchMultiSelect;
