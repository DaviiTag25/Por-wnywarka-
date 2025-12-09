<?php
/**
 * @copyright	Copyright (C) 2013 Comperia S.A. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

if(!class_exists('WP_List_Table')){
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
class comperia_list_table extends WP_List_Table {
		public $items = array();
		function __construct(){
			parent::__construct( array(
				'singular'  => 'item',
				'plural'    => 'items',
				'ajax'      => false
			) );
		}
		function column_default($item, $column_name){
			switch($column_name){
				case 'post_title':
					return $item->$column_name;
				case 'post_status':
					switch($item->post_status){
						case'publish': $r = '<span class="green">Opublikowany</span>';
							break;
						case'draft': $r = '<span class="red">Szkic</span>';
							break;
						case'pending': $r = '<span class="red">Oczekuje na przegląd</span>';
							break;
						case'trash': $r = '<span class="red">Kosz</span>';
							break;
						default:
							$r = '<span class="yellow">'.$item->post_status.'</span>';
					}
					return $r;
				default:
					return print_r($item,true);
			}
		}
		function column_post_title($item){
			$actions = array(
				 'edit'			=> sprintf('<a href="%s">Edytuj</a>',$item->edit)
				// ,'unpublish'	=> sprintf('<a href="?page=%s&action=%s&item=%s">Wycofaj</a>',$_REQUEST['page'],'unpublish',$item->id)
			);
			if($item->post_status == 'publish'){
				$actions['trash'] = sprintf('<a href="?page=%s&action=%s&item=%s">Kosz</a>',$_REQUEST['page'],'trash',$item->id);
			}else if($item->post_status == 'trash'){
				$actions['publish'] = sprintf('<a href="?page=%s&action=%s&item=%s">Opublikuj</a>',$_REQUEST['page'],'publish',$item->id);
			}
			
			return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s'
				,$item->post_title
				,$item->id
				,$this->row_actions($actions)
			);
		}
		function column_cb($item){
			return sprintf( '<input type="checkbox" name="%1$s[]" value="%2$s" />', $this->_args['singular'], $item->id );
		}
		function get_columns(){
			$columns = array(
				 'cb'				=> '<input type="checkbox" />'
				,'post_title'	=> 'Tytuł'
				,'post_status'	=> 'Stan'
			);
			return $columns;
		}
	function get_bulk_actions() {
		$actions = array(
			 'publish'		=> 'Opublikuj'
			// ,'unpublish'	=> 'Wycofaj'
			,'trash'	=> 'Kosz'
		);
		return $actions;
	}
	function process_bulk_action() {
		if(isset($_GET['item'])){
		// var_dump($_GET['item']);
			// echo '<pre>'.print_r(array($items,$_GET,$this->current_action()),true).'</pre>';die;
			if(is_array($_GET['item'])){
				$items = $_GET['item'];
			} elseif(is_numeric($_GET['item'])){
				$items = array($_GET['item']);
			}else{
			}
			switch( $this->current_action() ){
				case'publish':
					foreach($items as $item){
						wp_publish_post( $item );
					}
					break;
				case'unpublish':
					foreach($items as $item){
						$post = array(
							 'ID' => $item
							,'post_status' => 'draft'
						);
						wp_update_post( $post );
					}
					break;
				case'trash':
					foreach($items as $item){
						$post = array(
							 'ID' => $item
							,'post_status' => 'trash'
						);
						wp_update_post( $post );
					}
					break;
				default: return;
			}
		}
    }
    function prepare_items() {
		$columns = $this->get_columns();
		// echo '<pre>'.print_r($columns,true).'</pre>';die;
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);
		$this->process_bulk_action();
		$data = array();
		foreach(ComperiaHelper::$produkty as $kprodukt => $produkt ){
			$found = false;
			$pageOptions = pageOptions::getInstance('pageOptions');
			foreach($pageOptions->get_existing_comperia_pages(true) as $kpage => $page ){
				if($kprodukt == $kpage){ 
					$found = true;
					$data[$kpage]			= $page;
					$data[$kpage]->edit	= 'post.php?post='.$data[$kpage]->id.'&action=edit';
					continue 2; 
				}
			}
			if( !$found ){
				$data[$kpage]			= new stdClass();
				$data[$kpage]->add	= 'post-new.php?post_type=page';
			}
		}
		$this->items = $data;
    }
    
}