<?php

namespace app\controllers;
use app\models\UploadForm;
use app\src\API;
use Yii;
use yii\imagine\Image;
use yii\web\Controller;
use yii\web\UploadedFile;

class SearchController extends Controller
{
    private $access_key_id = '0d30d0979a1c9410fe44cc6387b255b7';
    private $secret_key = 'ef4c95300c780f41986c036c7b166908';
    private $service_type = 'search';
    private $service_id = '_0000025';

    public function actionIndex()
    {
        $model = new UploadForm();
        if (Yii::$app->request->isPost) {
            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
            if ($file_path = $model->upload()) {
                // 文件上传成功
                $api = new API($this->access_key_id,$this->secret_key);
                $res = $api->searchImage($this->service_type,$this->service_id,$file_path);
                if($res){
                    echo '<pre>';
                    var_dump($res);die;
                    //$sss = $api->addImageToSet($res['image_id'],'http://www.productai.cn');
                    //var_dump($sss);die;
                }
            }
        }
        return $this->render('index', ['model' => $model]);
    }
}
