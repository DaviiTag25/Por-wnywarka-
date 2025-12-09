<?php
/**
 * @copyright	Copyright (C) 2013 Comperia S.A. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */


function add_query_vars($aVars) {
	$aVars[] = 'szczegoly';
	$aVars[] = 'argumenty';
	return $aVars;
}
add_filter('query_vars', 'add_query_vars');

function add_rewrite_rules($aRules) {
	$pageOptions = pageOptions::getInstance('pageOptions');
	$pagesThatUsesComperia = $pageOptions->get_existing_comperia_pages(true);
	foreach( $pagesThatUsesComperia as $page ){
		$aNewRules = array( $page->path .'/([^/]+)/?$' => 'index.php?pagename=' . $page->path . '&szczegoly=$matches[1]');
		$aRules = $aNewRules + $aRules;
	}
	return $aRules;
}
add_filter('rewrite_rules_array', 'add_rewrite_rules');


// $szczegóły - array()$_GET url -> friendly_url_string oddaje tylko ostatni segment
// query musi posiadać $query[produkt] $query[id_oferty], ew. $query[argumenty]=arary
function SzczegolyBuildRoute($query)
{
	$model = ComperiaModel::getInstance('ComperiaModel');
	$query = array_merge( $query , $_POST );
	$lastSegment = array();
	$produkt = $query['produkt'];
	unset( $query['produkt'] );
	unset( $query['p'] );
	if( isset( $query['id_oferty'] ) ){
		$lastSegment[] = $query['id_oferty'];
		unset($query['id_oferty']);
	}else{
		return false; // link nie posiada id_oferty
	}			

	//get filtering fields from form
	$formFields = $model->getForm( $produkt );

	if( !empty($query['argumenty']) ){
		foreach( $formFields['odpowiedz']['formularz'] as $k => $field ){
			if( in_array( $field['name'] , array('p','produkt')) ){
				continue;
			}
			$name = preg_match('/argumenty\[([^]]+)\]/',$field['name'],$matches);
			if( $field['tag'] == 'input'){
				if( $field['type'] == 'text' ){
					$lastSegment[$k] = $query['argumenty'][$matches[1]];
					unset($query['argumenty'][$matches[1]]);
					if( empty($query['argumenty']) ){
						unset( $query['argumenty'] );
					}
				}else{
				}
			}else if( $field['tag'] == 'select' ){
				$selectValue = $query['argumenty'][$matches[1]];
				$lastSegment[$k] = $query['argumenty'][$matches[1]];
				unset($query['argumenty'][$matches[1]]);
			}
		}
	}
	return implode(',',$lastSegment);
}

// $szczegoly - string -> array
function SzczegolyParseRoute($szczegoly,$produkt)
{
	$model = ComperiaModel::getInstance('ComperiaModel');
	$vars = array();
	$vars['produkt'] = $produkt;

	$szczegoly = explode(',',$szczegoly);

	$vars['id_oferty'] = $szczegoly[0];
	unset($szczegoly[0]);
	
	$argumenty = $szczegoly;
	$formFields = $model->getForm( $vars['produkt'] );
	if( isset($argumenty[1]) && !empty($argumenty) ){
		$argument = current($argumenty);
		foreach( $formFields['odpowiedz']['formularz'] as $kfield => $field ){
			if( in_array( $field['name'] , array('p','produkt')) ){
				continue;
			}
			if (mb_substr( $field['name'] , 0, 9, 'utf-8') === 'argumenty' ) {
				$fieldName = mb_substr( $field['name'] , 10, -1, 'utf-8' );
			}
			$vars['argumenty'][ $fieldName ] = $argument;
			$argument = next($argumenty);
		}
	}
	return $vars;
}
// klasa do przekierowywania adresu wygenerowanego w widgecie na obecnie używany w pluginie
class plgSystemComperia
{
	static $tlumaczenieNazwOfert = array(
		 'oferty_leasingu'								=> 'leasing'
		,'kdfb'												=> 'kdf'
		,'kdfh'												=> 'kdf'
		,'kdfi'												=> 'kdf'
		,'kdfs'												=> 'kdf'
		,'kdf_finansowanie_dzialalnosci_biezacej'	=> 'kdf'
		,'kdf_kredyty_hipoteczne'						=> 'kdf'
		,'kdf_kredyty_samochodowe'						=> 'kdf'
		,'kdf_finansowanie_inwestycji'				=> 'kdf'
	);
	static function widgetClRedirect()
	{
		if( in_array(JRequest::getInt( 'p' , 0 ), array( 2, 5 ) ) && JRequest::getVar( 'widget_id', null ) ){
			$get = JRequest::get('get');
			$pageOptions = pageOptions::getInstance('pageOptions');
			$pagesThatUsesComperia = $pageOptions->get_existing_comperia_pages();

			// pobranie produktu
			if( isset($get['produkt']) ){
				$produkt = strtolower($get['produkt']);
				unset($get['produkt']);
			}else{
				reset($get);
				$produkt		= strtolower(key($get));
				unset($get[$produkt]);
			}
			$produkt = isset(self::$tlumaczenieNazwOfert[$produkt])?self::$tlumaczenieNazwOfert[$produkt]:$produkt;			
			$produkt = (
				array_key_exists($produkt,ComperiaHelper::$products)
				? $produkt
				: array_search(str_replace('_','-',$produkt),ComperiaHelper::$products)
			);
			$get['produkt'] = $produkt;

			$getS = array();
			$mustHaveFilters = array(); // tylko dla list
			if( JRequest::getInt( 'p' , 0 ) == 2 && isset($get['argumenty']) ){ 
				// router tego nie obsłuży, a usuwam zbedne pola - trzeba wyciągnąć z formularza - tylko dla listy
				$model = ComperiaModel::getInstance('ComperiaModel');
				$formFields = $model->getForm( $produkt );
				foreach( $formFields['odpowiedz']['formularz'] as $kfield => $field ){
					if( in_array( $field['name'] , array('p','produkt')) ){
						continue;
					}
					$name = preg_match('/argumenty\[([^]]+)\]/',$field['name'],$matches);
					$mustHaveFilters[$field['name']] = $get['argumenty'][$matches[1]];
				}
				$widget_id = $get['widget_id'];
				$szczegoly = '';
			}else if(JRequest::getInt( 'p' , 0 ) == 5){
				$getS['produkt'] = $get['produkt'];
				$getS['id_oferty']	= $get['id_oferty'];
				foreach( $get as $k => $v ){
					if( $k == 'widget_id' ){
						$widget_id = $v;
						continue;
					}
					$getS['argumenty'][$k] = $v; //= 'argumenty[' . $k . ']=' . $v;
				}
				$szczegoly = SzczegolyBuildRoute($getS);
				$id_oferty	= $get['id_oferty'];
				unset($get['id_oferty']);
			}
			unset($get['p']);

			// routowanie, ostatnie tłumaczenie i czyszczenie argumentów
			$url = trailingslashit($pagesThatUsesComperia[$produkt]->permalink) . $szczegoly;
			$url = parse_url($url);
			parse_str($url['query'],$url_variables);
			if( !empty($widget_id) ){
				$url['query'] = http_build_query( array_merge($url_variables,array('widget_id'=>$widget_id), $mustHaveFilters) );
			}else{
				$url['query'] = http_build_query( array_merge($url_variables, $mustHaveFilters) );
			}
			$url = $url['scheme'] .'://'. $url['host'] . $url['path'] . '?'. ( empty($url['query']) ? '' : '?'.$url['query'] ) ;
			wp_redirect( $url, 301 );
			exit;
		}
		
	}
}
