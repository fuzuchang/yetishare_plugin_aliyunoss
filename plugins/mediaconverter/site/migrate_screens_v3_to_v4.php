<?php

set_time_limit(60*60*4); // 4 hours

// setup includes
require_once('../../../includes/master.inc.php');

// load all videos
$videos = $db->getRows('SELECT id, originalFilename FROM file WHERE extension IN ("mp4") AND statusId = 1');

// output
echo "Found ".COUNT($videos)." videos to check for screenshots.<br/>";

// loop files
if(COUNT($videos))
{
	foreach($videos AS $video)
	{
		// find screenshot
		$path = DOC_ROOT.'/plugins/mediaconverter/site/screenshots/'.MD5((int)$video['id']).'.jpg';
		if(file_exists($path))
		{
			// prepare storage
			$newPath = DOC_ROOT.'/core/cache/plugins/mediaconverter/'.(int)$video['id'].'/original_thumb.jpg';
			$newFolderPath = dirname($newPath);
			@mkdir($newFolderPath, 0755, true);
			
			// move file
			$done = copy($path, $newPath);
            if ($done)
            {
                echo "<span style='color: green;'>Screenshot migrated for video #".(int)$video['id']."</span><br/>";
            }
			else
			{
				echo "<span style='color: red;'>Failed copying screenshot #".(int)$video['id']." from ".$path." to ".$newPath." (possibly permissions)</span><br/>";
			}
		}
		else
		{
			// not found
			echo "No screenshot found for video #".(int)$video['id']."<br/>";
		}
	}
}

// get original screenshot
echo "Finished process.<br/>";
echo "Any migrated screenshots will be in ".DOC_ROOT."/core/cache/plugins/mediaconverter/<br/>";
