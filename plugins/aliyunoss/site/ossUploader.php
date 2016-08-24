<?php

/**
 * Created by PhpStorm.
 * User: fuzuc
 * Date: 2016-08-23
 * Time: 18:23
 */
class ossUploader extends uploader
{

    public function ossPost($data){
        $info[] = $this->ossHandleFileUpload(
            $data['filename'],
            $data['size'],
            $data['mimeType'],
            $data['objectName'],
            $data['user']
        );
       return $info;
    }
    public function ossHandleFileUpload($name,$size,$type,$object,$user){

        $user_id =  (int)$user > 0 ? $user : null;
        $this->options['upload_url'] = 'upload_url' .ALI_OSS_UPLOAD_DIR;
        $this->options['delete_hash'] = sha1( $name . $object . microtime(true) );

        $fileUpload        = new stdClass();
        $fileUpload->name  = stripslashes($name);
        $fileUpload->size  = intval($size);
        $fileUpload->type  = $type;
        $fileUpload->error = null;

        $parts     = explode(".", $fileUpload->name);
        $lastPart  = end($parts);
        $extension = strtolower($lastPart);


        $fileUpload->url = $this->options['upload_url'] . rawurlencode($fileUpload->name);
        $fileUpload->size        = $size;
        $fileUpload->delete_url  = '~d?' . $this->options['delete_hash'];
        $fileUpload->info_url    = '~i?' . $this->options['delete_hash'];
        $fileUpload->delete_type = 'DELETE';
        $fileUpload->delete_hash = $this->options['delete_hash'];
        $originalFilename = $fileUpload->name;
        $fileUpload->hash = md5( $name . $size . $object . microtime(true) );

        // create delete hash, make sure it's unique
        $deleteHash = md5($fileUpload->name . coreFunctions::getUsersIPAddress() . microtime());

        // reset the connection to the database so mysql doesn't time out
        $db = Database::getDatabase(true);
        $db->close();
        $db = Database::getDatabase(true);

        // store in db
        $dbInsert = new DBObject("file", array("originalFilename", "shortUrl", "fileType", "extension", "fileSize", "localFilePath", "userId", "totalDownload", "uploadedIP", "uploadedDate", "statusId", "deleteHash", "serverId", "fileHash", "adminNotes", "folderId", "uploadSource", "keywords", "unique_hash","alioss_object_name"));

        $dbInsert->originalFilename = $fileUpload->name;
        $dbInsert->shortUrl         = 'temp';
        $dbInsert->fileType         = $fileUpload->type;
        $dbInsert->extension        = strtolower($extension);
        $dbInsert->fileSize         = $fileUpload->size;
        $dbInsert->localFilePath    = null;

        // add user id if user is logged in
        $dbInsert->userId        = $user_id;
        $dbInsert->totalDownload = 0;
        $dbInsert->uploadedIP    = coreFunctions::getUsersIPAddress();
        $dbInsert->uploadedDate  = coreFunctions::sqlDateTime();
        $dbInsert->statusId      = 1;
        $dbInsert->deleteHash    = $deleteHash;
        $dbInsert->serverId      = 100;
        $dbInsert->fileHash      = md5($fileUpload->name . $fileUpload->size . $fileUpload->type . microtime(true));
        $dbInsert->adminNotes    = '';
        $dbInsert->folderId      = null;
        $dbInsert->uploadSource  = $this->options['upload_source'];
        $dbInsert->keywords      = substr(implode(',', file::getKeywordArrFromString($originalFilename)), 0, 255);
        $dbInsert->unique_hash   = file::createUniqueFileHashString();
        $dbInsert->alioss_object_name   = $object;
        $dbInsert->insert();

        // create short url
        $tracker  = 1;
        $shortUrl = file::createShortUrlPart($tracker . $dbInsert->id);
        $fileTmp  = file::loadByShortUrl($shortUrl);
        while ($fileTmp)
        {
            $shortUrl = file::createShortUrlPart($tracker . $dbInsert->id);
            $fileTmp  = file::loadByShortUrl($shortUrl);
            $tracker++;
        }

        // update short url
        file::updateShortUrl($dbInsert->id, $shortUrl);

        $file                    = file::loadByShortUrl($shortUrl);
        $fileUpload->url         = $file->getFullShortUrl();
        $fileUpload->delete_url  = $file->getDeleteUrl();
        $fileUpload->info_url    = $file->getInfoUrl();
        $fileUpload->stats_url   = $file->getStatisticsUrl();
        $fileUpload->delete_hash = $file->deleteHash;
        $fileUpload->short_url   = $shortUrl;
        $fileUpload->file_id     = $file->id;
        $fileUpload->unique_hash = $dbInsert->unique_hash;
        $fileUpload->url_html = '&lt;a href=&quot;' . $fileUpload->url . '&quot; target=&quot;_blank&quot; title=&quot;' . t('view_image_on', 'View image on') . ' ' . SITE_CONFIG_SITE_NAME . '&quot;&gt;' . t('view', 'View') . ' ' . $fileUpload->name . ' ' . t('on', 'on') . ' ' . SITE_CONFIG_SITE_NAME . '&lt;/a&gt;';
        $fileUpload->url_bbcode = '[url]' . $fileUpload->url . '[/url]';

        $fileUpload->success_result_html = self::generateSuccessHtml($fileUpload, $this->options['upload_source']);
        return $fileUpload;
    }
}