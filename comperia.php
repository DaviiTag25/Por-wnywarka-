<?php
/*
Plugin Name: Comperia
Plugin URI: www.comperialead.pl
Description: Dodatek do obsługi wyników z programu partnerskiego Comperialead.
Version: 3.1.7
Author: ComperiaLead
Author URI: www.comperialead.pl
License: GNU General Public License version 2 or later; see LICENSE.txt
Copyright: Copyright (C) 2013 

Copyright 2013  Comperia S.A. (email : helpdesk@comperialead.pl )

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

com_comperia reffers to plugin itself
*/

define('COMPERIA',true);
define('_JEXEC',true); // dla szablonów
define('COMPERIA_PATH_PLUGIN',dirname(__FILE__));
define('COMPERIA_URL_PLUGIN',plugin_dir_url(__FILE__)); // w/ slash

require_once COMPERIA_PATH_PLUGIN . '/library/dependency.php';// biblioteka z używanymi klasami
require_once COMPERIA_PATH_PLUGIN . '/controller.php';		// główny kontroler
require_once COMPERIA_PATH_PLUGIN . '/config.php';				// konfiguracja globalna pluginu
require_once COMPERIA_PATH_PLUGIN . '/router.php';				// router dla szczegółów
require_once COMPERIA_PATH_PLUGIN . '/page_options.php';		// obsługa edycji stron wp
require_once COMPERIA_PATH_PLUGIN . '/backend.php';			// tabela zaplecza
require_once COMPERIA_PATH_PLUGIN . '/models/comperia.php';	// model komunikacji z cl
require_once COMPERIA_PATH_PLUGIN . '/view/comperia.php';	// główny widok
require_once COMPERIA_PATH_PLUGIN . '/widget/widget.php';	// widget do wyświetlania produktów
$controller = new comperiaController();				// wszystkie akcje powinny przechodzić przez główny kontroler

add_action( 'widgets_init', 'comperia_widget_init');
function comperia_widget_init(){
     register_widget( 'Comperia_Widget' );
}
// get_pages - filtrowanie stron

