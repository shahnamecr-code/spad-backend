import React from 'react';
import {adminPathApi} from "../../../constants/defaultValues";
import CheckboxTree from 'react-checkbox-tree';

class CategoryPermission extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      checked: this.props.checked,
      expanded: [],
      loading: true,
      categoryList: [],
    }

    this.onChange = this.onChange.bind(this)
    this.onExpand = this.onExpand.bind(this)
    this.getCategory = this.getCategory.bind(this)
  }

  componentDidMount() {
    this.getCategory();
  }

  async getCategory() {
    await axios.all([
      axios.get(`${adminPathApi}/category/get_categories`)
    ])
      .then(axios.spread((category) => {
        this.setState({
          loading: false,
          categoryList: category.data,
        });
      }))
      .catch(error => console.log(error));
  };


  onChange(checked) {
    this.setState({checked});
    this.props.setChecked(this.props.name, checked);
  };

  onExpand(expanded) {
    this.setState({expanded});
  }

  render() {
    if (this.state.loading) {
      return <div className={'loading'}/>
    }

    return (
      <CheckboxTree
        optimisticToggle={false}
        checkModel={'all'}
        direction={'rtl'}
        iconsClass="fa5"
        nodes={this.state.categoryList}
        showExpandAll
        checked={this.state.checked}
        expanded={this.state.expanded}
        onCheck={this.onChange}
        onExpand={this.onExpand}
      />
    );
  }
}

export default CategoryPermission;
