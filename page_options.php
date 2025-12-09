<?php
/**
 * @copyright	Copyright (C) 2013 Comperia S.A. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('COMPERIA') or die('');
class pageOptions extends JIObject{
	public $config;
	public $params;
	protected $_existingComperiaPages = null;
	protected $_originalFields = array();
	
	function __construct( $config ){ //ji tu $config, to konfiguracja globalna, a nie tak, jak wskazuje JIObject
		$this->config = &$config[0];
		$this->params = PageRegistry::getInstance('PageRegistry');
		$this->_originalFields = $this->params->fields;
		add_action( 'add_meta_boxes', array( $this, 'comperia_add_custom_box' ) );
		add_action( 'save_post', array( $this, 'save_comperia_page' ) );
	}
	
	function comperia_add_custom_box() {
		add_meta_box(
			 'comperia-page-options'
			,'Opcje Comperia'
			,array( $this, 'render_meta_box_content' )
			,'page'
			,'side'
			,'high'
		);
	}
	public function save_comperia_page( $post_id ) {
		if ( isset($_POST['post_type']) && 'page' == $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_page', $post_id ) )
				return;
		} else {
			if ( ! current_user_can( 'edit_post', $post_id ) )
				return;
		}
		if ( ! isset( $_POST['comperia_nonce'] ) || ! wp_verify_nonce( $_POST['comperia_nonce'], plugin_basename( __FILE__ ) ) )
			return;
		
		foreach( $this->params->fields as $kfield => &$field ){
			if( isset($_POST[ $kfield ]) ){
				switch( $field['type'] ){
					// case'':
						// break;
					case'list':
					case'text':
					case'textarea':
					default:
						$field['value'] = sanitize_text_field( $_POST[ $kfield ] );
						break;
				}
				if ( !add_post_meta( $post_id, '_comperia_' . $field['name'], $field['value'], true ) ) {
					update_post_meta( $post_id, '_comperia_' . $field['name'], $field['value'] );
				}
			}else{
				echo'brak wartości dla ' . $kfield;
			}
		}
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
	}
	// pobiera wartości z wp_option
	public function getValues($post){
		$globalConfig = comperiaConfig::getInstance('comperiaConfig');
		foreach( $this->params->fields as &$field ){
			$field['value'] = esc_attr(get_post_meta( $post->ID, '_comperia_' . $field['name'], true ));
			// fallback to global configuration
			if( in_array( $field['name'], array( 'showFormOnListDef','showFormOnListSearch'  )) && $field['value'] == '' ){
				$field['value'] = $globalConfig->get($field['name']);
			}
		}
	}
	public function render_meta_box_content( $post ) {
		wp_nonce_field( plugin_basename( __FILE__ ), 'comperia_nonce' );
		global $wpdb;

		$this->getValues($post);
		echo'<div class="comperia_form">';
		foreach( $this->params->fields as $kfield => $field ){
			$existingProduktPages = $field['name']=='produkt'?$wpdb->get_col("SELECT meta_value FROM " . $wpdb->postmeta . " WHERE	meta_key='_comperia_produkt' AND meta_value<>''"):array();
			echo '<div class="row">' . $this->config->getFieldHtml( $field, true, $existingProduktPages ) . '</div>';
		}
		echo'</div>';
	}
	
	public function get_existing_comperia_pages( $refresh = false ){
		if( $this->_existingComperiaPages === null || $refresh ){
			global $wpdb;
			$sql = "
				SELECT p.id, p.post_title, p.post_name, pm.meta_value as produkt, p.post_status as post_status FROM `".$wpdb->posts."` AS p
				JOIN `".$wpdb->postmeta."` AS pm ON pm.post_id = p.id
				WHERE 1=1
				AND pm.meta_key = '_comperia_produkt'
				AND pm.meta_value <> ''
				";
			$existingProduktPages = $wpdb->get_results($sql);
			foreach( $existingProduktPages as $k => $v ){
				$existingProduktPages[$v->produkt]					= $v;
				$existingProduktPages[$v->produkt]->permalink	= get_permalink( $v->id ); 
				$path = parse_url( $existingProduktPages[$v->produkt]->permalink );
				$path = trim($path['path'],'/');
				$existingProduktPages[$v->produkt]->path = $path; 
				unset($existingProduktPages[$k]);
			}
			$this->_existingComperiaPages = $existingProduktPages;
		}
		return $this->_existingComperiaPages;
	}
}
class JIRegistry extends JIObject{
	public $fields = array();
	public function get( $val, $default = '' ){
		return empty( $this->fields[ $val ]['value'] ) ? $default : $this->fields[ $val ]['value'];
	}
}
class PageRegistry extends JIRegistry{
	public $fields = array(
		'produkt' => array(
			 'name' 			=> 'produkt'
			,'type' 			=> 'list'
			,'default'		=> ''
			,'label' 		=> 'Produkt'
			,'description'	=> 'Produkt do przedstawienia na tej stronie, "nie używaj" = strona nie obsługiwana przez plugin Comperia'
			,'value' 		=> ''
			,'options' => array(
				 '' 		=> 'Nie używaj'
				,'kh'		=> 'Kredyty hipoteczne'		
				,'kg'		=> 'Kredyty gotówkowe'			
				,'ks'		=> 'Kredyty samochodowe'		
				,'ph'		=> 'Pożyczki hipoteczne'		
				,'kdf'	=> 'Kredyty dla firm'			
				,'lb'		=> 'Lokaty bankowe'				
				,'lp'		=> 'Lokaty progresywne'		
				,'pl'		=> 'Polisolokaty'				
				,'lbs'	=> 'Lokaty strukturyzowane'	
				,'ko'		=> 'Konta osobiste'				
				,'kosz'	=> 'Konta oszczędnościowe'	
				,'kk' 	=> 'Karty kredytowe'			
				,'lis'	=> 'Leasing'						
				,'chw'	=> 'Szybka gotówka'				
				,'pzb'	=> 'Produkty pozabankowe'		
			)
		)
		// ,'tekst_list_top' => array(
			 // 'name'			=> 'tekst_list_top'
			// ,'type'			=> 'textarea'
			// ,'default'		=> ''
			// ,'label'			=> 'Nad'
			// ,'description'	=> 'Tekst nad listą wyników'
			// ,'value'			=> ''
		// )
		// ,'tekst_list_bottom' => array(
			 // 'name'			=> 'tekst_list_bottom'
			// ,'type'			=> 'textarea'
			// ,'default'		=> ''
			// ,'label'			=> 'Pod'
			// ,'description'	=> 'Tekst pod listą wyników'
			// ,'value'			=> ''
		// )
		,'tableCaption' => array(
			 'name'			=> 'tableCaption'
			,'type'			=> 'list'
			,'label'			=> 'Nagłówek tabeli'
			,'description'	=> 'Czy pokazywać element &lt;caption&gt; tabeli'
			,'default'		=> '0'
			,'value'			=> ''
			,'options' => array(
				 '1' => 'Tak'
				,'0' => 'Nie'
			)
		)
		,'showFormOnListDef' => array(
			 'name'			=> 'showFormOnListDef'
			,'type'			=> 'list'
			,'label'			=> 'Co pokazywać(domyślnie)?'
			,'description'	=> 'Co pokazać, jeśli użytkownik nie wybrał żadnych opcji filtrowania'
			,'default'		=> '2'
			,'value'			=> ''
			,'options' => array(
				 '' => 'Domyślnie'
				,'0' => 'Formularz i listę wyników'
				,'1' => 'Listę wyników'
				,'2' => 'Formularz'
			)
		)
		,'showFormOnListSearch' => array(
			 'name'			=> 'showFormOnListSearch'
			,'type'			=> 'list'
			,'label'			=> 'Co pokazywać(wyszukanie)?'
			,'description'	=> 'Co pokazać, jeśli użytkownik wyszukał rozwiązania dla siebie. Nie można niepokazać wyników'
			,'default'		=> '1'
			,'value'			=> ''
			,'options' => array(
				 ''	=> 'Domyślnie'
				,'0'	=> 'Formularz i listę wyników'
				,'1'	=> 'Listę wyników'
			)
		)
		// ,'tekst_szczegoly_top' => array(
			 // 'name'			=> 'tekst_szczegoly_top'
			// ,'type'			=> 'textarea'
			// ,'default'		=> ''
			// ,'label'			=> 'Nad'
			// ,'description'	=> 'Tekst nad listą wyników'
			// ,'value'			=> ''
		// )
		// ,'tekst_szczegoly_bottom' => array(
			 // 'name'			=> 'tekst_szczegoly_bottom'
			// ,'type'			=> 'textarea'
			// ,'default'		=> ''
			// ,'label'			=> 'Pod'
			// ,'description'	=> 'Tekst pod listą wyników'
			// ,'value'			=> ''

		// )
	);
}
class viewParams extends JIRegistry{
	public $fields = array(
		'page_title' => array(
			 'name'			=> 'page_title'
			,'type'			=> 'string'
			,'default'		=> ''
			,'label'			=> 'Tytuł strony'
			,'description'	=> 'Tytuł pobrany z tytułu strony'
			,'value'			=> ''
		)
	);
	public function __construct( $config = array() ){
		$fields['page_title']['value'] = get_the_title();
	}
}






