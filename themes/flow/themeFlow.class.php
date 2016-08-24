<?php

class themeFlow extends Theme
{

    public $config = null;

    public function __construct()
    {
        // get the plugin config
        include('_theme_config.inc.php');

        // load config into the object
        $this->config = $themeConfig;
    }

    public function getThemeDetails()
    {
        return $this->config;
    }
	
	public function getMainLogoUrl()
	{
		// get database
		$db = Database::getDatabase();

		// see if the replaced logo exists
		$localCachePath = CACHE_DIRECTORY_ROOT . '/themes/'.$this->config['folder_name'].'/logo.png';
		if(file_exists($localCachePath))
		{
			return CACHE_WEB_ROOT . '/themes/'.$this->config['folder_name'].'/logo.png';
		}
		
		return $this->getMainFallbackLogoUrl();
	}

	public function getMainFallbackLogoUrl()
	{
		return coreFunctions::getCoreSitePath().'/themes/'.$this->config['folder_name'].'/images/main_logo_inverted.png';
	}
	
	public function getInverseLogoUrl()
	{
		// get database
		$db = Database::getDatabase();

		// see if the replaced logo exists
		$localCachePath = CACHE_DIRECTORY_ROOT . '/themes/'.$this->config['folder_name'].'/logo_inverse.png';
		if(file_exists($localCachePath))
		{
			return CACHE_WEB_ROOT . '/themes/'.$this->config['folder_name'].'/logo_inverse.png';
		}
		
		return $this->getInverseFallbackLogoUrl();
	}

	public function getInverseFallbackLogoUrl()
	{
		return coreFunctions::getCoreSitePath().'/themes/'.$this->config['folder_name'].'/images/main_logo.png';
	}
	
	public function getHomepageBackgroundImageUrl()
	{
		// see if the file exists
		$extensions = array('jpg', 'jpeg', 'png');
		foreach($extensions AS $extension)
		{
			$localCachePath = CACHE_DIRECTORY_ROOT . '/themes/'.$this->config['folder_name'].'/homepage-background.'.$extension;
			if(file_exists($localCachePath))
			{
				return CACHE_WEB_ROOT . '/themes/'.$this->config['folder_name'].'/homepage-background.'.$extension;
			}
		}
		
		return $this->getHomepageBackgroundImageFallbackUrl();
	}

	public function getHomepageBackgroundImageFallbackUrl()
	{
		return coreFunctions::getCoreSitePath().'/themes/'.$this->config['folder_name'].'/frontend_assets/images/home/banner_bg.jpg';
	}
	
	public function getHomepageBackgroundVideoUrl()
	{
		// check if it's been disabled
		$homepage_background_video_off = (int)themeHelper::getConfigValue('homepage_background_video_off');
		if($homepage_background_video_off == 1)
		{
			return false;
		}

		// see if the file exists
		$extensions = array('mp4');
		foreach($extensions AS $extension)
		{
			$localCachePath = CACHE_DIRECTORY_ROOT . '/themes/'.$this->config['folder_name'].'/homepage-background.'.$extension;
			if(file_exists($localCachePath))
			{
				return CACHE_WEB_ROOT . '/themes/'.$this->config['folder_name'].'/homepage-background.'.$extension;
			}
		}
		
		return $this->getHomepageBackgroundVideoFallbackUrl();
	}
	
	public function getHomepageBackgroundVideoFallbackUrl()
	{
		return coreFunctions::getCoreSitePath().'/themes/'.$this->config['folder_name'].'/frontend_assets/images/home/banner_video.mp4';
	}
	
	public function outputCustomCSSCode()
	{
		// see if the replaced logo exists
		$localCachePath = CACHE_DIRECTORY_ROOT . '/themes/'.$this->config['folder_name'].'/custom_css.css';
		if(file_exists($localCachePath))
		{
			return "<link href=\"".CACHE_WEB_ROOT . "/themes/".$this->config['folder_name']."/custom_css.css?r=".md5(microtime())."\" rel=\"stylesheet\">\n";
		}
	}
	
	public function getCustomCSSCode()
	{
		return themeHelper::getConfigValue('css_code');
	}
	
	public function getThemeSkin()
	{
		$skin = themeHelper::getConfigValue('site_skin');
		if(strlen($skin))
		{
			return $skin;
		}
		
		return false;
	}
}
