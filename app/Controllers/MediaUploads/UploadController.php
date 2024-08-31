<?php

namespace App\Controllers\MediaUploads;

use App\Controllers\BaseController;
use App\Libraries\S3Library;

class UploadController extends BaseController
{
    public function media_upload($mediafile,$mediafolder)
    {
       
        $file = $mediafile;
        $folder = $mediafolder;

        if ($file->isValid() && !$file->hasMoved()) {
            $filePath = $file->getTempName();
            $fileName = $file->getName();
            $s3Library = new S3Library();
            $result = $s3Library->uploadFile($filePath, $fileName, $folder);

            if (is_string($result)) {
                return $this->response->setJSON(['error' => $result]);
            }

            return $this->response->setJSON(['success' => 'File uploaded successfully', 'url' => $result['ObjectURL']]);
        }

        return $this->response->setJSON(['error' => 'File upload failed']);
    }

    
}
