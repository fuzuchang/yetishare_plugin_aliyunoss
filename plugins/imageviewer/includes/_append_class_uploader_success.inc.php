<?php

$ext = array('jpg', 'jpeg', 'png', 'gif');

// pick up variables
if (isset($params['file']))
{
    // uploaded file object
    $file = $params['file'];

    // check file type
    if (in_array(strtolower($file->extension), $ext))
    {
        // get image width/height
        $contents = $file->download(false);
		if(strlen($contents))
		{
			// load into memory
			$im = imagecreatefromstring($contents);
			if ($im)
			{
				// get image size
				$imageWidth  = imagesx($im);
				$imageHeight = imagesy($im);

				$rawData       = array();
				if(function_exists('exif_read_data'))
				{
					// temp save image in cache for exif function
					$imageFilename = 'plugins/imageviewer/_tmp/' . md5(microtime() . $file->id) . '.' . $file->extension;
					$cachePath     = cache::saveCacheToFile($imageFilename, $contents);
					if ($cachePath)
					{
						$exif = exif_read_data($cachePath, 0, true);
                        if($exif)
                        {
                            foreach ($exif as $key => $section)
                            {
                                // only log certain types of data
                                if(!in_array($key, array('IFD0', 'EXIF', 'COMMENT')))
                                {
                                    continue;
                                }

                                foreach ($section as $name => $val)
                                {
                                    // stop really long data
                                    if(COUNT($rawData) > 200)
                                    {
                                        continue;
                                    }

                                    // limit text length just encase someone if trying to feed it invalid data
                                    $rawData[substr($name, 0, 200)] = substr($val, 0, 500);
                                }
                            }
                        }
					}
					
					// clear cached file
					cache::removeCacheFile($imageFilename);
				}

				// store width/height
				$dbInsert           = new DBObject("plugin_imageviewer_meta", array("file_id", "width", "height", "raw_data"));
				$dbInsert->file_id  = $file->id;
				$dbInsert->width    = $imageWidth;
				$dbInsert->height   = $imageHeight;
				$dbInsert->raw_data = json_encode($rawData);
				$dbInsert->insert();
			}
		}
    }
}