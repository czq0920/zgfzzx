<?php

namespace ProductAI\Tests;

use PHPUnit\Framework\TestCase;
use ProductAI\API;

class APITest extends TestCase
{
    private $product_ai;

    protected function setUp()
    {
        $this->product_ai = new API(ACCESS_KEY_ID, SECRET_KEY);
        $this->product_ai->curl_opt[CURLOPT_TIMEOUT] = 120;
    }

    public function testBadMethodCall()
    {
        $this->expectException('BadMethodCallException');
        $this->product_ai->notExistsMethod();
    }

    public function testSearchImageByURL()
    {
        $result = $this->product_ai->searchImage(SERVICE_TYPE_SEARCH, SERVICE_ID_SEARCH,
            'http://www.sinaimg.cn/dy/slidenews/24_img/2013_13/40223_662671_794351.jpg', [], [
                '长靴',
                '针织衫',
                '短靴',
                '牛仔裤',
                '打底裤',
            ], 20, 0.8);
        $this->assertArrayHasKey('results', $result);
    }

    public function testSearchImageByFile()
    {
        $result = $this->product_ai->searchImage(SERVICE_TYPE_SEARCH, SERVICE_ID_SEARCH, '@'.__DIR__.'/test.jpg', [
            0.1, 0.2, 0.8, 0.6
        ], [], 10, 0);
        $this->assertArrayHasKey('results', $result);
    }

    public function testSearchImageByString()
    {
        $result = $this->product_ai->searchImage(SERVICE_TYPE_SEARCH, SERVICE_ID_SEARCH, file_get_contents(__DIR__.'/test.jpg'));
        $this->assertArrayHasKey('results', $result);
    }

    public function testSearchImageByForm()
    {
        $this->expectException('OutOfBoundsException');
        $this->product_ai->searchImage(SERVICE_TYPE_SEARCH, SERVICE_ID_SEARCH, '#test');
    }

    public function testClassifyImage()
    {
        $result = $this->product_ai->classifyImage(SERVICE_TYPE_CLASSIFY, SERVICE_ID_CLASSIFY, 'http://www.sinaimg.cn/dy/slidenews/24_img/2013_13/40223_662671_794351.jpg');
        $this->assertEquals(0, $result['is_err']);
    }

    public function testDetectImage()
    {
        $result = $this->product_ai->detectImage(SERVICE_TYPE_DETECT, SERVICE_ID_DETECT, 'http://www.wed114.cn/jiehun/uploads/allimg/c130401/1364P42Q140-49539.jpg');
        $this->assertArrayNotHasKey('is_err', $result);
    }

    public function testAddImageToSet()
    {
        $result = $this->product_ai->addImageToSet(IMAGE_SET_ID, 'http://www.wed114.cn/jiehun/uploads/allimg/c130401/1364P42Q140-49539.jpg', 'test image', [
            '百褶裙',
            '针织衫',
            '紧身半裙',
            '短夹克',
            '直筒裙',
        ]);
        $this->assertArrayNotHasKey('error_code', $result);
    }

    public function testAddImagesToSet()
    {
        $result = $this->product_ai->addImagesToSet(IMAGE_SET_ID, [
            [
                'url' => 'http://images.yoka.com/pic/fashion/roadshow/2010/U162P1T117D149859F2577DT20100827201536.JPG',
                'meta' => 'test image 1',
                'tag' => [
                    '无肩带裙',
                    'A字裙',
                    '连体裤',
                    '紧身连衣裙',
                    '女式衬衫',
                ],
            ],
            [
                'url' => 'http://www.people.com.cn/mediafile/pic/20130924/54/1190700219959357062.jpg',
                'meta' => 'test image 2',
                'tags' => [
                    '短靴',
                    '吊带裙',
                    '时尚平底鞋',
                    '帆布鞋',
                    '短夹克',
                ]
            ],
        ]);
        $this->assertArrayNotHasKey('error_code', $result);
    }

    public function testRemoveImagesFromSet()
    {
        $result = $this->product_ai->removeImagesFromSet(IMAGE_SET_ID, [
            'http://images.yoka.com/pic/fashion/roadshow/2010/U162P1T117D149859F2577DT20100827201536.JPG',
            'http://www.people.com.cn/mediafile/pic/20130924/54/1190700219959357062.jpg',
        ]);
        $this->assertArrayNotHasKey('error_code', $result);
    }

    public function testImageColorAnalysis()
    {
        $result = $this->product_ai->imageColorAnalysis(file_get_contents(__DIR__.'/test.jpg'), 'person_outfit', 'major', 'basic');
        $this->assertArrayHasKey('results', $result);
    }
}
