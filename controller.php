<?php
/**
 * @copyright	Copyright (C) 2013 Comperia S.A. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('COMPERIA') or die('');
class comperiaController{
	public $config;
	public $pageOptions;
	public $view;

	function __construct(){
		$produkt = ''; // może nie tu
		
		$this->config 			= comperiaConfig::getInstance('comperiaConfig');
		$this->pageOptions	= pageOptions::getInstance('pageOptions','default',array($this->config));
		$this->view				= new ComperiaView( $this->config );

		add_action('admin_menu', array( $this,'my_menu'));
		add_action('admin_head', array( $this, 'css') );
		add_action('init', array( 'plgSystemComperia', 'widgetClRedirect' ) );
		
		//rss na kokpicie
		add_action('wp_dashboard_setup', array($this, 'example_add_dashboard_widgets') );
		
		// sprawdzanie, czy jest komunikacja z API
		if( isset($_POST['config_submit']) && !in_array( ComperiaHelper::getApi(), array('!KLUCZ API!','') ) ){
			add_action('admin_notices', array( $this, 'checkApi' ) ) ;
		}
		
		// akcja czyszczenia cache
		if( isset($_POST['clearCache']) ){
			add_action('admin_notices', array( $this, 'clearCache' ) ) ;
		}
		
		// ponowna instalacja
		if( isset($_POST['reinstall_submit']) ){
			add_action('admin_notices', array( $this, 'reinstall' ) ) ;
		}
		
		// powiadomienia nie wymagające akcji
		add_action('admin_notices', array( $this, 'admin_notices' ) ) ;
		
		// 
		add_action('save_post', array($this,'refresh_permalinks') );
	}
	function example_add_dashboard_widgets() {
		wp_add_dashboard_widget('example_dashboard_widget', 'Blog Comperialead', array($this,'example_dashboard_widget_function') );	
	}
	function example_dashboard_widget_function() {
		echo '<div class="rss-widget">';  
		wp_widget_rss_output(array(  
			 'url'			=> 'http://blog.comperialead.pl/feed'
			,'title'			=> 'Blog Comperialead'
			,'items'			=> 4
			,'show_summary'=> 1
			,'show_author'	=> 0
			,'show_date'	=> 1
		));  
		echo "</div>";  
	} 

	public function admin_notices(){
		$isset_api = !in_array(get_option( 'comperia_api' ,''),array('','!KLUCZ API!'));
		if( $isset_api ){
			// wszystko przeczytane
		}else{
			//ważne informacje
			echo'<div class="updated" style="position:relative">
				<p>
					<img style="float:left" src="'. COMPERIA_URL_PLUGIN . 'media_comperia/images/16x16.png' .'" alt="comperia logo">
					'.($isset_api?'Podany klucz API wydaje się być niepoprawny. ':'').'Wprowadź klucz API w konfiguracji pluginu
					<a href="'.admin_url().'?page=COMPERIA_MENU_CONFIG&comperia_read=true#communication">tutaj</a>
				</p>
			</div>';
		}
		if( get_option( 'comperia_reinstall' ,'0') ){
			echo '<div class="updated">
				<p>Plugin Comperia jest gotowy do ponownej instalacji, aby jej dokonać przejdź do <a href="plugins.php">Listy wtyczek</a>, wyłącz i włącz plugin comperia</p>
			</div>';
		}
	}
	function css(){
		echo '<link rel="stylesheet" href="'. COMPERIA_URL_PLUGIN .'media_comperia/css/wp-admin.css' .'" />';
	}
	function my_menu() {
		global $my_admin_page;

		$my_admin_page = add_menu_page(
			 'Comperia Konfiguracja'
			,'Comperia Konfiguracja'
			,'manage_options'
			,'COMPERIA_MENU_CONFIG'
			,array($this,'configurationPage')
			,COMPERIA_URL_PLUGIN . 'media_comperia/images/16x16.png'
			,'99.223'
		);
		add_action('load-'.$my_admin_page, array($this,'help'));
	}
	function help(){
		global $my_admin_page;
		$screen = get_current_screen();
		if ( $screen->id != $my_admin_page ){
			return;
		}
		$screenIds = array( 
			 // array('id' => 'start'	, 'url' => 'http://www.comperialead.pl/help/instrukcja-obslugi-pluginu-comperialead-dla-wordpress/' )
			 array('id' => 'widgets'	, 'url' => '' )
			,array('id' => 'more_info'	, 'url' => 'http://www.comperialead.pl/help/wordpress' )
		);
		$tabs = array(
			array(
				 'id'			=> $screenIds[0]['id']
				,'title'		=> __('Rozpoczynanie')
				,'content'	=> '<iframe id="api_help_iframe" class="helpframe" src=""></iframe>
					<small><a href="'.$screenIds[0]['url'].'" target="_blank">zobacz w nowej zakładce</a></small>
					<script>
						jQuery(document).ready(function(){
							jQuery("#contextual-help-link,.contextual-help-tabs a[aria-controls=tab-panel-'. $screenIds[0]['id'] .']").click(function(){
								jQuery("#tab-panel-'. $screenIds[0]['id'] .' iframe").attr("src","'.$screenIds[0]['url'].'");
							});
						});
					</script>
					'
			),array(
				 'id'			=> $screenIds[1]['id']
				,'title'		=> __('Widgety')
				,'content'	=> '<p>' . __( 'Treść do widgetów' ) . '</p>'
			),array(
				 'id'			=> $screenIds[2]['id']
				,'title'		=> __('Więcej informacji')
				,'content'	=> '<p>Aktualna wersja instrukcji dostępna pod adresem <a target="_blank" href="'.$screenIds[2]['url'].'">'.$screenIds[2]['url'].'</a></p>'
			)
		);
		foreach($tabs as $tab){
			// $screen->add_help_tab( $tab );
			
		}
	}
	function configurationPage(){
		// odświeża reguły przepisywania(to samo, co ustawienia->bezpośrednie odnośniki)
		global $wp_rewrite;
		$wp_rewrite->flush_rules();

		$checked = get_option( 'comperia_reinstall', 1)?' checked':'';
		$cacheInfo = $this->getCacheInfo();

		$testListTable = new comperia_list_table();
		$testListTable->prepare_items();
		?>
		<h1><img alt="logo Comperia" src="<?php echo COMPERIA_URL_PLUGIN; ?>media_comperia/images/16x16.png"> Plugin Comperia - konfiguracja</h1>
		<table id="comperia_config_table">
			<tr>
				<td style="width:65%">
					<form action="" method="get">
						<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
						<?php $testListTable->display() ?>
						<style>
							.green{color:#00ff00}
							.red{color:#ff0000}
							.yellow{color:#ffff00}
							/* fix wp style - not in wp-head, so it wont break anything */
												tr:hover .row-actions{visibility:hidden}
							.wp-list-table tr:hover .row-actions{visibility:visible}
						</style>
					</form>
				</td>
				<td style="width:35%">
					<h3>Cache</h3>
					<form action="" method="post">
						<p>Cache zawiera obecnie <span class="ilosc"><?php echo $cacheInfo['cacheCount'] ?></span> plików i zajmuje <?php echo $this->sizeFilter($cacheInfo['cacheSize']); ?> na dysku. Pamięć podręczna przyspiesza stronę. Warto jednak wyczyścić je co kilka tygodni.</p>
						<input type="submit" name="clearCache" value="Wyczyść cache" class="button button-primary">
					</form>
					<hr>
					<h3>Opcje</h3>
						<?php echo $this->config->getForm(); ?>
					<hr>
					<form action="" method="post">
						<div class="row">
							<label for="reinstall" title="Jeśli checkbox jest zaznaczony, to przy ponownym włączeniu pluginu zostaną dodane produkty nie posiadające przypisanej strony. Po instalacji pole będzie odznaczone.">Zainstalować ponownie</labe>
							<input id="reinstall" name="reinstall" type="checkbox"<?php echo $checked; ?>>
						</div>
						<input type="submit" name="reinstall_submit" value="Zapisz" class="button button-primary">
					</form>
				</td>
			</tr>
		</table>
		<?php if( in_array(get_option( 'comperia_api' ,''),array('','!KLUCZ API!')) || !get_option( 'comperia_period_api_check', 0 ) ):?>
		<style>#api{border-color:red!important}</style>
		<?php endif; ?>
		<script>
			jQuery(document).ready(function(){
				var allParents	= jQuery('fieldset','#comperia_config_table');
				var allPanels	= jQuery('.fieldset-content','#comperia_config_table').hide();
				jQuery('#communication').addClass('active').children('.fieldset-content').show();
				jQuery('.comperia_form fieldset legend a','#comperia_config_table').click(function() {
					$this = jQuery(this);
					$parent = $this.parent().parent();
					$target = $this.parent().next();
					if(!$parent.hasClass('active')){
						allParents.removeClass('active')
						allPanels.slideUp();
						$parent.addClass('active')
						$target.slideDown();
					}
					return false;
				});
			});
		</script>
		<?php
	}
	function getCacheInfo(){
		$path			= COMPERIA_PATH_PLUGIN.'/cache';
		$cacheSize	= 0;
		$files		= scandir($path);
		$cleanPath	= rtrim($path, '/'). '/';

		foreach($files as $t) {
			if ($t<>"." && $t<>"..") {
				$currentFile = $cleanPath . $t;
				if ( !is_dir($currentFile) ){
					$size = filesize($currentFile);
					$cacheSize += $size;
				}
			}
		}

		return array(
			 'cacheCount'	=> count($files) - 2 // . ..
			,'cacheSize'	=> $cacheSize
		);
	}
	function sizeFilter( $bytes ){
		$label = array( 'B', 'KB', 'MB', 'GB', 'TB', 'PB' );
		for( $i = 0; $bytes >= 1024 && $i < ( count( $label ) -1 ); $bytes /= 1024, $i++ );
			return( round( $bytes, 2 ) . " " . $label[$i] );
	}
	function refresh_permalinks(){
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
	}
	// actions
	
	function reinstall(){
		update_option('comperia_reinstall',(int)isset($_POST['reinstall']));
	}
	function clearCache(){
		$path			= COMPERIA_PATH_PLUGIN.'/cache';
		$count		= 0;
		$errors		= 0;
		$files		= scandir($path);
		$cleanPath	= rtrim($path, '/'). '/';
		
		foreach($files as $t) {
			if ($t<>"." && $t<>"..") {
				$currentFile = $cleanPath . $t;
				if ( !is_dir($currentFile) ){
					if(unlink($cleanPath.$t)){
						$count++;
					}else{
						$errors++;
					}
				}
			}
		}
		echo '<div class="updated">
			<p>Poprawnie usunięto '.$count.' plików z cache</p>
			'.($errors>0?'<p>Niestety nie można było usunąć '. $errors .' plików. Prawdopodobnie są właśnie używane.</p>':'').'
		</div>';
	}
	function checkApi(){
		if( !in_array( ComperiaHelper::getApi(), array('!KLUCZ API!','') )){
			$params = array(
				 'format'	 	=> 'serialize'
				,'polecenie'	=> 'formularz'
				,'produkt'		=> 'kg'
				,'api'			=> ComperiaHelper::getApi()
				,'host'			=> $_SERVER['HTTP_HOST']
			);

			$curlHandle = curl_init();
			curl_setopt( $curlHandle, CURLOPT_URL, ComperiaHelper::getApiUrl() );
			curl_setopt( $curlHandle, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt( $curlHandle, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT'] );
			curl_setopt( $curlHandle, CURLOPT_RETURNTRANSFER, true);
			curl_setopt( $curlHandle, CURLOPT_TIMEOUT, 10 );
			curl_setopt( $curlHandle, CURLOPT_FAILONERROR, true);
			curl_setopt( $curlHandle, CURLOPT_POST, 1);
			curl_setopt( $curlHandle, CURLOPT_POSTFIELDS, http_build_query($params));
			$r = unserialize(curl_exec($curlHandle));
			$isWorking = false;
			if( !empty($r['odpowiedz']) ){
				$isWorking = true;
			}
			if( !$isWorking ){
				$curlInfo = curl_getinfo($curlHandle);
				if( isset($curlInfo['http_code']) && '200' ==  $curlInfo['http_code'] ){
					if( !empty($r['info']) ){
						$guess = $r['info'];
					}else{
						$guess = 'Wystąpił nieznany błąd';
					}
				}else{
					$guess = 'Wordpress nie może nawiązać połączenia. Spróbuj ponownie za jakiś czas. Jeśli problem będzie się powtarzał skontaktuj się z administratorem serwera.';
				}
				echo '<div class="error">
					<p>Wystąpił problem z połączeniem z API Comperialead</p>
					<p>'.$guess.'</p>
				</div>';
				update_option( 'comperia_period_api_check', 0 );
			}else{
				update_option( 'comperia_period_api_check', time() );
			}
		}
	}
}