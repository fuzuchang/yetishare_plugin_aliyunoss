<?php

/*
 * available params
 * 
 * $params['User'];
 * */

// pickup user
$userObj = $params['User'];

// database
$db = Database::getDatabase();

// remove user from mailing list data
$db->query('DELETE FROM plugin_newsletter_unsubscribe WHERE user_id = '.(int)$userObj->id);