// informacje o produktach
class ComperiaHelper{
	public static $produkty = array(
		 'kh'		=> array( 'a' => 'kredyt-hipoteczny'		, 'pl' => 'Kredyty hipoteczne'		)
		,'kg'		=> array( 'a' => 'kredyt-gotowkowy'			, 'pl' => 'Kredyty gotówkowe'			)
		,'ks'		=> array( 'a' => 'kredyt-samochodowy'		, 'pl' => 'Kredyty samochodowe'		)
		,'ph'		=> array( 'a' => 'pozyczka-hipoteczna'		, 'pl' => 'Pożyczki hipoteczne'		)
		,'kdf'	=> array( 'a' => 'kredyty-dla-firm'			, 'pl' => 'Kredyty dla firm'			)
		,'lb'		=> array( 'a' => 'lokaty-bankowe'			, 'pl' => 'Lokaty bankowe'				)
		,'lp'		=> array( 'a' => 'lokaty-progresywne'		, 'pl' => 'Lokaty progresywne'		)
		,'pl'		=> array( 'a' => 'polisolokaty'				, 'pl' => 'Polisolokaty'				)
		,'lbs'	=> array( 'a' => 'lokaty-strukturyzowane'	, 'pl' => 'Lokaty strukturyzowane'	)
		,'ko'		=> array( 'a' => 'konta-osobiste'			, 'pl' => 'Konta osobiste'				)
		,'kosz'	=> array( 'a' => 'konta-oszczednosciowe'	, 'pl' => 'Konta oszczędnościowe'	)
		,'kk' 	=> array( 'a' => 'karty-kredytowe'			, 'pl' => 'Karty kredytowe'			)
		,'lis'	=> array( 'a' => 'leasing'						, 'pl' => 'Leasing'						)
		,'chw'	=> array( 'a' => 'szybka-gotowka'			, 'pl' => 'Szybka gotówka'				)
		,'pzb'	=> array( 'a' => 'produkty-pozabankowe'	, 'pl' => 'Produkty pozabankowe'		)

	);
	public static 	$products = array(
		 'kh'		=> 'kredyty-hipoteczne'
		,'kg'		=> 'kredyty-gotowkowe'
		,'ks'		=> 'kredyty-samochodowe'
		,'ph'		=> 'pozyczki-hipoteczne'
		,'kdf'	=> 'kredyty-dla-firm'
		,'lb'		=> 'lokaty-bankowe'
		,'lp'		=> 'lokaty-progresywne'
		,'pl'		=> 'polisolokaty'
		,'lbs'	=> 'lokaty-strukturyzowane'
		,'ko'		=> 'konta-osobiste'
		,'kosz'	=> 'konta-oszczednosciowe'
		,'kk'		=> 'karty-kredytowe'
		,'lis'	=> 'leasing'
		,'chw'	=> 'szybka-gotowka'
		,'pzb'	=> 'produkty-pozabankowe'
	);
	public static $productsPol = array(
		 'kh'		=> 'Kredyty hipoteczne'	
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

	);
	public static function getApi(){
		$comperiaConfig = comperiaConfig::getInstance('comperiaConfig');
		return $comperiaConfig->get('api','');
	}
	public static function getApiUrl(){
		$comperiaConfig = comperiaConfig::getInstance('comperiaConfig');
		return $comperiaConfig->get('serwer','');
	}
	public static function stopka(){
		return '';
	}
	public static function gettargetAttr($target,$href){
		switch($target){
			case'_popup': 
				return 'onclick="openWindowComperia(\'' . $href . '\');return false;"';
				break;
			case'_blank':
			case'_self':
				return 'target="' . $target . '"';
				break;
			default:
		}
	}
}
class ComperiaInstall{
	public function __construct(){
		register_activation_hook( __FILE__, array($this,'run') );
	}
	public function run(){
		if( get_option( 'comperia_reinstall', 1) ){
			$this->addPages();
			update_option( 'comperia_reinstall', 0);
		}
	}
	public function addPages(){
		global $user_ID;
		global $wpdb;
		
		$toAdd = ComperiaHelper::$produkty;
		$pageOptions = pageOptions::getInstance('pageOptions');
		$existingPages = $pageOptions->get_existing_comperia_pages();
		$d = array();
		foreach( $toAdd as $produkt => $produktName ){
			foreach( $existingPages as $k => $v ){
				if( $k == $produkt ){
					unset($toAdd[$produkt]);
					continue;
				}
			}
		}
		//wyniki-porownan
		
		$wyniki_porownan = $wpdb->get_row("SELECT * FROM ".$wpdb->posts." WHERE post_name = 'wyniki-porownan'", 'ARRAY_A');

		if( !$wyniki_porownan ){
		$wyniki_porownan['post_type']    = 'page';
		$wyniki_porownan['post_content'] = '[comperia]';
		$wyniki_porownan['post_parent']  = 0;
		$wyniki_porownan['post_author']  = $user_ID;
		$wyniki_porownan['post_status']  = 'publish';
		$wyniki_porownan['post_title']   = 'Wyniki porównań';
		$wyniki_porownan['comment_status']   = 'closed';
		$parent_id = wp_insert_post($wyniki_porownan);
		}else{
			$parent_id = $wyniki_porownan['ID'];
		}
		
		
		foreach( $toAdd as $produkt => $produktName ){
			$page = array();
			$page['post_type']		= 'page';
			$page['post_content']	= '[comperia]';
			$page['post_parent']		= 0;
			$page['post_author']		= $user_ID;
			$page['post_status']		= 'publish';
			$page['post_title']		= $produktName['pl'];
			$page['comment_status']	= 'closed';
			$page['post_parent']		= $parent_id;
			$postComperia['produkt'] = $produkt;
			$postComperia['tableCaption'] = 0;
			$postComperia['showFormOnListDef'] = '';
			$postComperia['showFormOnListSearch'] = '';
			
			if( $produkt == 'pzb' ){
				$postComperia['showFormOnListDef'] = '1';
				$postComperia['showFormOnListSearch'] = '1';
			}
			
			$pageid = wp_insert_post ($page);
			if ($pageid == 0) {
				// strona się nie dodała
			}else{
				$page = get_post( $pageid, ARRAY_A );
				foreach( $postComperia as $k => $v ){
					if ( !add_post_meta( $pageid, '_comperia_' . $k, $v, true ) ) {
						update_post_meta( $pageid, '_comperia_' . $k, $v );
					}
				}
			}
		}
		$links_to_products = '<ul>';
		$pageOptions = pageOptions::getInstance('pageOptions');
		foreach( $pageOptions->get_existing_comperia_pages(true) as $k => $v ){
			$links_to_products .= '<li><a href="'. $v->permalink .'">'. $v->post_title .'</a></li>' ;
		}
		// zaktualizuj domyślną treść do wyników porównań dając tam linki do wszystkich produktów
		$wyniki_porownan['ID'] = $parent_id;
		$wyniki_porownan['post_content'] = $links_to_products.'</ul>';
		wp_update_post($wyniki_porownan);
	}
	public function addWidgets(){
		// do not add widgets
	}
}
new ComperiaInstall;
require 'plugin-updates/plugin-update-checker.php';

function check_link_translation($val){
	return 'Szukaj aktualizacji';
}
function check_update_translation($val){
	switch($val){
		case'This plugin is up to date.':
			return 'Plugin aktualny';
		case'A new version of this plugin is available.':
			return'Nowa wersja jest dostępna do pobrania. Przeprowadzenie aktualizacji jest wysoce zalecane.';
		default:
			return $val;
	}
}

add_filter( 'puc_manual_check_link-comperia', 'check_link_translation', 10, 1);
add_filter( 'puc_manual_check_message-comperia', 'check_update_translation', 10, 1);

$ExampleUpdateChecker = new PluginUpdateChecker(
	 'http://www.comperialead.pl/update/wordpress/update.json'
	,__FILE__
	,'comperia'
);