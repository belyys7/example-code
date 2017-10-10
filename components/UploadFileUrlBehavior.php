<?php

namespace api\components;

use Yii;

use yii\base\Behavior;

class UploadFileUrlBehavior extends Behavior
{
    public $path;
    public $idFile;
    public $urlFile;
    public $nameFile;
    public $nameFileGenerated;

    /**
     * Get the name from url
     */
    public function getNameOfUrl()
    {
        $this->nameFile = basename($this->urlFile);
    }

    /**
     * Generating a unique file name
     */
    public function generateNameFile()
    {
        $this->nameFileGenerated = Yii::$app->security->generateRandomString(14) . '.pdf';
    }

    /**
     * Check for document expansion - PDF
     * @return bool
     */
    public function checkRegexUrlFile()
    {
        $re = '/^(http:\/\/|https:\/\/)(.*)\/(.*)\.pdf$/';
        preg_match_all($re, $this->urlFile, $matches, PREG_SET_ORDER, 0);
        return ($matches[0]) ? true : false;
    }

    /**
     * Uploading a document to a URL
     * @param $modelFile
     * @return bool
     */
    public function uploadFileUrl($modelFile)
    {
        if ($this->checkRegexUrlFile()) {

            $this->getNameOfUrl();
            $this->generateNameFile();

            if (file_put_contents($this->path . $this->nameFileGenerated, file_get_contents($this->urlFile))) {

                $modelFile->name = $this->nameFile;
                $modelFile->file = $this->nameFileGenerated;

                if ($modelFile->validate()) {
                    $modelFile->save(false);
                    $this->idFile = $modelFile->id;
                    return true;
                }

            }
        }
    }

}