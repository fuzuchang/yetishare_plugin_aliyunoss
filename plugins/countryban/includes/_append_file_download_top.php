<?php

// load plugin details
$pluginConfig   = pluginHelper::pluginSpecificConfiguration('countryban');
$pluginSettings = json_decode($pluginConfig['data']['plugin_settings'], true);

// check country
if(strlen($pluginSettings['banned_countries']))
{
	// get country
	$visitorCountry = strtolower(Stats::getCountry(Stats::getIP()));
	
	// check if it's banned
	$bannedCountriesArr = explode("|", strtolower($pluginSettings['banned_countries']));
	if (in_array($visitorCountry, $bannedCountriesArr))
	{
		header('HTTP/1.0 403 Forbidden');
		die();
	}
}