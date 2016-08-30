'use strict'

var my_expenses = [];

window.ee = new EventEmitter();

var Expense = React.createClass({
	onDeleteHandler: function (id) {
		$.post('/worker/ajax/remove',
			{
				id: id
			},
			function (response) {
				console.log('response ' + response);
			}
		);

		window.ee.emit('News.remove', id);
	},
	render: function (argument) {
		var id = this.props.data.id;
		var title = this.props.data.title;
		var price = this.props.data.price;
		var date = this.props.data.created_at;
		return (
			<div className="col-sm-4">
				<div className="expense">
					<div className="delete" onClick={this.onDeleteHandler.bind(this, id)}>
						<i className="glyphicon glyphicon-remove"></i>
					</div>
					<div className="title">{title}</div>
					<div className="price">{price}</div>
					<div className="date">{date}</div>
				</div>
			</div>
		)
	}
});

var Expenses = React.createClass({
	render: function (argument) {
		var data = this.props.data;
		var template = data.map(function(item, index){
			return(
				<div key={index}>
					<Expense data={item} />
				</div>
			)
		});
		return(
			<div className="row">{template}</div>
		)
	}
});

var Add = React.createClass({
	getInitialState: function (argument) {
		return{
			btnDisabled: true,
			textIsEmpty: true,
			priceIsEmpty: true
		}
	},
	componentDidMount: function (argument) {
		ReactDOM.findDOMNode(this.refs.title).focus();
	},
	onClickHandler: function (e) {
		e.preventDefault();
		var title = ReactDOM.findDOMNode(this.refs.title);
		var price = ReactDOM.findDOMNode(this.refs.price);
		
		var curdate = new Date();
		var date = curdate.getFullYear()+'-'+curdate.getMonth()+'-'+curdate.getDate();
		var item = [{
			id: '',
			title: title.value,
			price: price.value,
			created_at: date
		}];

		$.post('/worker/ajax/add',
			{
				item: item
			},
			function( response ){
				item[0]['id'] = response['id'];
				console.log(response);
		});

		window.ee.emit('News.add', item);

		title.value = '';
		price.value = '';
		this.setState({textIsEmpty: true, priceIsEmpty: true});
	},
	onCheckRuleClick: function (argument) {
		this.setState({btnDisabled: !this.state.btnDisabled});
	},
	onFieldChange: function (fieldName, e) {
		if(e.target.value.trim().length > 0){
			this.setState({[''+fieldName]: false});
		}
		else{
			this.setState({[''+fieldName]: true});
		}
	},
	render: function (argument) {
		var priceIsEmpty = this.state.priceIsEmpty;
		var textIsEmpty = this.state.textIsEmpty;
		var btnDisabled = this.state.btnDisabled;
		return (
			<form>
				<div className='form-group'>
					<input type='text' defaultValue='' ref='title' onChange={this.onFieldChange.bind(this, 'textIsEmpty')} className='form-control' placeholder='Title' />
				</div>
				<div className='form-group'>
					<input type='text' defaultValue='' ref='price' onChange={this.onFieldChange.bind(this, 'priceIsEmpty')} className='form-control' placeholder='Price' />
				</div>
				<div class="checkbox">
				  <label>
				    <input type="checkbox" defaultChecked={false} ref='checkrule' onChange={this.onCheckRuleClick} /> Save
				  </label>
				</div>
				<button type="submit" className="btn btn-default" 
					onClick={this.onClickHandler} 
					ref='alert' 
					disabled={btnDisabled||textIsEmpty||priceIsEmpty}>Save</button>
			</form>
		)
	}
});

var App = React.createClass({
	getInitialState: function () {
		return {
			expenses: []
		}
	},
	componentDidMount: function (argument) {
		$.get('/worker/ajax/get', function(data) {
			this.setState({expenses: data});
		}.bind(this));
		var self = this;
		window.ee.addListener('News.add', function (item) {
			var nextExp = item.concat(self.state.expenses);
			self.setState({expenses: nextExp});
		});

		window.ee.addListener('News.remove', function(id){
			var expensesArr = self.state.expenses;
			for(var i=0; i<expensesArr.length; i++){
				if (expensesArr[i]['id'] == id){
					expensesArr.splice(i, 1);
				}
			}
			self.setState({expenses: expensesArr});
		});
	},
	componentWillUnmount: function (argument) {
		window.ee.removeListener('News.add');
		window.ee.removeListener('News.remove');
	},
	render: function (argument) {
		return (
			<div>
				<Add />
				<Expenses data={this.state.expenses}/>
			</div>
		)
	}
});

ReactDOM.render(
	<div>
		<App />
	</div>,
	document.getElementById('app')
);