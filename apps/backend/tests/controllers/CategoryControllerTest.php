<?php
namespace Robinson\Backend\Tests\Controllers;
// @codingStandardsIgnoreStart
class CategoryControllerTest extends BaseTestController
{
    protected $categoryImagesFolder;
    
    public function setUp(\Phalcon\DiInterface $di = null, \Phalcon\Config $config = null)
    {
        parent::setUp($di, $config);
        $this->populateTable('categories');
        $this->populateTable('category_images');
        // setup fs
        $this->categoryImagesFolder = \org\bovigo\vfs\vfsStream::setup('img/category');
        $this->getDI()->getShared('config')->application->categoryImagesPath = \org\bovigo\vfs\vfsStream::url('img/category');
    }
    
    public function testCategoryCreateShouldExist()
    {
        // logged in
        $this->registerMockSession();
        $this->dispatch('/admin/category/create');
        $this->assertResponseContentContains('<textarea name="description" class="ckeditor" placeholder="Tekst" required="required"></textarea>');
    }
    
    public function testCategoryCreateSubmitShouldCreateNewRecord()
    {
        // logged in
        $this->registerMockSession();
        $category = 'test category';
        $description = 'this is some category description';
        $status = 0;
        
        $request = $this->getMock('Phalcon\Http\Request', array('getPost', 'isPost'));
   
        $request->expects($this->at(2))
            ->method('getPost')
            ->with($this->equalTo('category'))
            ->will($this->returnValue($category));
        
        $request->expects($this->at(3))
            ->method('getPost')
            ->with($this->equalTo('description'))
            ->will($this->returnValue($description));
        
        $request->expects($this->at(4))
            ->method('getPost')
            ->with($this->equalTo('status'))
            ->will($this->returnValue($status));
 
        $request->expects($this->any())
            ->method('isPost')
            ->will($this->returnValue(true));
        
        $this->getDI()->setShared('request', $request);
      
        $this->dispatch('/admin/category/create');
        $this->assertRedirectTo('/admin/category/update/2');
        /* @var $categoryModel \Robinson\Backend\Models\Category */
        $categoryModel = \Robinson\Backend\Models\Category::findFirst("category = '$category'");
        $this->assertEquals($category, $categoryModel->getCategory());
        $this->assertEquals($description, $categoryModel->getDescription());
        $this->assertEquals($status, $categoryModel->getStatus());
        $this->assertNotEmpty($categoryModel->getCreatedAt());
        $this->assertNotEmpty($categoryModel->getUpdatedAt());
    }
    
    public function testCategoryUpdateShouldWorkAsExpected()
    {
        $this->registerMockSession();
        /* @var $category \Robinson\Backend\Models\Category */
        $category = \Robinson\Backend\Models\Category::findFirst();
        
        $request = $this->getMock('Phalcon\Http\Request', array('getPost', 'isPost'));
   
        $request->expects($this->at(1))
            ->method('getPost')
            ->with($this->equalTo('category'))
            ->will($this->returnValue($category->getCategory() . ' updated!'));
        
        $request->expects($this->at(2))
            ->method('getPost')
            ->with($this->equalTo('description'))
            ->will($this->returnValue($category->getDescription() . ' updated!'));
        
        $request->expects($this->at(3))
            ->method('getPost')
            ->with($this->equalTo('status'))
            ->will($this->returnValue($category->getStatus(\Robinson\Backend\Models\Category::STATUS_INVISIBLE)));
 
        $request->expects($this->any())
            ->method('isPost')
            ->will($this->returnValue(true));
        
        $this->getDI()->setShared('request', $request);
        
        $mockImagick = $this->getMock('Imagick', array('scaleimage', 'writeimage'));
        $mockImagick->expects($this->any())
            ->method('scaleimage')
            ->will($this->returnValue(true));
        $mockImagick->expects($this->any())
            ->method('writeimage')
            ->will($this->returnValue(true));
        
        $this->getDI()->set('Imagick', $mockImagick);
        
        $this->dispatch('/admin/category/update/' . $category->getCategoryId());
        $updatedCategory = \Robinson\Backend\Models\Category::findFirst("category = '" . $category->getCategory() . " updated!'");
        
        $this->assertEquals($category->getCategory() . ' updated!', $updatedCategory->getCategory());
        $this->assertEquals($category->getDescription() . ' updated!', $updatedCategory->getDescription());
        $this->assertEquals($category->getDescription() . ' updated!', $updatedCategory->getDescription());
        $this->assertEquals(\Robinson\Backend\Models\Category::STATUS_VISIBLE, $updatedCategory->getStatus());
    }
    
