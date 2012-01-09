<?php
/**
 * Displays a {@link SS_List} in a grid format.
 * 
 * GridField is a field that takes an SS_List and displays it in an table with rows 
 * and columns. It reminds of the old TableFields but works with SS_List types 
 * and only loads the necessary rows from the list.
 * 
 * The minimum configuration is to pass in name and title of the field and a 
 * SS_List.
 * 
 * <code>
 * $gridField = new GridField('ExampleGrid', 'Example grid', new DataList('Page'));
 * </code>
 * 
 * @see SS_List
 * 
 * @package sapphire
 * @subpackage fields-relational
 */
class GridField extends FormField {
	
	/**
	 *
	 * @var array
	 */
	public static $allowed_actions = array(
		'gridFieldAlterAction'
	);
	
	/** @var SS_List - the datasource */
	protected $list = null;

	/** @var string - the classname of the DataObject that the GridField will display. Defaults to the value of $this->list->dataClass */
	protected $modelClassName = '';
	
	/** @var array */
	public $fieldCasting = array();

	/** @var array */
	public $fieldFormatting = array();

	/** @var GridState - the current state of the GridField */
	protected $state = null;
	
	/**
	 *
	 * @var GridFieldConfig
	 */
	protected $config = null;
	
	/**
	 * The components list 
	 */
	protected $components = array();
	
	/**
	 * This is the columns that will be visible
	 *
	 * @var array
	 */
	protected $displayFields = array();
	
	/**
	 * Internal dispatcher for column handlers.
	 * Keys are column names and values are GridField_ColumnProvider objects
	 */
	protected $columnDispatch = null;

	/**
	 * Creates a new GridField field
	 *
	 * @param string $name
	 * @param string $title
	 * @param SS_List $dataList
	 * @param GridFieldConfig $config
	 */
	public function __construct($name, $title = null, SS_List $dataList = null, GridFieldConfig $config = null) {
		parent::__construct($name, $title, null);
		
		FormField::__construct($name);

		if($dataList) {
			$this->setList($dataList);
		}
		
		if(!$config) {
			$this->config = $this->getDefaultConfig();
		} else {
			$this->config = $config;
		}
		
		$this->setComponents($this->config);
		$this->components[] = new GridState_Component();
		$this->state = new GridState($this);
		
		
		$this->addExtraClass('ss-gridfield');
		$this->requireDefaultCSS();
		
	}
	
	/**
	 * Set the modelClass that this field will get it column headers from
	 * 
	 * @param string $modelClassName 
	 */
	public function setModelClass($modelClassName) {
		$this->modelClassName = $modelClassName;
		return $this;
	}
	
	/**
	 * Returns a dataclass that is a DataObject type that this field should look like.
	 * 
	 * @throws Exception
	 * @return string
	 */
	public function getModelClass() {
		if ($this->modelClassName) return $this->modelClassName;
		if ($this->list && $this->list->dataClass) return $this->list->dataClass;

		throw new LogicException('GridField doesn\'t have a modelClassName, so it doesn\'t know the columns of this grid.');
	}
	
	/**
	 * Set which Components that this GridFields contain by using a GridFieldConfig
	 *
	 * @param GridFieldConfig $config 
	 */
	protected function setComponents(GridFieldConfig $config) {
		$this->components = $config->getComponents();
		return $this;
	}
	
	/**
	 * Get a default configuration for this gridfield
	 * 
	 * @return GridFieldConfig
	 */
	protected function getDefaultConfig() {
		$config = GridFieldConfig::create();
		$config->addComponent(new GridFieldSortableHeader());
		$config->addComponent(new GridFieldFilter());
		$config->addComponent(new GridFieldDefaultColumns());
		$config->addComponent(new GridFieldPaginator());
		return $config;
	}
	
	/**
	 * Require the default css styling
	 */
	protected function requireDefaultCSS() {
		Requirements::css('sapphire/css/GridField.css');
	}

	/**
	 * @return array
	 */
	public function getDisplayFields() {
		if(!$this->displayFields) {
			return singleton($this->getModelClass())->summaryFields();
		}
		return $this->displayFields;
	}
	
	/**
	 *
	 * @return GridFieldConfig
	 */
	public function getConfig() {
		return $this->config;
	}
	
	/**
	 *
	 * @param array $fields 
	 */
	public function setDisplayFields(array $fields) {
		if(!is_array($fields)) {
			throw new InvalidArgumentException('Arguments passed to GridField::setDisplayFields() must be an array');
		}
		$this->displayFields = $fields;
		return $this;
	}

	/**
	 * @param array $casting
	 */
	public function setFieldCasting($casting) {
		$this->fieldCasting = $casting;
		return $this;
	}

	/**
	 * @param array $casting
	 */
	public function getFieldCasting() {
		return $this->fieldCasting;
	}

	/**
	 * @param array $casting
	 */
	public function setFieldFormatting($formatting) {
		$this->fieldFormatting = $formatting;
		return $this;
	}

	/**
	 * @param array $casting
	 */
	public function getFieldFormatting() {
		return $this->fieldFormatting;
	}
	
