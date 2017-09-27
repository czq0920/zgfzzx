<?php
namespace app\models;

use yii\base\Model;
use yii\imagine\Image;
use yii\web\UploadedFile;

class UploadForm extends Model
{
    /**
     * @var UploadedFile
     */
    public $imageFile;

    public function rules()
    {
        return [
            [['imageFile'], 'file', 'skipOnEmpty' => false, 'extensions' => 'png, jpg','mimeTypes' => 'image/jpeg, image/png'],
        ];
    }

    public function upload()
    {
        if ($this->validate()) {
            $head_dir = \Yii::getAlias('@webroot').'/uploads/'.date('Ymd').'/';
            if(!is_dir($head_dir))
            {
                if(!mkdir($head_dir,0777,true))
                {
                    echo '创建文件目录失败！';
                    return false;
                }
            }
            $file_name = $head_dir.uniqid().'.'.$this->imageFile->extension;
            $res = $this->imageFile->saveAs($file_name);
            if($res){
                $img_info = getimagesize($file_name);
                if($img_info){
                    $length = $img_info[0];
                    $height = $img_info[1];
                    if($length>800&&$height>800){
                        //压缩
                        Image::thumbnail($file_name, 800, 800)->save($file_name, ['quality' => 100]);
                    }elseif($length>800){
                        Image::thumbnail($file_name, 800,$height)->save($file_name, ['quality' => 100]);
                    }elseif($height>800){
                        Image::thumbnail($file_name, $length,800)->save($file_name, ['quality' => 100]);
                    }
                }
                //return $this->base64Img($file_name);
                $PSize = filesize($file_name);
                $picturedata = fread(fopen($file_name, "r"), $PSize);
                return $picturedata;
            }
            return false;
        } else {
            return false;
        }
    }
    /**
     * @param $path  图片绝对路径
     * @return bool|string  转换后的base64编码图片
     *
     */
    public function base64Img($path)
    {
        try{
            $type=getimagesize($path);//取得图片的大小，类型等
            $content=file_get_contents($path);
            $file_content=chunk_split(base64_encode($content));//base64编码
            switch($type[2]){//判读图片类型
                case 1:$img_type="gif";break;
                case 2:$img_type="jpg";break;
                case 3:$img_type="png";break;
            }
            $img='data:image/'.$img_type.';base64,'.$file_content;//合成图片的base64编码
            @unlink($path);
            //return  "<img src='".$img."'>";
            return  $img;
        }catch (Exception $e){
            return false;
        }

    }

}