    public function testCategoryUpdateWithAddedImageShouldWorkAsExpected()
    {
        $this->registerMockSession();
        /* @var $category \Robinson\Backend\Models\Category */
        $category = \Robinson\Backend\Models\Category::findFirst();
        
        $request = $this->getMock('Phalcon\Http\Request', array('getPost', 'isPost', 'getUploadedFiles'));
   
        $request->expects($this->at(1))
            ->method('getPost')
            ->with($this->equalTo('category'))
            ->will($this->returnValue($category->getCategory() . ' updated!'));
        
        $request->expects($this->at(2))
            ->method('getPost')
            ->with($this->equalTo('description'))
            ->will($this->returnValue($category->getDescription() . ' updated!'));
        
        $request->expects($this->at(3))
            ->method('getPost')
            ->with($this->equalTo('status'))
            ->will($this->returnValue($category->getStatus(\Robinson\Backend\Models\Category::STATUS_INVISIBLE)));
 
        $request->expects($this->any())
            ->method('isPost')
            ->will($this->returnValue(true));
        
        $fileMock = $this->getMock('Phalcon\Http\Request\File', array('getName', 'moveTo'), array(), 'MockFileRequest', false);
        $fileMock->expects($this->exactly(2))
            ->method('getName')
            ->will($this->returnValue('testfile.png'));
        $fileMock->expects($this->once())
            ->method('moveTo')
            ->will($this->returnValue(true));
        
        $request->expects($this->once())
            ->method('getUploadedFiles')
            ->will($this->returnValue(array
            (
                0 => $fileMock,
            )));
        
        $categoryImage = $this->getMockBuilder('Robinson\Backend\Models\Images\Category')
            ->setMethods(array('applyWatermark'))
            ->getMock();
        $categoryImage->expects($this->once())
            ->method('applyWatermark')
            ->will($this->returnValue(true));
        $this->getDI()->set('Robinson\Backend\Models\Images\Category', $categoryImage);
            
        $mockImagick = $this->getMock('Imagick', array('scaleimage', 'writeimage'));
        $mockImagick->expects($this->any())
            ->method('scaleimage')
            ->will($this->returnValue(true));
        $mockImagick->expects($this->any())
            ->method('writeimage')
            ->will($this->returnValue(true));
 
        $this->getDI()->setShared('request', $request);
        $this->getDI()->set('Imagick', $mockImagick);
        $this->dispatch('/admin/category/update/' . $category->getCategoryId());
        
        $this->assertResponseContentContains('<dt>Slike:</dt>
            
                        
            <dd>
                <img');
        
        $updatedCategory = \Robinson\Backend\Models\Category::findFirst("category = '" . $category->getCategory() . " updated!'");
        
        $this->assertEquals($category->getCategory() . ' updated!', $updatedCategory->getCategory());
        $this->assertEquals($category->getDescription() . ' updated!', $updatedCategory->getDescription());
        $this->assertEquals($category->getDescription() . ' updated!', $updatedCategory->getDescription());
        $this->assertEquals(\Robinson\Backend\Models\Category::STATUS_VISIBLE, $updatedCategory->getStatus());
    }
    
    public function testCategoryUpdateChangingOrderShouldWorkAsExpected()
    {
        $this->registerMockSession();
        /* @var $category \Robinson\Backend\Models\Category */
        $category = \Robinson\Backend\Models\Category::findFirstByCategoryId(1);
        
        $sort = array
        (
            1 => 2,
            2 => 1,
            3 => 4,
            4 => 5,
            5 => 3,
        );
        
        $request = $this->getMock('Phalcon\Http\Request', array('getPost', 'isPost'));
        $request->expects($this->at(4))
            ->method('getPost')
            ->with($this->equalTo('sort'))
            ->will($this->returnValue($sort));
        $request->expects($this->at(5))
            ->method('getPost')
            ->with($this->equalTo('sort'))
            ->will($this->returnValue($sort));
        $request->expects($this->at(6))
            ->method('getPost')
            ->with($this->equalTo('sort'))
            ->will($this->returnValue($sort));
        $request->expects($this->at(7))
            ->method('getPost')
            ->with($this->equalTo('sort'))
            ->will($this->returnValue($sort));
        $request->expects($this->at(8))
            ->method('getPost')
            ->with($this->equalTo('sort'))
            ->will($this->returnValue($sort));
        $request->expects($this->at(9))
            ->method('getPost')
            ->with($this->equalTo('sort'))
            ->will($this->returnValue($sort));
        $request->expects($this->at(10))
            ->method('getPost')
            ->with($this->equalTo('sort'))
            ->will($this->returnValue($sort));
        $request->expects($this->at(11))
            ->method('getPost')
            ->with($this->equalTo('sort'))
            ->will($this->returnValue($sort));
        $request->expects($this->at(12))
            ->method('getPost')
            ->with($this->equalTo('sort'))
            ->will($this->returnValue($sort));
         $request->expects($this->at(13))
            ->method('getPost')
            ->with($this->equalTo('sort'))
            ->will($this->returnValue($sort));
        
        $request->expects($this->at(1))
            ->method('getPost')
            ->with($this->equalTo('category'))
            ->will($this->returnValue($category->getCategory() . ' updated!'));
        
        $request->expects($this->at(2))
            ->method('getPost')
            ->with($this->equalTo('description'))
            ->will($this->returnValue($category->getDescription() . ' updated!'));
        
        $request->expects($this->at(3))
            ->method('getPost')
            ->with($this->equalTo('status'))
            ->will($this->returnValue($category->getStatus(\Robinson\Backend\Models\Category::STATUS_INVISIBLE)));
 
        $request->expects($this->any())
            ->method('isPost')
            ->will($this->returnValue(true));
        
        $mockImagick = $this->getMock('Imagick', array('scaleimage', 'writeimage'));
        $mockImagick->expects($this->any())
            ->method('scaleimage')
            ->will($this->returnValue(true));
        $mockImagick->expects($this->any())
            ->method('writeimage')
            ->will($this->returnValue(true));
        $splFileInfoMock = $this->getMock('SplFileInfo', array('isFile'), array(), 'MockSplFileInfo', false);
        $splFileInfoMock->expects($this->any())
            ->method('isFile')
            ->will($this->returnValue(true));
        $this->getDI()->set('SplFileInfo', $splFileInfoMock);
        $this->getDI()->setShared('request', $request);
        $this->getDI()->set('Imagick', $mockImagick);

        $this->dispatch('/admin/category/update/' . $category->getCategoryId());
        
        $imageCategories = \Robinson\Backend\Models\Images\Category::find("categoryId = 1");
      
        foreach($imageCategories as $image)
        {
            $this->assertEquals($sort[$image->getImageId()], $image->getSort());
        }
    }
    
    public function testDeletingCategoryImageShouldWorkAsExpected()
    {
        $this->registerMockSession();
        $requestMock = $this->getMock('Phalcon\Http\Request', array('getPost'));
        $requestMock->expects($this->once())
            ->method('getPost')
            ->with($this->equalTo('id'))
            ->will($this->returnValue(3));
        $this->getDI()->setShared('request', $requestMock);
        $this->dispatch('/admin/category/deleteImage');
        $image = \Robinson\Backend\Models\Images\Category::findFirst(3);
        $this->assertFalse($image);
    }
    
    public function testCategoryIndexShouldDisplayCategoryPage()
    {
        $this->registerMockSession();
        $this->dispatch('/admin/category/index');
        $this->assertResponseCode(200);
    }
} 