	/**
	 * Taken from TablelistField
	 * 
	 * @param $value
	 * 
	 */
	public function getCastedValue($value, $castingDefinition) {
		if(is_array($castingDefinition)) {
			$castingParams = $castingDefinition;
			array_shift($castingParams);
			$castingDefinition = array_shift($castingDefinition);
		} else {
			$castingParams = array();
		}
		
		if(strpos($castingDefinition,'->') === false) {
			$castingFieldType = $castingDefinition;
			$castingField = DBField::create($castingFieldType, $value);
			$value = call_user_func_array(array($castingField,'XML'),$castingParams);
		} else {
			$fieldTypeParts = explode('->', $castingDefinition);
			$castingFieldType = $fieldTypeParts[0];	
			$castingMethod = $fieldTypeParts[1];
			$castingField = DBField::create($castingFieldType, $value);
			$value = call_user_func_array(array($castingField,$castingMethod),$castingParams);
		}
		
		return $value;
	}	 

	/**
	 * Set the datasource
	 *
	 * @param SS_List $list
	 */
	public function setList(SS_List $list) {
		$this->list = $list;
		return $this;
	}

	/**
	 * Get the datasource
	 *
	 * @return SS_List
	 */
	public function getList() {
		return $this->list;
	}
	
	/**
	 * Get the current GridState
	 *
	 * @return GridState 
	 */
	public function getState($getData=true) {
		if(!$this->state) {
			throw new LogicException('State has not been defined');
		}
		if($getData) {
			return $this->state->getData();
		}
		return $this->state;
	}

	/**
	 * Returns the whole gridfield rendered with all the attached Elements
	 *
	 * @return string
	 */
	public function FieldHolder() {
		// Get columns
		$columns = $this->getColumns();

		// Get data
		$list = $this->getList();
		foreach($this->components as $item) {
 			if($item instanceof GridField_DataManipulator) {
				$list = $item->getManipulatedData($this, $list);
			}
		}
		
		// Render headers, footers, etc
		$content = array(
			'header' => array(),
			'body' => array(),
			'footer' => array(),
			'before' => array(),
			'after' => array(),
		);

		foreach($this->components as $item) {			
			if($item instanceof GridField_HTMLProvider) {
				$fragments = $item->getHTMLFragments($this);
				foreach($fragments as $k => $v) {
					$content[$k][] = $v;
				}
			}
		}

		foreach($list as $idx => $record) {
			$record->iteratorProperties($idx, $list->count());
			$row = "<tr class='".$record->FirstLast()." ".$record->EvenOdd()."'>";
			foreach($columns as $column) {
				$colContent = $this->getColumnContent($record, $column);
				// A return value of null means this columns should be skipped altogether.
				if($colContent === null) continue;
				$colAttributes = $this->getColumnAttributes($record, $column);
				$row .= $this->createTag('td', $colAttributes, $colContent);
			}
			$row .= "</tr>";
			$content['body'][] = $row;
		}

		// Turn into the relevant parts of a table
		$head = $content['header'] ? $this->createTag('thead', array(), implode("\n", $content['header'])) : '';
		$body = $content['body'] ? $this->createTag('tbody', array(), implode("\n", $content['body'])) : '';
		$foot = $content['footer'] ? $this->createTag('tfoot', array(), implode("\n", $content['footer'])) : '';

		$attrs = array(
			'id' => isset($this->id) ? $this->id : null,
			'class' => "field CompositeField {$this->extraClass()}"
		);
		return
			implode("\n", $content['before']) .
			$this->createTag('table', $attrs, $head."\n".$foot."\n".$body) .
			implode("\n", $content['after']);
	}

	function getColumns() {
		// Get column list
		$columns = array();
		foreach($this->components as $item) {
			if($item instanceof GridField_ColumnProvider) {
				$item->augmentColumns($this, $columns);
			}
		}
		return $columns;
	}
	
	public function getColumnContent($record, $column) {
		// Build the column dispatch
		if(!$this->columnDispatch) $this->buildColumnDispatch();
		
		$handler = $this->columnDispatch[$column];
		if($handler) {
			return $handler->getColumnContent($this, $record, $column);
		} else {
			throw new InvalidArgumentException("Bad column '$column'");
		}
	}
	
	public function getColumnAttributes($record, $column) {
		// Build the column dispatch
		if(!$this->columnDispatch) $this->buildColumnDispatch();
		
		$handler = $this->columnDispatch[$column];
		if($handler) {
			$attrs =  $handler->getColumnAttributes($this, $record, $column);
			if(is_array($attrs)) return $attrs;
			else if($attrs) throw new LogicException("Non-array response from " . get_class($handler) . "::getColumnAttributes()");
			else return array();
		} else {
			throw new InvalidArgumentException("Bad column '$column'");
		}
	}
	
	public function getColumnMetadata($column) {
		// Build the column dispatch
		if(!$this->columnDispatch) $this->buildColumnDispatch();
		
		$handler = $this->columnDispatch[$column];
		if($handler) {
			$metadata =  $handler->getColumnMetadata($this, $column);
			if(is_array($metadata)) return $metadata;
			else if($metadata) throw new LogicException("Non-array response from " . get_class($handler) . "::getColumnMetadata()");
			else return array();
		} else {
			throw new InvalidArgumentException("Bad column '$column'");
		}
	}
	
