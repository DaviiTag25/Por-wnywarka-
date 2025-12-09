<?php
/**
 * @copyright	Copyright (C) 2013 Comperia S.A. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */


class comperiaConfig extends JIObject{
	protected 	$_config			= array();
	private		$defaultConfig	= '/config.xml';
	public 		$prefix			= 'comperia_';
	private 		$ignoreFields	= array('remember_Itemid','noMenu_404','tmplComponentinTmpl','turn_plugin_on','sef_advanced_link');
	private		$translations	= array();
	
	public function __construct( $config = array() ){
		$this->loadTranslation();
		$path_to_config =	( ! empty($config['path_to_config']) == null ? $this->defaultConfig : $config['path_to_config'] );
		$config = new SimpleXMLElement( COMPERIA_PATH_PLUGIN . $path_to_config , 0, true );
		foreach( $config->fieldset as $fieldset ){
			$mandatoryFieldset = array('name'=>'','label'=>'','description'=>'');
			$fieldsetTmp = array();
			foreach( $fieldset->attributes() as $k => $v ) {
				if( $k == 'name' ){
					if(in_array($v,$this->ignoreFields)){continue 2;}
				}
				$fieldsetTmp[$k] = $this->translate((string)$v);
				unset($mandatoryFieldset[$k]);
			}
			if( ! empty( $mandatoryFieldset ) ){ 
				die('Niepoprawnie sformatowany plik konfiguracyjny. Brak parametrów ' . print_r($mandatoryFieldset,true) . ' w ' . print_r($fieldset,true));
			}
			$fieldsetTmp['fields'] = array();
			foreach( $fieldset as $field ){
				$mandatoryField = array('name'=>'', 'type'=>'', 'default'=>'', 'label'=>'', 'description'=>'');
				$fieldTmp = array();
				foreach( $field->attributes() as $k => $v ) {
					if( $k == 'name' ){
						if(in_array($v,$this->ignoreFields)){continue 2;}
					}
					$fieldTmp[$k] = $this->translate((string)$v);
					unset($mandatoryField[$k]);
				}
				// get list options if list
				switch ($field->attributes()->type){
					case 'list':
						$fieldTmp['options'] = array();
						foreach( $field->option as $option ){
							$fieldTmp['options'][(string)$option->attributes()->value] = $this->translate((string)$option);
						}
						$fieldTmp['value'] = $fieldTmp['default'];
						break;
					case 'spacer':
						unset($mandatoryField['default']);
						unset($mandatoryField['description']);
						break;
					default:
						$fieldTmp['value'] = $fieldTmp['default'];
						break;
				
				}
				
				//check if all mandatory attributes set
				if( ! empty( $mandatoryField ) ){ 
					die('Niepoprawnie sformatowany plik konfiguracyjny. Brak parametrów ' . print_r($mandatoryField,true) . ' w ' . print_r($field,true));
				}
				$fieldsetTmp['fields'][$fieldTmp['name']] = $fieldTmp;
			}
			$this->_config[$fieldsetTmp['name']] = $fieldsetTmp;
		}

		$this->save(); // save from POST if set
		$this->getValues(); // propagate values from database
	}
	public function install(){
		foreach( $this->_config as $fieldset ){
			foreach( $fieldset as $k => $field ){
				switch ($field['type']){
					case 'list':
					case 'text':
					case 'integer':
						add_option( $this->prefix . $field['name'], $field['default'] , '' , 'no' );
					case 'spacer':
					default:
						break;
				
				}
			}
		}
	}
	public function getValues(){
		foreach( $this->_config as &$fieldset ){
			foreach( $fieldset['fields'] as $k => &$field ){
				switch ($field['type']){
					case 'list':
					case 'text':
					case 'integer':
						$field['value'] = get_option( $this->prefix . $field['name'], $field['default']);
					case 'spacer':
					default:
						break;
				}
			}
		}
	}
	public function save(){
		if( isset($_POST['config_submit']) ){
			foreach( $this->_config as $fieldset ){
				foreach( $fieldset['fields'] as $k => $field ){
					switch ($field['type']){
						case 'list':
						case 'text':
						case 'integer':
							$field['value'] = update_option( $this->prefix . $field['name'], $_POST[ $field['name'] ] );
						case 'spacer':
						default:
							break;
					}
				}
			}
		}
	}
	public function get( $fieldName, $default = null ){

		foreach( $this->_config as $fieldset ){
			if( isset($fieldset['fields'][$fieldName]) ){
				// getOption & save in xml
				if(!empty($fieldset['fields'][$fieldName]['value'])){
					return $fieldset['fields'][$fieldName]['value'];
				}
			}
		}
		return $default;
	}
	public function set( $fieldName, $value ){
		//
	}
	public function getForm(){
		ob_start();
		echo '<form class="comperia_form accordion" action="" method="post">';
		echo '<input type="submit" value="Zapisz" name="config_submit" class="button button-primary">';
		foreach( $this->_config as $fieldset ){
			echo '<fieldset id="' . $fieldset['name'] . '" name="' . $fieldset['name'] . '">';
				echo '<legend><a href="#' . $fieldset['name'] . '" >' . $fieldset['label'] . '</a></legend>';
				echo '<div class="fieldset-content">';
				echo '<p>' . $fieldset['description'] . '</p>';
				foreach( $fieldset['fields'] as $k => $field ){
					echo '<div class="row">' . $this->getFieldHtml( $field ) . '</div>';
				}
				echo '</div>';
			echo '</fieldset>';
		}
		echo '<input type="submit" value="Zapisz" name="config_submit" class="button button-primary">';
		echo'</form>';
		return ob_get_clean();
	}
	// fieldsToDisable - tylko dla list
	public function getFieldHtml( $field, $getLabel = true, $fieldsToDisable = array() ){
		// text -> value, list->[options][value]
		$label = '';
		
		switch( $field['type'] ){
			case 'integer':
			case 'text':
				$label = $this->getLabel($field);
				$field = '<input id="' . $field['name'] . '" name="' . $field['name'] . '" type="text" value="' . $field['value'] . '">';
				break;
			case 'textarea':
				$label = $this->getLabel($field);
				$field = '<textarea id="' . $field['name'] . '" name="' . $field['name'] . '">' . $field['value'] . '</textarea>';
				break;
			case 'list':
				$label = $this->getLabel($field);
				$options = '';
				foreach( $field['options'] as $k => $v ){
					$selected = ( $k == $field['value'] ? ' selected' : '' );
					$disabled = ( in_array( $k, $fieldsToDisable ) && !$selected ? ' disabled' : '' );
					$options .= '<option value="' . $k . '"'. $selected . $disabled . '>' . $v . '</option>';
				}
				$field = '<select id="' . $field['name'] . '" name="' . $field['name'] . '">' . $options . '</select>';
				break;
			case 'spacer':
				$field = '<p id="' . $field['name'] . '" name="' . $field['name'] . '">' . $field['label'] . '</p>';
				break;
			default:
				die('Konfiguracja: nieobsługiwany typ pola: "' . $field['type'] . '"');
				break;
		}
		return ($getLabel?$label:'') . $field;
	}
	public function getLabel($field){
		return '<label for="' . $field['name'] . '" title="' . $field['description'] . '">' . $field['label'] . '</label>';
	}
	protected function loadTranslation( $lang = 'pl-PL' ){
		$this->translations['JNO'] = 'nie';
		$this->translations['JYES'] = 'tak';
		$file = "$lang.config.ini";
		$fileContent = explode( "\n", file_get_contents(dirname(__FILE__) .'/'. $file) );
		foreach($fileContent as $line){
			if( mb_strlen(trim($line)) == 0 ){ //empty line
			}else if( $line[0] == ';' ){ // comment
			}else {
				if(false !== preg_match('/([0-9A-Z_]+)\s?=\s?\"([^"]*)\";/',$line,$matches)){
					$this->translations[$matches[1]] = $matches[2];
				}else{
					// błąd parsera
				}
				
			}
		}
	}
	protected function translate( $string ){
		if(isset($this->translations[$string])){
			return $this->translations[$string];
		}
		return $string;
	}
}