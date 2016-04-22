<?php if (!defined('WPINC')) die();

class PdfLightViewer_FrontController {
	
	public static function getConfig($atts, $post) {
		$pdf_light_viewer_config = self::parseDefaultsSettings($atts, $post);
	
		// download options
			if ($pdf_light_viewer_config['download_allowed']) {
				$pdf_file_id = PdfLightViewer_Model::getPDFFileId($post->ID);
				$pdf_file_url = wp_get_attachment_url($pdf_file_id);
				
				$alternate_download_link = PdfLightViewer_Plugin::get_post_meta($post->ID, 'alternate_download_link', true);
				
				$pdf_light_viewer_config['download_link'] = ($alternate_download_link ? $alternate_download_link : $pdf_file_url);
			}
		
		$pdf_upload_dir = PdfLightViewer_Plugin::getUploadDirectory($post->ID);
		$pdf_upload_dir_url = PdfLightViewer_Plugin::getUploadDirectoryUrl($post->ID);
		
		$pdf_light_viewer_config['pdf_upload_dir_url'] = $pdf_upload_dir_url;
		
		$pages = directory_map($pdf_upload_dir);
		$thumbs = directory_map($pdf_upload_dir.'-thumbs');
		
		if (empty($pages) || empty($thumbs)) {
			echo '<span style="color: Salmon">'.__('[pdf-light-viewer] shortcode cannot be rendered due to the error: No converted pages found',PDF_LIGHT_VIEWER_PLUGIN).'</span>';
			return;
		}
		
		sort($pages);
		sort($thumbs);
		
		// check permissions
			$current_user = wp_get_current_user();
			$current_user_roles = $current_user->roles;
		
			$pages_limits = PdfLightViewer_Plugin::get_post_meta($post->ID, 'pdf_light_viewer_permissions_metabox_repeat_group', true);
			
			$limit = 0;
			if (!empty($pages_limits)) {
				foreach($pages_limits as $pages_limit) {
					if (empty($current_user_roles) && $pages_limit['pages_limit_user_role'] == 'anonymous') {
						$limit = isset($pages_limit['pages_limit_visible_pages']) ? $pages_limit['pages_limit_visible_pages'] : null;
					}
					else if(in_array($pages_limit['pages_limit_user_role'], $current_user_roles)) {
						$limit = isset($pages_limit['pages_limit_visible_pages']) ? $pages_limit['pages_limit_visible_pages'] : null;
					}
				}
			}
			
		// limit allowed pages for user role
			if (!$limit) {
				$pdf_light_viewer_config['pages'] = $pages;
				$pdf_light_viewer_config['thumbs'] = $thumbs;
			}
			else {
				for($page = 0; $page < $limit; $page++) {
					$pdf_light_viewer_config['pages'][$page] = $pages[$page];
					$pdf_light_viewer_config['thumbs'][$page] = $thumbs[$page];
				}
			}
			
		$pdf_light_viewer_config = apply_filters(PDF_LIGHT_VIEWER_PLUGIN.':front_config', $pdf_light_viewer_config, $post);
		
		return $pdf_light_viewer_config;
	}
	
	public static function disaply_pdf_book($atts = array()) {
		global $pdf_light_viewer_config;
	
		if (!isset($atts['id']) || !$atts['id']) {
			return;
		}
		
		$post = get_post($atts['id']);
		if (empty($post) || !$post->ID) {
			return;
		}
		
		$pdf_light_viewer_config = static::getConfig($atts, $post);
		
		ob_start();
		ob_clean();
		
		// the loop
			if (locate_template($pdf_light_viewer_config['template'].'.php') != '') {
				get_template_part($pdf_light_viewer_config['template']);
			}
			else {
				if (file_exists(PDF_LIGHT_VIEWER_APPPATH.'/templates/'.$pdf_light_viewer_config['template'].'.php')) {
					include(PDF_LIGHT_VIEWER_APPPATH.'/templates/'.$pdf_light_viewer_config['template'].'.php');
				}
				else {
					include(PDF_LIGHT_VIEWER_APPPATH.'/templates/shortcode-pdf-light-viewer.php');
				}
			}
			
	

		return str_ireplace(["\n", "\r"], ' ', ob_get_clean());
	}
	
	
	public static function parseDefaultsSettings($args, $post = null) {
		$defaults = array(
			'template' => 'shortcode-pdf-light-viewer',
			'download_link' => '',
			'download_allowed' => (bool)PdfLightViewer_Plugin::get_post_meta($post->ID, 'download_allowed', true),
			'hide_thumbnails_navigation' => (bool)PdfLightViewer_Plugin::get_post_meta($post->ID, 'hide_thumbnails_navigation', true),
			'hide_fullscreen_button' => (bool)PdfLightViewer_Plugin::get_post_meta($post->ID, 'hide_fullscreen_button', true),
			'disable_page_zoom' => (bool)PdfLightViewer_Plugin::get_post_meta($post->ID, 'disable_page_zoom', true),
			'page_width' => PdfLightViewer_Plugin::get_post_meta($post->ID, 'pdf-page-width', true),
			'page_height' => PdfLightViewer_Plugin::get_post_meta($post->ID, 'pdf-page-height', true),
            		'force_one_page_layout' => (bool)PdfLightViewer_Plugin::get_post_meta($post->ID, 'force_one_page_layout', true),
		
			'pages' => array(),
			'thumbs' => array(),
		
			'print_allowed' => false,
			'enabled_pdf_text' => false,
			'enabled_pdf_search' => false,
			'enabled_archive' => false
		);
						
		return wp_parse_args($args, $defaults);
	}
	
	
	public static function generate_template_item_css_classes() {
		$css_classes = '';
		return $css_classes;
	}
	
	
	public static function getPageLink($number) {
		$url = apply_filters(PDF_LIGHT_VIEWER_PLUGIN.':front_get_page_link', $number);
		
		if (!$url || $url == $number) {
			$url = '#page/'.$number;
		}
		
		return $url;
	}
}