	public function getColumnCount() {
		// Build the column dispatch
		if(!$this->columnDispatch) $this->buildColumnDispatch();
		
		return count($this->columnDispatch);	
		
	}
	protected function buildColumnDispatch() {
		$this->columnDispatch = array();
		foreach($this->components as $item) {
			if($item instanceof GridField_ColumnProvider) {
				$columns = $item->getColumnsHandled($this);
				foreach($columns as $column) {
					$this->columnDispatch[$column] = $item;
				}
			}
		}			
	}
	
	/**
	 * This is the action that gets executed when a GridField_AlterAction gets clicked.
	 *
	 * @param array $data
	 * @return string 
	 */
	public function gridFieldAlterAction($data, $form, $request) {
		$id = $data['StateID'];
		$stateChange = Session::get($id);

		$state = $this->getState(false);
		$state->setValue($data['GridState']);
		
		$gridName = $stateChange['grid'];
		$grid = $form->Fields()->fieldByName($gridName);
		$actionName = $stateChange['actionName'];
		
		$args = $stateChange['args'];
		$grid->handleAction($actionName, $args, $data);
		
		// Make the form re-load it's values from the Session after redirect
		// so the changes we just made above survive the page reload
		// @todo Form really needs refactoring so we dont have to do this
		if (Director::is_ajax()) {
			return $form->forTemplate();
		} else {
			$data = $form->getData();
			Session::set("FormInfo.{$form->FormName()}.errors", array());
			Session::set("FormInfo.{$form->FormName()}.data", $data);
			Controller::curr()->redirectBack();
		}
		
	}
	
	public function handleAction($actionName, $args, $data) {
		$actionName = strtolower($actionName);
		foreach($this->components as $item) {
			if(!($item instanceof GridField_ActionProvider)) {
				continue;
			}
			
			if(in_array($actionName, array_map('strtolower', $item->getActions($this)))) {
				return $item->handleAction($this, $actionName, $args, $data);
			}
		}
		throw new InvalidArgumentException("Can't handle action '$actionName'");
	}
}


/**
 * This class is the base class when you want to have an action that alters the state of the gridfield
 * 
 * @package sapphire
 * @subpackage forms
 * 
 */
class GridField_Action extends FormAction {

	/**
	 *
	 * @var GridField
	 */
	protected $gridField;
	
	/**
	 *
	 * @var string
	 */
	protected $buttonLabel;
	
	/**
	 *
	 * @var array 
	 */
	protected $stateValues;
	
	/**
	 *
	 * @var array
	 */
	//protected $stateFields = array();
	
	protected $actionName;
	protected $args = array();

	/**
	 *
	 * @param GridField $gridField
	 * @param type $name
	 * @param type $label
	 * @param type $actionName
	 * @param type $args 
	 */
	public function __construct(GridField $gridField, $name, $label, $actionName, $args) {
		$this->gridField = $gridField;
		$this->buttonLabel = $label;
		$this->actionName = $actionName;
		$this->args = $args;
		parent::__construct($name);
	}

	/**
	 * urlencode encodes less characters in percent form than we need - we need everything that isn't a \w
	 * 
	 * @param string $val
	 */
	public function nameEncode($val) {
		return preg_replace_callback('/[^\w]/', array($this, '_nameEncode'), $val);
	}

	/**
	 * The callback for nameEncode
	 * 
	 * @param string $val
	 */
	public function _nameEncode($match) {
		return '%'.dechex(ord($match[0]));
	}

	/**
	 * Default method used by Templates to render the form
	 *
	 * @return string HTML tag
	 */
	public function Field() {
		// Store state in session, and pass ID to client side
		$state = array(
			'grid' => $this->getNameFromParent(),
			'actionName' => $this->actionName,
			'args' => $this->args,
		);
		
		$id = preg_replace('/[^\w]+/', '_', uniqid('', true));
		Session::set($id, $state);
		
		$actionData['StateID'] = $id;
		
		// And generate field
		$attributes = array(
			'class' => 'action' . ($this->extraClass() ? $this->extraClass() : ''),
			'id' => $this->id(),
			'type' => 'submit',
			// Note:  This field needs to be less than 65 chars, otherwise Suhosin security patch 
			// will strip it from the requests 
			'name' => 'action_gridFieldAlterAction'. '?' . http_build_query($actionData),
			'tabindex' => $this->getTabIndex(),
		);

		if($this->isReadonly()) {
			$attributes['disabled'] = 'disabled';
			$attributes['class'] = $attributes['class'] . ' disabled';
		}
		
		return $this->createTag('button', $attributes, $this->buttonLabel);
	}

	/**
	 * Calculate the name of the gridfield relative to the Form
	 *
	 * @param GridField $base
	 * @return string
	 */
	protected function getNameFromParent() {
		$base = $this->gridField;
		$name = array();
		do {
			array_unshift($name, $base->getName());
			$base = $base->getForm();
		} while ($base && !($base instanceof Form));
		return implode('.', $name);
	}
}